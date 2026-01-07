<?php
/**
 * Checkout Process
 * Procesare comenzi - validare, creare ordine, gestionare plăți
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions_orders.php';
require_once __DIR__ . '/../includes/functions_referral.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/cart.php');
}

// Debugging: logare POST data (doar în development)
if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
    error_log("=== CHECKOUT POST DATA ===");
    error_log("POST Keys: " . implode(", ", array_keys($_POST)));
    error_log("customer_name: " . ($_POST['customer_name'] ?? 'MISSING'));
    error_log("customer_email: " . ($_POST['customer_email'] ?? 'MISSING'));
    error_log("customer_phone: " . ($_POST['customer_phone'] ?? 'MISSING'));
    error_log("shipping_address: " . ($_POST['shipping_address'] ?? 'MISSING'));
    error_log("payment_method: " . ($_POST['payment_method'] ?? 'MISSING'));
}

// Verificare CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    setMessage("Token invalid. Încearcă din nou.", "danger");
    redirect('/pages/checkout.php');
}

// Validare date POST - extragere și curățare
$customerName = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
$customerEmail = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
$customerPhone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
$shippingAddress = isset($_POST['shipping_address']) ? trim($_POST['shipping_address']) : '';
$paymentMethod = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// Validare câmpuri obligatorii - verifică dacă sunt complet goale DUPĂ trim
if ($customerName === '' || $customerEmail === '' || $customerPhone === '' || $shippingAddress === '') {
    // Debugging: loghează ce câmpuri lipsesc
    $missingFields = [];
    if ($customerName === '') $missingFields[] = 'Nume Complet';
    if ($customerEmail === '') $missingFields[] = 'Email';
    if ($customerPhone === '') $missingFields[] = 'Telefon';
    if ($shippingAddress === '') $missingFields[] = 'Adresă Livrare';
    
    $errorMsg = "Completează toate câmpurile obligatorii: " . implode(', ', $missingFields);
    setMessage($errorMsg, "danger");
    redirect('/pages/checkout.php');
}

if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    setMessage("Adresa de email este invalidă.", "danger");
    redirect('/pages/checkout.php');
}

// Validare telefon mai flexibilă (acceptă și spații/caractere)
$cleanPhone = preg_replace('/[^0-9]/', '', $customerPhone);
if (strlen($cleanPhone) < 10) {
    setMessage("Numărul de telefon trebuie să conțină cel puțin 10 cifre.", "danger");
    redirect('/pages/checkout.php');
}

if (!in_array($paymentMethod, ['bank_transfer', 'stripe'])) {
    setMessage("Metodă de plată invalidă.", "danger");
    redirect('/pages/checkout.php');
}

$db = getDB();
$userId = isLoggedIn() ? $_SESSION['user_id'] : null;

// Asigură că session_id există pentru guest users
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}
$sessionId = $_SESSION['session_id'];

// Obține produse din coș
if ($userId) {
    $stmt = $db->prepare("
        SELECT c.id as cart_id, c.quantity, p.id, p.name, p.price, p.sale_price
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
} else {
    $stmt = $db->prepare("
        SELECT c.id as cart_id, c.quantity, p.id, p.name, p.price, p.sale_price
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.session_id = ?
    ");
    $stmt->bind_param("s", $sessionId);
}

$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Verificare coș gol
if (empty($cartItems)) {
    setMessage("Coșul tău este gol.", "warning");
    redirect('/pages/cart.php');
}

// Produse digitale: calcul subtotal fără cantități/stoc
$subtotal = 0;
foreach ($cartItems as $item) {
    $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
    $subtotal += $price; // cantitate implicit 1
}

// Calculare discount dacă există cupon
$discount = 0;
$couponCode = null;
$appliedCoupon = $_SESSION['applied_coupon'] ?? null;

if ($appliedCoupon) {
    $couponCode = $appliedCoupon['code'];
    
    if ($appliedCoupon['discount_type'] === 'percent') {
        $discount = ($subtotal * $appliedCoupon['discount_value']) / 100;
    } else {
        $discount = $appliedCoupon['discount_value'];
    }
}

// Aplicare credit din referrals (dacă există)
$creditUsed = 0;
if ($userId && isset($_SESSION['credit_to_use']) && $_SESSION['credit_to_use'] > 0) {
    $availableCredit = getUserCreditBalance($userId);
    $requestedCredit = floatval($_SESSION['credit_to_use']);
    
    // Validează că utilizatorul chiar are creditul solicitat
    $creditUsed = min($requestedCredit, $availableCredit, ($subtotal - $discount));
}

$totalAmount = $subtotal - $discount - $creditUsed;

// Generare order number unic
$orderNumber = 'BRD' . date('Ymd') . strtoupper(substr(uniqid(), -6));

// Începe tranzacție
$db->begin_transaction();

try {
    // Construiește JSON cu produse (fără quantity)
    $products = array_map(function($item) {
        $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
        return [
            'id' => (int)$item['id'],
            'name' => $item['name'],
            'price' => (float)$price
        ];
    }, $cartItems);
    $productsJson = json_encode($products, JSON_UNESCAPED_UNICODE);

    // Pentru comenzile guest, user_id trebuie să fie NULL (nu 0)
    $userIdForDb = $userId ? $userId : null;

    // Inserare în tabelul orders
    if ($userIdForDb) {
        $stmt = $db->prepare("
            INSERT INTO orders (
                user_id, customer_name, customer_email, customer_phone, shipping_address,
                order_number, total_amount, subtotal, discount_amount, coupon_code,
                status, payment_status, payment_method, notes,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', ?, ?, NOW())
        ");
        $stmt->bind_param("isssssdddsss", 
            $userIdForDb, $customerName, $customerEmail, $customerPhone, $shippingAddress,
            $orderNumber, $totalAmount, $subtotal, $discount, $couponCode,
            $paymentMethod, $notes
        );
    } else {
        $stmt = $db->prepare("
            INSERT INTO orders (
                customer_name, customer_email, customer_phone, shipping_address,
                order_number, total_amount, subtotal, discount_amount, coupon_code,
                status, payment_status, payment_method, notes,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', ?, ?, NOW())
        ");
        $stmt->bind_param("sssssdddsss", 
            $customerName, $customerEmail, $customerPhone, $shippingAddress,
            $orderNumber, $totalAmount, $subtotal, $discount, $couponCode,
            $paymentMethod, $notes
        );
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Database insert failed: " . $stmt->error);
    }
    $orderId = $db->insert_id;
    
    // Inserăm temporar în order_items pentru fiecare produs (până când migrăm schema)
    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, 1)");
    foreach ($cartItems as $item) {
        $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
        $stmt->bind_param("iisd", $orderId, $item['id'], $item['name'], $price);
        if (!$stmt->execute()) {
            throw new Exception("Order item insert failed: " . $stmt->error);
        }
    }
    
    // Incrementare utilizări cupon dacă există
    if (!empty($couponCode)) {
        $stmt = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?");
        if ($stmt) {
            $stmt->bind_param("s", $couponCode);
            $stmt->execute();
        }
    }
    
    // Ștergere coș
    if ($userId) {
        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $db->prepare("DELETE FROM cart WHERE session_id = ?");
        $stmt->bind_param("s", $sessionId);
    }
    $stmt->execute();
    
    // Elimină cuponul aplicat din sesiune
    unset($_SESSION['applied_coupon']);
    
    // ✅ APLICARE CREDIT DIN REFERRALS (dacă a fost folosit)
    if ($userId && $creditUsed > 0) {
        $result = applyCreditToOrder($userId, $orderId, $creditUsed);
        
        if ($result['success']) {
            error_log("Credit aplicat cu succes: {$creditUsed} RON pentru comanda #{$orderId}");
        } else {
            error_log("Eroare la aplicarea creditului: " . $result['message']);
        }
        
        // Curățare credit din sesiune
        unset($_SESSION['credit_to_use']);
    }
    
    // Commit tranzacție
    $db->commit();
    
    // ✅ ACTIVARE DESCĂRCĂRI - Folosește funcția centralizată
    // Pentru comenzi gratuite (0 RON) sau transfer bancar confirmat
    if ($totalAmount == 0) {
        // Comandă gratuită - activează imediat descărcările
        processFreeOrder($orderId);
    } elseif ($paymentMethod === 'bank_transfer') {
        // Transfer bancar - descărcările se vor activa după confirmare admin
        // Nu activăm automat aici
    }

    // ✅ TRIMITE EMAIL DE CONFIRMARE
    $orderData = [
        'id' => $orderId,
        'order_number' => $orderNumber,
        'customer_email' => $customerEmail,
        'customer_name' => $customerName,
        'total_amount' => $totalAmount,
        'payment_method' => $paymentMethod
    ];
    sendOrderConfirmationEmail($orderData);

    // Procesare în funcție de metoda de plată
    if ($paymentMethod === 'bank_transfer') {
        // Track purchase event for bank transfer (GA4)
        require_once __DIR__ . '/../includes/analytics.php';
        trackPurchase($totalAmount, 'RON', $orderNumber);
        
        // Redirect către instrucțiuni plată
        redirect('/pages/payment_instructions.php?order=' . $orderNumber);
        
    } elseif ($paymentMethod === 'stripe') {
        // Verificare dacă Stripe SDK e instalat
        if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
            setMessage("Plata cu cardul nu este disponibilă momentan. Vă rugăm alegeți Transfer Bancar.", "warning");
            redirect('/pages/checkout.php');
        }
        
        // Integrare Stripe
        require_once __DIR__ . '/../vendor/autoload.php';
        
        if (!defined('STRIPE_SECRET_KEY') || empty(STRIPE_SECRET_KEY)) {
            setMessage("Plata cu cardul nu este configurată. Vă rugăm alegeți Transfer Bancar.", "warning");
            redirect('/pages/checkout.php');
        }
        
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        
        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'ron',
                        'product_data' => [
                            'name' => 'Comandă #' . $orderNumber,
                            'description' => count($cartItems) . ' produs(e) - Brodero'
                        ],
                        'unit_amount' => round($totalAmount * 100), // în bani (cenți)
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => SITE_URL . '/pages/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => SITE_URL . '/pages/payment_cancel.php?order=' . $orderNumber,
                'customer_email' => $customerEmail,
                'metadata' => [
                    'order_id' => $orderId,
                    'order_number' => $orderNumber
                ]
            ]);
            
            // Salvare stripe_session_id în DB
            $stmt = $db->prepare("UPDATE orders SET stripe_session_id = ? WHERE id = ?");
            $stmt->bind_param("si", $session->id, $orderId);
            $stmt->execute();
            
            // CRITICAL: Save session before redirect to Stripe
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            
            // Redirect la Stripe Checkout
            header('Location: ' . $session->url);
            exit;
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            setMessage("Eroare la procesarea plății. Încearcă din nou.", "danger");
            redirect('/pages/checkout.php');
        }
    }
    
} catch (Exception $e) {
    // Rollback în caz de eroare
    if (isset($db)) {
        $db->rollback();
    }
    setMessage("Eroare la procesarea comenzii: " . $e->getMessage(), "danger");
    redirect('/pages/checkout.php');
}
?>
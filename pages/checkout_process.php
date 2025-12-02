<?php
/**
 * Checkout Process
 * Procesare comandă - validare, creare ordine, gestionare plăți
 */

// Enable error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    error_log("=== CHECKOUT PROCESS START ===");

    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        error_log("Checkout: Not POST method");
        redirect('/pages/cart.php');
    }

    // Verificare CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setMessage("Token invalid. Încearcă din nou.", "danger");
        redirect('/pages/checkout.php');
    }

    // Validare date POST
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? '';
$notes = trim($_POST['notes'] ?? '');

error_log("Checkout POST data - Name: $customerName, Email: $customerEmail, Payment: $paymentMethod");

if (empty($customerName) || empty($customerEmail) || empty($customerPhone) || empty($shippingAddress)) {
    error_log("Checkout: Missing required fields");
    setMessage("Completează toate câmpurile obligatorii.", "danger");
    redirect('/pages/checkout.php');
}

if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    error_log("Checkout: Invalid email - $customerEmail");
    setMessage("Adresa de email este invalidă.", "danger");
    redirect('/pages/checkout.php');
}

// Validare telefon mai flexibilă (acceptă și spații/caractere)
$cleanPhone = preg_replace('/[^0-9]/', '', $customerPhone);
if (strlen($cleanPhone) < 10) {
    error_log("Checkout: Invalid phone - $customerPhone");
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

error_log("Checkout: Found " . count($cartItems) . " cart items");

// Verificare coș gol
if (empty($cartItems)) {
    error_log("Checkout: Cart is empty");
    setMessage("Coșul tău este gol.", "warning");
    redirect('/pages/cart.php');
}

// Produse digitale: calcul subtotal fără cantități/stoc
$subtotal = 0;
foreach ($cartItems as $item) {
    $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
    $subtotal += $price; // cantitate implicită 1
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

$totalAmount = $subtotal - $discount;

// Generare order number unic
$orderNumber = 'BRD' . date('Ymd') . strtoupper(substr(uniqid(), -6));

error_log("Checkout: Starting transaction for order $orderNumber, Total: $totalAmount");

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
                user_id, order_number, total_amount,
                status, payment_status, payment_method, notes,
                created_at
            ) VALUES (?, ?, ?, 'pending', 'unpaid', ?, ?, NOW())
        ");
        $stmt->bind_param("isdss", $userIdForDb, $orderNumber, $totalAmount, $paymentMethod, $notes);
    } else {
        $stmt = $db->prepare("
            INSERT INTO orders (
                order_number, total_amount,
                status, payment_status, payment_method, notes,
                created_at
            ) VALUES (?, ?, 'pending', 'unpaid', ?, ?, NOW())
        ");
        $stmt->bind_param("sdss", $orderNumber, $totalAmount, $paymentMethod, $notes);
    }
    
    if (!$stmt->execute()) {
        error_log("Orders INSERT failed: " . $stmt->error);
        throw new Exception("Database insert failed");
    }
    $orderId = $db->insert_id;
    
    // Inserăm temporar în order_items pentru fiecare produs (până când migrăm schema)
    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, 1)");
    foreach ($cartItems as $item) {
        $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
        $stmt->bind_param("iisd", $orderId, $item['id'], $item['name'], $price);
        if (!$stmt->execute()) {
            error_log("Order item insert failed: " . $stmt->error);
            throw new Exception("Order item insert failed");
        }
    }
    
    // Incrementare utilizări cupon dacă există
    if (!empty($couponCode)) {
        $stmt = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?");
        if ($stmt) {
            $stmt->bind_param("s", $couponCode);
            if (!$stmt->execute()) {
                error_log("Coupon used_count update failed: " . $stmt->error);
            }
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
    if (!$stmt->execute()) {
        error_log("Cart clear failed: " . $stmt->error);
    }
    
    // Elimină cuponul aplicat din sesiune
    unset($_SESSION['applied_coupon']);
    
    // Commit tranzacție
    $db->commit();
    
    // Setează downloads_enabled pe iteme
    $enableDownloads = ($paymentMethod === 'stripe') ? 1 : 0;
    $stmt = $db->prepare("UPDATE order_items SET downloads_enabled = ? WHERE order_id = ?");
    if ($stmt) { $stmt->bind_param("ii", $enableDownloads, $orderId); $stmt->execute(); }

    // Procesare în funcție de metoda de plată
    if ($paymentMethod === 'bank_transfer') {
        // Redirect către instrucțiuni plată
        redirect('/pages/payment_instructions.php?order=' . $orderNumber);
        
    } elseif ($paymentMethod === 'stripe') {
        // Integrare Stripe
        require_once __DIR__ . '/../vendor/autoload.php';
        
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
            
            // Redirect la Stripe Checkout
            header('Location: ' . $session->url);
            exit;
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe Error: " . $e->getMessage());
            setMessage("Eroare la procesarea plății. Încearcă din nou.", "danger");
            redirect('/pages/checkout.php');
        }
    }
    
} catch (Exception $e) {
    // Rollback în caz de eroare
    if (isset($db)) {
        $db->rollback();
    }
    error_log("Checkout Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Afișează eroarea direct pe ecran pentru debugging
    die("EROARE CHECKOUT: " . $e->getMessage() . "<br>Fișier: " . $e->getFile() . "<br>Linia: " . $e->getLine() . "<br><pre>" . $e->getTraceAsString() . "</pre>");
}

} catch (Throwable $e) {
    // Catch-all pentru orice eroare PHP
    error_log("FATAL Checkout Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Afișează eroarea direct pe ecran pentru debugging
    die("EROARE FATALĂ CHECKOUT: " . $e->getMessage() . "<br>Fișier: " . $e->getFile() . "<br>Linia: " . $e->getLine() . "<br><pre>" . $e->getTraceAsString() . "</pre>");
}
?>

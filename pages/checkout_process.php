<?php
// DEBUG MODE - CHECKOUT PROCESS
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h3>DEBUG MODE STARTED</h3>";
flush();

try {
    echo "1. Loading config...<br>";
    require_once __DIR__ . '/../config/config.php';
    echo "Config loaded.<br>";
    
    echo "2. Loading database...<br>";
    require_once __DIR__ . '/../config/database.php';
    echo "Database loaded.<br>";

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die("ERROR: Request method is " . $_SERVER['REQUEST_METHOD'] . ", expected POST. <a href='/pages/cart.php'>Back to cart</a>");
    }

    echo "3. Checking CSRF...<br>";
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("ERROR: CSRF Token mismatch. <a href='/pages/checkout.php'>Back to checkout</a>");
    }
    echo "CSRF OK.<br>";

    // Validare date POST
    echo "4. Validating POST data...<br>";
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    echo "Data: Name=$customerName, Email=$customerEmail, Phone=$customerPhone, Method=$paymentMethod<br>";

    if (empty($customerName) || empty($customerEmail) || empty($customerPhone) || empty($shippingAddress)) {
        die("ERROR: Missing fields. <a href='/pages/checkout.php'>Back</a>");
    }

    if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        die("ERROR: Invalid email. <a href='/pages/checkout.php'>Back</a>");
    }

    $cleanPhone = preg_replace('/[^0-9]/', '', $customerPhone);
    if (strlen($cleanPhone) < 10) {
        die("ERROR: Invalid phone. <a href='/pages/checkout.php'>Back</a>");
    }

    if (!in_array($paymentMethod, ['bank_transfer', 'stripe'])) {
        die("ERROR: Invalid payment method. <a href='/pages/checkout.php'>Back</a>");
    }
    echo "Validation OK.<br>";

    echo "5. Connecting to DB...<br>";
    $db = getDB();
    $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
    echo "DB Connected. UserID: " . ($userId ? $userId : 'Guest') . "<br>";

    if (!isset($_SESSION['session_id'])) {
        $_SESSION['session_id'] = session_id();
    }
    $sessionId = $_SESSION['session_id'];
    echo "SessionID: $sessionId<br>";

    echo "6. Fetching cart items...<br>";
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

    if (!$stmt->execute()) {
        throw new Exception("Cart query failed: " . $stmt->error);
    }
    $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo "Cart items found: " . count($cartItems) . "<br>";

    if (empty($cartItems)) {
        die("ERROR: Cart is empty. <a href='/pages/cart.php'>Back to cart</a>");
    }

    echo "7. Calculating totals...<br>";
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
        $subtotal += $price;
    }

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
    echo "Total calculated: $totalAmount (Sub: $subtotal, Disc: $discount)<br>";

    $orderNumber = 'BRD' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    echo "Order Number: $orderNumber<br>";

    echo "8. Starting transaction...<br>";
    $db->begin_transaction();

    echo "9. Inserting Order...<br>";
    $userIdForDb = $userId ? $userId : null;

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
        throw new Exception("Order insert failed: " . $stmt->error);
    }
    $orderId = $db->insert_id;
    echo "Order inserted. ID: $orderId<br>";
    
    echo "10. Inserting Order Items...<br>";
    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, 1)");
    foreach ($cartItems as $item) {
        $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
        $stmt->bind_param("iisd", $orderId, $item['id'], $item['name'], $price);
        if (!$stmt->execute()) {
            throw new Exception("Order item insert failed: " . $stmt->error);
        }
        echo "Item inserted: " . $item['name'] . "<br>";
    }
    
    if (!empty($couponCode)) {
        echo "Updating coupon usage...<br>";
        $stmt = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?");
        if ($stmt) {
            $stmt->bind_param("s", $couponCode);
            $stmt->execute();
        }
    }
    
    echo "11. Clearing cart...<br>";
    if ($userId) {
        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $db->prepare("DELETE FROM cart WHERE session_id = ?");
        $stmt->bind_param("s", $sessionId);
    }
    $stmt->execute();
    unset($_SESSION['applied_coupon']);
    
    echo "12. Committing transaction...<br>";
    $db->commit();
    echo "Transaction committed.<br>";
    
    $enableDownloads = ($paymentMethod === 'stripe') ? 1 : 0;
    $stmt = $db->prepare("UPDATE order_items SET downloads_enabled = ? WHERE order_id = ?");
    if ($stmt) { $stmt->bind_param("ii", $enableDownloads, $orderId); $stmt->execute(); }

    echo "<h3>SUCCESS!</h3>";
    
    if ($paymentMethod === 'bank_transfer') {
        $url = '/pages/payment_instructions.php?order=' . $orderNumber;
        echo "Payment method is Bank Transfer.<br>";
        echo "<b><a href='$url'>CLICK HERE TO CONTINUE TO PAYMENT INSTRUCTIONS</a></b>";
        // redirect($url); // Disabled for debug
        
    } elseif ($paymentMethod === 'stripe') {
        echo "Payment method is Stripe.<br>";
        if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
            die("Stripe SDK missing.");
        }
        require_once __DIR__ . '/../vendor/autoload.php';
        
        if (!defined('STRIPE_SECRET_KEY') || empty(STRIPE_SECRET_KEY)) {
            die("Stripe key missing.");
        }
        
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        
        echo "Creating Stripe session...<br>";
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'ron',
                    'product_data' => [
                        'name' => 'Comandă #' . $orderNumber,
                        'description' => count($cartItems) . ' produs(e) - Brodero'
                    ],
                    'unit_amount' => round($totalAmount * 100),
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
        
        $stmt = $db->prepare("UPDATE orders SET stripe_session_id = ? WHERE id = ?");
        $stmt->bind_param("si", $session->id, $orderId);
        $stmt->execute();
        
        echo "Stripe session created.<br>";
        echo "<b><a href='" . $session->url . "'>CLICK HERE TO PAY WITH CARD</a></b>";
    }
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    echo "<h2 style='color:red'>EXCEPTION CAUGHT</h2>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Throwable $e) {
    echo "<h2 style='color:red'>FATAL ERROR CAUGHT</h2>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

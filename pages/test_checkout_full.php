<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>TEST CHECKOUT STEP BY STEP</h3>";

try {
    echo "1. Loading config...<br>";
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
    echo "✓ Config loaded<br><br>";
    
    echo "2. Simulating POST data...<br>";
    $_POST['customer_name'] = 'Test User';
    $_POST['customer_email'] = 'test@test.com';
    $_POST['customer_phone'] = '0712345678';
    $_POST['shipping_address'] = 'Test Address';
    $_POST['payment_method'] = 'bank_transfer';
    $_POST['notes'] = '';
    $_POST['csrf_token'] = $_SESSION['csrf_token'] ?? 'test';
    $_SESSION['csrf_token'] = 'test';
    echo "✓ POST data set<br><br>";
    
    echo "3. Getting DB and user info...<br>";
    $db = getDB();
    $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
    if (!isset($_SESSION['session_id'])) {
        $_SESSION['session_id'] = session_id();
    }
    $sessionId = $_SESSION['session_id'];
    echo "✓ User ID: " . ($userId ?? 'guest') . "<br>";
    echo "✓ Session ID: $sessionId<br><br>";
    
    echo "4. Getting cart items...<br>";
    if ($userId) {
        $stmt = $db->prepare("SELECT c.id as cart_id, c.quantity, p.id, p.name, p.price, p.sale_price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $db->prepare("SELECT c.id as cart_id, c.quantity, p.id, p.name, p.price, p.sale_price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.session_id = ?");
        $stmt->bind_param("s", $sessionId);
    }
    $stmt->execute();
    $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo "✓ Cart items found: " . count($cartItems) . "<br><br>";
    
    if (empty($cartItems)) {
        die("❌ CART IS EMPTY - Adaugă produse în coș mai întâi!");
    }
    
    echo "5. Calculating total...<br>";
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
        $subtotal += $price;
        echo "  - {$item['name']}: {$price} LEI<br>";
    }
    $totalAmount = $subtotal;
    echo "✓ Total: $totalAmount LEI<br><br>";
    
    echo "6. Creating order...<br>";
    $orderNumber = 'BRD' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    echo "✓ Order number: $orderNumber<br>";
    
    $db->begin_transaction();
    echo "✓ Transaction started<br><br>";
    
    echo "7. Inserting order into DB...<br>";
    $customerName = $_POST['customer_name'];
    $customerEmail = $_POST['customer_email'];
    $customerPhone = $_POST['customer_phone'];
    $shippingAddress = $_POST['shipping_address'];
    $paymentMethod = $_POST['payment_method'];
    $notes = $_POST['notes'];
    
    if ($userId) {
        $stmt = $db->prepare("INSERT INTO orders (user_id, order_number, total_amount, status, payment_status, payment_method, notes, created_at) VALUES (?, ?, ?, 'pending', 'unpaid', ?, ?, NOW())");
        $stmt->bind_param("isdss", $userId, $orderNumber, $totalAmount, $paymentMethod, $notes);
    } else {
        $stmt = $db->prepare("INSERT INTO orders (order_number, total_amount, status, payment_status, payment_method, notes, created_at) VALUES (?, ?, 'pending', 'unpaid', ?, ?, NOW())");
        $stmt->bind_param("sdss", $orderNumber, $totalAmount, $paymentMethod, $notes);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("DB Insert failed: " . $stmt->error);
    }
    $orderId = $db->insert_id;
    echo "✓ Order inserted with ID: $orderId<br><br>";
    
    echo "8. Inserting order items...<br>";
    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, 1)");
    foreach ($cartItems as $item) {
        $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
        $stmt->bind_param("iisd", $orderId, $item['id'], $item['name'], $price);
        if (!$stmt->execute()) {
            throw new Exception("Order item insert failed: " . $stmt->error);
        }
        echo "  ✓ Added: {$item['name']}<br>";
    }
    echo "<br>";
    
    echo "9. Committing transaction...<br>";
    $db->commit();
    echo "✓ Transaction committed<br><br>";
    
    echo "<strong style='color:green;'>✓✓✓ TOT A FUNCȚIONAT! Checkout-ul e OK!</strong><br>";
    echo "Problema poate fi la:<br>";
    echo "- Stripe integration (dacă plata e cu card)<br>";
    echo "- Redirect-uri<br>";
    echo "- Clearing cart<br>";
    
} catch (Throwable $e) {
    if (isset($db)) $db->rollback();
    echo "<br><strong style='color:red;'>❌ EROARE GĂSITĂ:</strong><br>";
    echo "Mesaj: " . $e->getMessage() . "<br>";
    echo "Fișier: " . $e->getFile() . "<br>";
    echo "Linia: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

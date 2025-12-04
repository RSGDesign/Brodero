<?php
/**
 * Process Payment with Stripe
 * Procesează plată cu Stripe API
 */

header('Content-Type: application/json');
ob_start();

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';

    // Verificare metoda POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metoda nu este permisă');
    }

    // Obțineți input-ul JSON
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Date invalide');
    }

    // Verificare CSRF token
    if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Token de securitate invalid');
    }

    $paymentMethodId = $input['payment_method_id'] ?? null;
    $cartId = $input['cart_id'] ?? null;

    if (!$paymentMethodId || !$cartId) {
        throw new Exception('Lipsesc parametri necesari');
    }

    // Verificare SDK Stripe
    if (!class_exists('Stripe\Stripe')) {
        throw new Exception('Stripe SDK nu este instalat. Rulează: composer require stripe/stripe-php');
    }

    // Inițializare Stripe
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    // Obțineți utilizatorul și coșul
    $db = getDB();
    $userId = $_SESSION['user_id'] ?? null;
    $sessionId = $_SESSION['session_id'] ?? null;

    if (!$userId && !$sessionId) {
        throw new Exception('Utilizator neautentificat');
    }

    // Obțineți comenzile din coș
    if ($userId) {
        $stmt = $db->prepare("
            SELECT c.id as cart_id, c.quantity, c.product_id, p.name, p.price, p.sale_price
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $db->prepare("
            SELECT c.id as cart_id, c.quantity, c.product_id, p.name, p.price, p.sale_price
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.session_id = ?
        ");
        $stmt->bind_param("s", $sessionId);
    }

    $stmt->execute();
    $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($cartItems)) {
        throw new Exception('Coșul este gol');
    }

    // Calcul total
    $total = 0;
    foreach ($cartItems as $item) {
        $price = $item['sale_price'] ?? $item['price'];
        $total += $price * 100; // Stripe folosește cenți
    }

    // Calcul discount (dacă există cupon aplicat)
    $discount = 0;
    if (isset($_SESSION['applied_coupon'])) {
        $appliedCoupon = $_SESSION['applied_coupon'];
        $couponCode = is_array($appliedCoupon) ? ($appliedCoupon['code'] ?? null) : $appliedCoupon;

        if ($couponCode) {
            $couponStmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
            $couponStmt->bind_param("s", $couponCode);
            $couponStmt->execute();
            $coupon = $couponStmt->get_result()->fetch_assoc();
            $couponStmt->close();

            if ($coupon) {
                if ($coupon['discount_type'] === 'percent') {
                    $discount = round(($total * $coupon['discount_value']) / 100);
                } else {
                    $discount = $coupon['discount_value'] * 100;
                }
                $total -= $discount;
            }
        }
    }

    // Creare Payment Intent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => max(50, $total), // Stripe necesită minim 50 cenți
        'currency' => 'eur',
        'payment_method' => $paymentMethodId,
        'confirmation_method' => 'automatic',
        'confirm' => true,
        'return_url' => SITE_URL . '/pages/checkout.php?payment_status=success',
        'metadata' => [
            'order_id' => $cartId,
            'customer_email' => $_SESSION['user_email'] ?? 'guest@example.com'
        ]
    ]);

    // Verificare status plată
    if ($paymentIntent->status === 'succeeded') {
        // Creare comandă în baza de date
        $orderData = createOrderFromCart($userId, $sessionId, $paymentIntent->id, 'stripe');
        ob_end_clean();

        echo json_encode([
            'success' => true,
            'message' => 'Plată procesată cu succes!',
            'order_id' => $orderData['id'],
            'redirect_url' => SITE_URL . '/pages/order_confirmation.php?id=' . $orderData['id']
        ]);
        exit;

    } elseif ($paymentIntent->status === 'requires_action') {
        // 3D Secure sau alte verificări necesare
        ob_end_clean();
        echo json_encode([
            'requires_action' => true,
            'client_secret' => $paymentIntent->client_secret
        ]);
        exit;

    } else {
        throw new Exception('Plată eșuată: ' . $paymentIntent->status);
    }

} catch (\Stripe\Exception\ApiErrorException $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Eroare Stripe: ' . $e->getMessage()
    ]);
    exit;

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

/**
 * Helper function - Creare comandă din coș
 */
function createOrderFromCart($userId, $sessionId, $stripePaymentId, $paymentMethod) {
    $db = getDB();

    // Obțineți detalii coș
    if ($userId) {
        $stmt = $db->prepare("
            SELECT c.id as cart_id, c.quantity, c.product_id, p.name, p.price, p.sale_price
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $db->prepare("
            SELECT c.id as cart_id, c.quantity, c.product_id, p.name, p.price, p.sale_price
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.session_id = ?
        ");
        $stmt->bind_param("s", $sessionId);
    }

    $stmt->execute();
    $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Calcul total
    $totalAmount = 0;
    foreach ($cartItems as $item) {
        $price = $item['sale_price'] ?? $item['price'];
        $totalAmount += $price;
    }

    // Creare comandă
    $orderNumber = 'ORD-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
    $status = 'completed';
    $paymentStatus = 'paid';

    $stmt = $db->prepare("
        INSERT INTO orders (user_id, order_number, total_amount, status, payment_status, payment_method, stripe_payment_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param("isdsss", $userId, $orderNumber, $totalAmount, $status, $paymentStatus, $paymentMethod, $stripePaymentId);

    if (!$stmt->execute()) {
        throw new Exception('Eroare la crearea comenzii');
    }

    $orderId = $stmt->insert_id;
    $stmt->close();

    // Adăugare iteme în comandă
    foreach ($cartItems as $item) {
        $price = $item['sale_price'] ?? $item['price'];
        $stmt = $db->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("idid", $orderId, $item['product_id'], $item['quantity'], $price);
        $stmt->execute();
        $stmt->close();
    }

    // Curățare coș
    if ($userId) {
        $db->query("DELETE FROM cart WHERE user_id = $userId");
    } else {
        $db->query("DELETE FROM cart WHERE session_id = '$sessionId'");
    }

    // Curățare coupon aplicat
    unset($_SESSION['applied_coupon']);

    return [
        'id' => $orderId,
        'order_number' => $orderNumber,
        'total_amount' => $totalAmount
    ];
}
?>

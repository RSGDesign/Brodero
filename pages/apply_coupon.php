<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/cart.php');
}

// Verificare CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    setMessage("Token invalid. Încearcă din nou.", "danger");
    redirect('/pages/cart.php');
}

$couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));

if (empty($couponCode)) {
    setMessage("Introdu un cod de cupon.", "warning");
    redirect('/pages/cart.php');
}

$db = getDB();

// Preia cuponul din baza de date
$stmt = $db->prepare("SELECT * FROM coupons WHERE code = ?");
$stmt->bind_param("s", $couponCode);
$stmt->execute();
$coupon = $stmt->get_result()->fetch_assoc();

if (!$coupon) {
    setMessage("Cupon invalid.", "danger");
    redirect('/pages/cart.php');
}

// Verificări valabilitate cupon
if (!$coupon['is_active']) {
    setMessage("Acest cupon nu mai este activ.", "danger");
    redirect('/pages/cart.php');
}

if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
    setMessage("Acest cupon a expirat.", "danger");
    redirect('/pages/cart.php');
}

if ($coupon['max_uses'] && $coupon['used_count'] >= $coupon['max_uses']) {
    setMessage("Acest cupon a fost utilizat complet.", "danger");
    redirect('/pages/cart.php');
}

// Calculează subtotalul coșului
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("
        SELECT SUM(
            CASE 
                WHEN p.sale_price > 0 THEN p.sale_price * c.quantity
                ELSE p.price * c.quantity
            END
        ) as subtotal
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
} else {
    $sessionId = $_SESSION['session_id'] ?? '';
    $stmt = $db->prepare("
        SELECT SUM(
            CASE 
                WHEN p.sale_price > 0 THEN p.sale_price * c.quantity
                ELSE p.price * c.quantity
            END
        ) as subtotal
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.session_id = ?
    ");
    $stmt->bind_param("s", $sessionId);
}

$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$subtotal = $result['subtotal'] ?? 0;

// Verifică suma minimă
if ($coupon['min_order_amount'] && $subtotal < $coupon['min_order_amount']) {
    setMessage("Acest cupon necesită o comandă minimă de {$coupon['min_order_amount']} LEI. Subtotalul curent: {$subtotal} LEI.", "warning");
    redirect('/pages/cart.php');
}

// Salvează cuponul în sesiune
$_SESSION['applied_coupon'] = [
    'code' => $coupon['code'],
    'discount_type' => $coupon['discount_type'],
    'discount_value' => $coupon['discount_value']
];

setMessage("Cupon aplicat cu succes! Reducere: " . ($coupon['discount_type'] === 'percent' ? $coupon['discount_value'] . '%' : $coupon['discount_value'] . ' LEI'), "success");
redirect('/pages/cart.php');
?>

<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$cartId = (int)($_POST['cart_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($cartId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$db = getDB();

// Verifică dacă cart item există și aparține utilizatorului curent
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT c.id, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
    $stmt->bind_param("ii", $cartId, $_SESSION['user_id']);
} else {
    $sessionId = $_SESSION['session_id'] ?? '';
    $stmt = $db->prepare("SELECT c.id, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.session_id = ?");
    $stmt->bind_param("is", $cartId, $sessionId);
}

$stmt->execute();
$result = $stmt->get_result();
$cartItem = $result->fetch_assoc();

if (!$cartItem) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    exit;
}

// Verifică stocul disponibil
if ($quantity > $cartItem['stock']) {
    echo json_encode(['success' => false, 'message' => 'Cantitate indisponibilă în stoc']);
    exit;
}

// Update cantitatea
$stmt = $db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param("ii", $quantity, $cartId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Coș actualizat']);
} else {
    echo json_encode(['success' => false, 'message' => 'Eroare la actualizare']);
}
?>

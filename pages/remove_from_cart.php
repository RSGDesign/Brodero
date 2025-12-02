<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$cartId = (int)($_POST['cart_id'] ?? 0);

if ($cartId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart ID']);
    exit;
}

$db = getDB();

// Verifică dacă cart item există și aparține utilizatorului curent
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cartId, $_SESSION['user_id']);
} else {
    $sessionId = $_SESSION['session_id'] ?? '';
    $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND session_id = ?");
    $stmt->bind_param("is", $cartId, $sessionId);
}

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Produs eliminat din coș']);
} else {
    echo json_encode(['success' => false, 'message' => 'Eroare la ștergere']);
}
?>

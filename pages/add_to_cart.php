<?php
/**
 * Add to Cart Handler
 * Adaugă produse în coș cu validare stoc
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodă invalidă']);
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($productId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Date invalide']);
    exit;
}

$db = getDB();

// Verificare produs și stoc
$stmt = $db->prepare("SELECT id, name, price, sale_price, stock_status FROM products WHERE id = ? AND stock_status = 'in_stock'");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Produsul nu este disponibil']);
    exit;
}

// Generare session_id dacă nu există
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

$userId = isLoggedIn() ? $_SESSION['user_id'] : null;
$sessionId = $_SESSION['session_id'];

// Verificare dacă produsul există deja în coș
if ($userId) {
    $checkStmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $checkStmt->bind_param("ii", $userId, $productId);
} else {
    $checkStmt = $db->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?");
    $checkStmt->bind_param("si", $sessionId, $productId);
}

$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();

if ($existing) {
    // Actualizare cantitate
    $newQuantity = $existing['quantity'] + $quantity;
    $updateStmt = $db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("ii", $newQuantity, $existing['id']);
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Cantitatea a fost actualizată în coș',
            'cart_count' => getCartCount()
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Eroare la actualizare']);
    }
} else {
    // Adăugare produs nou
    if ($userId) {
        $insertStmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $insertStmt->bind_param("iii", $userId, $productId, $quantity);
    } else {
        $insertStmt = $db->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)");
        $insertStmt->bind_param("sii", $sessionId, $productId, $quantity);
    }
    
    if ($insertStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Produsul a fost adăugat în coș',
            'cart_count' => getCartCount()
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Eroare la adăugare']);
    }
}

function getCartCount() {
    $db = getDB();
    
    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
    } else {
        $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = ?");
        $stmt->bind_param("s", $_SESSION['session_id']);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['total'] ?? 0);
}
?>
<?php
/**
 * AJAX endpoint pentru actualizare status comandă
 */

error_reporting(0);
ini_set('display_errors', 0);

// Output buffering
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

ob_end_clean();
ob_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

function sendJSON($data) {
    ob_end_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificare admin
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    sendJSON(['success' => false, 'message' => 'Acces negat.']);
}

// Verificare metoda POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Metodă invalidă.']);
}

// Validare CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    sendJSON(['success' => false, 'message' => 'Token CSRF invalid.']);
}

// Validare date
if (empty($_POST['order_id']) || empty($_POST['status'])) {
    sendJSON(['success' => false, 'message' => 'Date incomplete.']);
}

$orderId = (int)$_POST['order_id'];
$newStatus = cleanInput($_POST['status']);

// Validare status
$validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
if (!in_array($newStatus, $validStatuses)) {
    sendJSON(['success' => false, 'message' => 'Status invalid.']);
}

$db = getDB();

// Actualizare status
$stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
if (!$stmt) {
    sendJSON(['success' => false, 'message' => 'Eroare la pregătire query.']);
}

$stmt->bind_param("si", $newStatus, $orderId);

if ($stmt->execute()) {
    sendJSON(['success' => true, 'message' => 'Status comandă actualizat cu succes!']);
} else {
    sendJSON(['success' => false, 'message' => 'Eroare la actualizare status.']);
}

$stmt->close();
?>

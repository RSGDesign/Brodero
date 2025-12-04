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
$newPaymentStatus = cleanInput($_POST['payment_status'] ?? '');

// Validare status comandă
$validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
if (!in_array($newStatus, $validStatuses)) {
    sendJSON(['success' => false, 'message' => 'Status comandă invalid.']);
}

// Validare status plată (opțional)
$validPaymentStatuses = ['unpaid', 'paid', 'refunded'];
if (!empty($newPaymentStatus) && !in_array($newPaymentStatus, $validPaymentStatuses)) {
    sendJSON(['success' => false, 'message' => 'Status plată invalid.']);
}

$db = getDB();

// Construieste query în funcție de ce se actualizează
if (!empty($newPaymentStatus)) {
    // Actualizare status comandă și plată
    $stmt = $db->prepare("UPDATE orders SET status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?");
    if (!$stmt) {
        sendJSON(['success' => false, 'message' => 'Eroare la pregătire query.']);
    }
    $stmt->bind_param("ssi", $newStatus, $newPaymentStatus, $orderId);
} else {
    // Actualizare doar status comandă
    $stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    if (!$stmt) {
        sendJSON(['success' => false, 'message' => 'Eroare la pregătire query.']);
    }
    $stmt->bind_param("si", $newStatus, $orderId);
}

if ($stmt->execute()) {
    sendJSON(['success' => true, 'message' => 'Status comandă actualizat cu succes!']);
} else {
    sendJSON(['success' => false, 'message' => 'Eroare la actualizare status.']);
}

$stmt->close();
?>

<?php
/**
 * Dezactivare cont utilizator
 * AJAX endpoint pentru ștergere cont
 */

error_reporting(0);
ini_set('display_errors', 0);

// Curăță buffer-ul existent
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

// Funcție pentru a trimite răspuns JSON
function sendJSON($data) {
    ob_end_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificare autentificare
if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Trebuie să fii autentificat.']);
}

// Verificare metodă POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Metodă invalidă.']);
}

// Verificare CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    sendJSON(['success' => false, 'message' => 'Token CSRF invalid.']);
}

$db = getDB();
$userId = $_SESSION['user_id'];
$password = $_POST['password'] ?? '';

// Validare parolă
if (empty($password)) {
    sendJSON(['success' => false, 'message' => 'Parola este obligatorie pentru dezactivare.']);
}

// Verifică parola
$stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    sendJSON(['success' => false, 'message' => 'Parola este incorectă.']);
}

// Dezactivare cont (nu ștergere)
$stmt = $db->prepare("UPDATE users SET deleted_account = 1, deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    // Deconectare utilizator
    session_destroy();
    
    sendJSON([
        'success' => true, 
        'message' => 'Contul a fost dezactivat cu succes.',
        'redirect' => SITE_URL
    ]);
} else {
    sendJSON(['success' => false, 'message' => 'Eroare la dezactivare. Încearcă din nou.']);
}

$stmt->close();
?>

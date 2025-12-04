<?php
/**
 * Dezactivare cont utilizator
 * AJAX endpoint cu verificare parolă
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Verificare autentificare
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Trebuie să fii autentificat.']);
    exit;
}

// Verificare metodă POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodă invalidă.']);
    exit;
}

// Verificare CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalid.']);
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];
$password = $_POST['password'] ?? '';

// Validare parolă
if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Parola este obligatorie pentru dezactivare.']);
    exit;
}

// Verifică parola
$stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Parola este incorectă.']);
    exit;
}

// Dezactivare cont (nu ștergere)
$stmt = $db->prepare("UPDATE users SET deleted_account = 1, deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    // Deconectare utilizator
    session_destroy();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Contul a fost dezactivat cu succes.',
        'redirect' => SITE_URL
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Eroare la dezactivare. Încearcă din nou.']);
}

$stmt->close();
?>

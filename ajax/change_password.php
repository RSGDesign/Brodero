<?php
/**
 * Schimbare parolă utilizator
 * AJAX endpoint cu validare hash bcrypt
 */

ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

ob_end_clean();

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

// Obține date POST
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validări
$errors = [];

if (empty($currentPassword)) {
    $errors[] = 'Parola curentă este obligatorie.';
}

if (empty($newPassword)) {
    $errors[] = 'Parola nouă este obligatorie.';
}

if (strlen($newPassword) < 8) {
    $errors[] = 'Parola nouă trebuie să aibă cel puțin 8 caractere.';
}

if (!preg_match('/[A-Z]/', $newPassword)) {
    $errors[] = 'Parola nouă trebuie să conțină cel puțin o literă mare.';
}

if (!preg_match('/[0-9]/', $newPassword)) {
    $errors[] = 'Parola nouă trebuie să conțină cel puțin o cifră.';
}

if ($newPassword !== $confirmPassword) {
    $errors[] = 'Parolele noi nu coincid.';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Verifică parola curentă
$stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($currentPassword, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Parola curentă este incorectă.']);
    exit;
}

// Hash parola nouă
$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

// Actualizare în baza de date
$stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param("si", $newPasswordHash, $userId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Parola a fost schimbată cu succes!'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Eroare la salvare. Încearcă din nou.'
    ]);
}

$stmt->close();
?>

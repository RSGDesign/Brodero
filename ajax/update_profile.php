<?php
/**
 * Actualizare date profil utilizator
 * AJAX endpoint pentru salvare date personale
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

// Validare și sanitizare date
$firstName = cleanInput($_POST['first_name'] ?? '');
$lastName = cleanInput($_POST['last_name'] ?? '');
$phone = cleanInput($_POST['phone'] ?? '');
$country = cleanInput($_POST['country'] ?? '');
$city = cleanInput($_POST['city'] ?? '');
$newsletter = isset($_POST['newsletter']) ? 1 : 0;
$notifications = isset($_POST['notifications']) ? 1 : 0;

// Validări
$errors = [];

if (empty($firstName)) {
    $errors[] = 'Prenumele este obligatoriu.';
}

if (empty($lastName)) {
    $errors[] = 'Numele este obligatoriu.';
}

if (!empty($phone) && !preg_match('/^[0-9\s\-\+\(\)]{10,20}$/', $phone)) {
    $errors[] = 'Număr de telefon invalid.';
}

if (!empty($errors)) {
    sendJSON(['success' => false, 'message' => implode(' ', $errors)]);
}

// Actualizare în baza de date
$stmt = $db->prepare("UPDATE users SET 
    first_name = ?, 
    last_name = ?, 
    phone = ?, 
    country = ?, 
    city = ?, 
    newsletter = ?, 
    notifications = ?,
    updated_at = NOW()
    WHERE id = ?");

$stmt->bind_param("sssssiis", $firstName, $lastName, $phone, $country, $city, $newsletter, $notifications, $userId);

if ($stmt->execute()) {
    // Actualizare sesiune
    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
    
    sendJSON([
        'success' => true, 
        'message' => 'Profilul a fost actualizat cu succes!'
    ]);
} else {
    sendJSON([
        'success' => false, 
        'message' => 'Eroare la salvare. Încearcă din nou.'
    ]);
}

$stmt->close();
?>

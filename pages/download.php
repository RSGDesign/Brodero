<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions_downloads.php';

if (!isLoggedIn()) {
    setMessage('Trebuie să fii autentificat pentru descărcare.', 'danger');
    redirect('/pages/login.php');
}

$fileId = isset($_GET['file']) ? (int)$_GET['file'] : 0;
$orderId = isset($_GET['order']) ? (int)$_GET['order'] : 0;
$token = $_GET['token'] ?? '';
$userId = $_SESSION['user_id'];

if (!$fileId || !$orderId) {
    setMessage('Parametri invalidi pentru descărcare.', 'danger');
    redirect('/');
}

if (!validateDownloadToken($token, $fileId, $orderId, $userId)) {
    setMessage('Token descărcare invalid sau expirat.', 'danger');
    redirect('/pages/cont.php');
}

if (!isFileAvailableToUser($fileId, $userId, $orderId)) {
    setMessage('Fișier indisponibil pentru descărcare.', 'danger');
    redirect('/pages/cont.php');
}

$db = getDB();
$stmt = $db->prepare("SELECT file_name, file_path, download_limit, download_count FROM product_files WHERE id = ?");
$stmt->bind_param("i", $fileId);
$stmt->execute();
$file = $stmt->get_result()->fetch_assoc();
if (!$file) {
    setMessage('Fișierul nu a fost găsit.', 'danger');
    redirect('/pages/cont.php');
}

// Limită de descărcări
if ((int)$file['download_limit'] > 0 && (int)$file['download_count'] >= (int)$file['download_limit']) {
    setMessage('Limita de descărcări a fost atinsă.', 'warning');
    redirect('/pages/cont.php');
}

// Incrementează și pornește descărcarea securizată
incrementDownloadCount($fileId);
$fullPath = __DIR__ . '/../' . ltrim($file['file_path'], '/');
if (!secureDownload($fullPath, $file['file_name'])) {
    setMessage('Eroare la descărcare.', 'danger');
    redirect('/pages/cont.php');
}
exit;

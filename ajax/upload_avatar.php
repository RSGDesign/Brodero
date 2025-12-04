<?php
/**
 * Upload și procesare avatar utilizator
 * AJAX endpoint cu crop și redimensionare
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

// Verificare fișier încărcat
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Eroare la încărcarea fișierului.']);
    exit;
}

$file = $_FILES['avatar'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Validare tip fișier
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Tip fișier invalid. Doar JPG și PNG sunt permise.']);
    exit;
}

// Validare mărime
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Fișierul este prea mare. Mărimea maximă este 5MB.']);
    exit;
}

// Creare director avatare dacă nu există
$avatarDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($avatarDir)) {
    mkdir($avatarDir, 0755, true);
}

// Generare nume unic fișier
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = 'avatar_' . $userId . '_' . time() . '.' . $extension;
$filePath = $avatarDir . $fileName;
$relativeFilePath = 'avatars/' . $fileName;

// Procesare imagine - redimensionare la 300x300
$imageType = exif_imagetype($file['tmp_name']);
$sourceImage = null;

switch ($imageType) {
    case IMAGETYPE_JPEG:
        $sourceImage = imagecreatefromjpeg($file['tmp_name']);
        break;
    case IMAGETYPE_PNG:
        $sourceImage = imagecreatefrompng($file['tmp_name']);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Format imagine nesuportat.']);
        exit;
}

if (!$sourceImage) {
    echo json_encode(['success' => false, 'message' => 'Eroare la procesarea imaginii.']);
    exit;
}

// Obține dimensiuni originale
$width = imagesx($sourceImage);
$height = imagesy($sourceImage);

// Calculare dimensiuni pentru crop pătrat
$size = min($width, $height);
$x = ($width - $size) / 2;
$y = ($height - $size) / 2;

// Creare imagine nouă 300x300
$targetImage = imagecreatetruecolor(300, 300);

// Păstrare transparență pentru PNG
if ($imageType === IMAGETYPE_PNG) {
    imagealphablending($targetImage, false);
    imagesavealpha($targetImage, true);
    $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
    imagefilledrectangle($targetImage, 0, 0, 300, 300, $transparent);
}

// Crop și redimensionare
imagecopyresampled($targetImage, $sourceImage, 0, 0, $x, $y, 300, 300, $size, $size);

// Salvare imagine
$saved = false;
if ($imageType === IMAGETYPE_JPEG) {
    $saved = imagejpeg($targetImage, $filePath, 90);
} else {
    $saved = imagepng($targetImage, $filePath, 9);
}

// Eliberare memorie
imagedestroy($sourceImage);
imagedestroy($targetImage);

if (!$saved) {
    echo json_encode(['success' => false, 'message' => 'Eroare la salvarea fișierului.']);
    exit;
}

// Ștergere avatar vechi dacă există
$stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$oldAvatar = $stmt->get_result()->fetch_assoc()['avatar'] ?? null;
$stmt->close();

if ($oldAvatar && file_exists($avatarDir . basename($oldAvatar))) {
    unlink($avatarDir . basename($oldAvatar));
}

// Actualizare în baza de date
$stmt = $db->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param("si", $relativeFilePath, $userId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Avatar actualizat cu succes!',
        'avatar_url' => SITE_URL . '/uploads/' . $relativeFilePath
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Eroare la salvare în baza de date.']);
}

$stmt->close();
?>

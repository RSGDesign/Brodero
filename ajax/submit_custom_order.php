<?php
/**
 * AJAX Handler - Submit Custom Order
 * Procesează formular comenzi personalizate + upload fișiere
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// ============================================================================
// 1. VERIFICĂRI INIȚIALE
// ============================================================================

// Verificare metodă POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodă invalidă']);
    exit;
}

// Verificare CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalid']);
    exit;
}

// Anti-spam honeypot
if (!empty($_POST['website'])) {
    echo json_encode(['success' => false, 'message' => 'Spam detectat']);
    exit;
}

// ============================================================================
// 2. VALIDARE DATE FORMULAR
// ============================================================================

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$description = trim($_POST['description'] ?? '');
$budget = !empty($_POST['budget']) ? floatval($_POST['budget']) : null;

// Validări
$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Numele este obligatoriu (minim 2 caractere)';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email invalid';
}

if (empty($description) || strlen($description) < 20) {
    $errors[] = 'Descrierea este obligatorie (minim 20 caractere)';
}

if (!empty($budget) && ($budget < 0 || $budget > 999999)) {
    $errors[] = 'Buget invalid';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Erori de validare: ' . implode(', ', $errors)
    ]);
    exit;
}

// ============================================================================
// 3. PROCESARE UPLOAD FIȘIER
// ============================================================================

$filePath = null;
$fileOriginalName = null;

if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['file'];
    
    // Verificare erori upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'message' => 'Eroare la încărcarea fișierului'
        ]);
        exit;
    }
    
    // Verificare dimensiune (max 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        echo json_encode([
            'success' => false,
            'message' => 'Fișierul este prea mare (max 10MB)'
        ]);
        exit;
    }
    
    // Verificare tip fișier
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'zip', 'rar'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode([
            'success' => false,
            'message' => 'Tip fișier invalid. Doar JPG, PNG, PDF, ZIP, RAR sunt permise'
        ]);
        exit;
    }
    
    // Verificare MIME type (securitate adițională)
    $allowedMimes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'application/pdf',
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/octet-stream'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimes)) {
        echo json_encode([
            'success' => false,
            'message' => 'Tip fișier invalid detectat'
        ]);
        exit;
    }
    
    // Creează director dacă nu există
    $uploadDir = __DIR__ . '/../uploads/custom-orders/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Protejare director cu .htaccess
    $htaccessPath = $uploadDir . '.htaccess';
    if (!file_exists($htaccessPath)) {
        file_put_contents($htaccessPath, "Options -Indexes\n<Files *>\n    Order Allow,Deny\n    Allow from all\n</Files>\n<FilesMatch \"\\.(php|php3|php4|php5|phtml|exe|sh)$\">\n    Order Allow,Deny\n    Deny from all\n</FilesMatch>");
    }
    
    // Generează nume unic pentru fișier
    $uniqueName = uniqid('order_', true) . '_' . time() . '.' . $fileExtension;
    $filePath = 'uploads/custom-orders/' . $uniqueName;
    $fileFullPath = __DIR__ . '/../' . $filePath;
    
    // Mută fișierul
    if (!move_uploaded_file($file['tmp_name'], $fileFullPath)) {
        echo json_encode([
            'success' => false,
            'message' => 'Eroare la salvarea fișierului'
        ]);
        exit;
    }
    
    $fileOriginalName = $file['name'];
}

// ============================================================================
// 4. SALVARE ÎN BAZA DE DATE
// ============================================================================

try {
    $db = getPDO();
    
    // IP address pentru tracking
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $stmt = $db->prepare("
        INSERT INTO custom_orders 
        (name, email, phone, description, budget, file_path, file_original_name, ip_address, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new')
    ");
    
    $stmt->execute([
        $name,
        $email,
        $phone ?: null,
        $description,
        $budget,
        $filePath,
        $fileOriginalName,
        $ipAddress
    ]);
    
    $orderId = $db->lastInsertId();
    
    // ========================================================================
    // 5. NOTIFICARE EMAIL (OPȚIONAL)
    // ========================================================================
    
    // Poți adăuga aici trimitere email către admin
    // require_once __DIR__ . '/../includes/email.php';
    // sendCustomOrderNotification($orderId, $name, $email);
    
    // ========================================================================
    // 6. SUCCESS RESPONSE
    // ========================================================================
    
    // Salvează mesaj de succes în sesiune pentru afișare după redirect
    $_SESSION['custom_order_success'] = 'Cererea ta a fost trimisă cu succes! Vom reveni în cel mai scurt timp.';
    
    echo json_encode([
        'success' => true,
        'message' => 'Cererea a fost trimisă cu succes!',
        'order_id' => $orderId
    ]);
    
} catch (PDOException $e) {
    // Log error
    error_log("Custom Order Error: " . $e->getMessage());
    
    // Șterge fișierul dacă upload a reușit dar DB a eșuat
    if ($filePath && file_exists(__DIR__ . '/../' . $filePath)) {
        unlink(__DIR__ . '/../' . $filePath);
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Eroare la salvarea cererii. Vă rugăm încercați din nou.'
    ]);
}

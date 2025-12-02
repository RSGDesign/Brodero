<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function getProductFiles($productId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM product_files WHERE product_id = ? AND status = 'active' ORDER BY uploaded_at DESC");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getUserDownloadableFiles($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT oi.order_id, oi.product_id, oi.product_name, oi.downloads_enabled, o.payment_status,
                                 pf.id as file_id, pf.file_name, pf.file_path, pf.file_size, pf.download_limit, pf.download_count
                          FROM orders o
                          JOIN order_items oi ON o.id = oi.order_id
                          JOIN product_files pf ON pf.product_id = oi.product_id
                          WHERE o.user_id = ? AND pf.status = 'active'
                          ORDER BY o.created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function incrementDownloadCount($fileId) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE product_files SET download_count = download_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $fileId);
    return $stmt->execute();
}

function isFileAvailableToUser($fileId, $userId, $orderId) {
    $db = getDB();
    // Eliminat condiția strictă payment_status = 'paid' pentru a permite descărcarea dacă downloads_enabled = 1
    $stmt = $db->prepare("SELECT pf.download_limit, pf.download_count
                          FROM product_files pf
                          JOIN order_items oi ON oi.product_id = pf.product_id AND oi.order_id = ?
                          JOIN orders o ON o.id = oi.order_id
                          WHERE pf.id = ? AND o.user_id = ? AND pf.status = 'active' AND oi.downloads_enabled = 1");
    $stmt->bind_param("iii", $orderId, $fileId, $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) return false;
    if ((int)$row['download_limit'] === 0) return true;
    return (int)$row['download_count'] < (int)$row['download_limit'];
}

function secureDownload($filePath, $downloadName) {
    if (!file_exists($filePath)) {
        return false;
    }
    $fsize = filesize($filePath);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $fsize);
    $fp = fopen($filePath, 'rb');
    fpassthru($fp);
    fclose($fp);
    return true;
}

function generateDownloadToken($fileId, $orderId, $userId) {
    $token = bin2hex(random_bytes(16));
    if (!isset($_SESSION['download_tokens'])) {
        $_SESSION['download_tokens'] = [];
    }
    
    // Cheie unică pentru fiecare combinație fișier-comandă
    $key = "f{$fileId}_o{$orderId}";
    
    $_SESSION['download_tokens'][$key] = [
        'token' => $token,
        'file_id' => $fileId,
        'order_id' => $orderId,
        'user_id' => $userId,
        'expires' => time() + 3600 // Valabil 1 oră
    ];
    return $token;
}

function validateDownloadToken($token, $fileId, $orderId, $userId) {
    $key = "f{$fileId}_o{$orderId}";
    $t = $_SESSION['download_tokens'][$key] ?? null;
    
    if (!$t) return false;
    if ($t['token'] !== $token) return false;
    if ($t['file_id'] != $fileId || $t['order_id'] != $orderId || $t['user_id'] != $userId) return false;
    if ($t['expires'] < time()) return false;
    return true;
}

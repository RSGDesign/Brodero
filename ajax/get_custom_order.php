<?php
/**
 * AJAX Handler - Get Custom Order Details
 * Returnează detaliile unei comenzi personalizate pentru modal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Verificare admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acces interzis']);
    exit;
}

$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID invalid']);
    exit;
}

try {
    $db = getPDO();
    
    $stmt = $db->prepare("SELECT * FROM custom_orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        // Adaugă URL complet pentru fișier
        if ($order['file_path']) {
            $order['file_path'] = SITE_URL . '/' . $order['file_path'];
        }
        
        echo json_encode([
            'success' => true,
            'order' => $order
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Comanda nu a fost găsită']);
    }
    
} catch (PDOException $e) {
    error_log("Get Custom Order Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Eroare la încărcarea datelor']);
}

<?php
/**
 * Script de Test - Verificare Status Descărcări
 * 
 * Verifică statusul descărcărilor pentru o comandă specifică
 * Util pentru debugging
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions_orders.php';

// Verificare acces admin
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
    }
}

if (!isAdmin()) {
    die('Acces interzis.');
}

$db = getDB();

// Obține ID comandă din URL
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;

if ($orderId) {
    // Obține detalii comandă
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        die('Comanda nu există.');
    }
    
    // Obține items
    $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Obține status descărcări
    $downloadStatus = getOrderDownloadStatus($orderId);
    
    header('Content-Type: application/json');
    echo json_encode([
        'order_id' => $orderId,
        'order_number' => $order['order_number'],
        'payment_status' => $order['payment_status'],
        'order_status' => $order['status'],
        'download_status' => $downloadStatus,
        'items' => array_map(function($item) {
            return [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'] ?? 'N/A',
                'downloads_enabled' => (int)$item['downloads_enabled']
            ];
        }, $items),
        'diagnosis' => [
            'is_paid' => $order['payment_status'] === 'paid',
            'downloads_enabled' => $downloadStatus['downloads_enabled'],
            'should_enable' => $order['payment_status'] === 'paid' && !$downloadStatus['downloads_enabled'],
            'recommendation' => ($order['payment_status'] === 'paid' && !$downloadStatus['downloads_enabled']) 
                ? 'Rulează sync_downloads.php sau apelează enableOrderDownloads(' . $orderId . ')'
                : 'Statusul este corect.'
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

// Afișează toate comenzile cu probleme
$query = "SELECT o.id, o.order_number, o.payment_status, o.status,
          COUNT(oi.id) as total_items,
          SUM(CASE WHEN oi.downloads_enabled = 1 THEN 1 ELSE 0 END) as enabled_items
          FROM orders o
          LEFT JOIN order_items oi ON oi.order_id = o.id
          WHERE o.payment_status = 'paid'
          GROUP BY o.id
          HAVING enabled_items < total_items
          ORDER BY o.created_at DESC
          LIMIT 50";

$result = $db->query($query);
$problematicOrders = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'total_problematic_orders' => count($problematicOrders),
    'orders' => $problematicOrders,
    'usage' => 'Apelează cu ?order_id=X pentru detalii specifice',
    'fix_url' => SITE_URL . '/admin/sync_downloads.php'
], JSON_PRETTY_PRINT);
?>

<?php
/**
 * Funcții pentru gestionarea comenzilor și fișierelor descărcabile
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Finalizează o comandă și activează descărcările pentru toate itemele
 * 
 * @param int $orderId ID-ul comenzii
 * @param string $paymentStatus Status plată ('paid', 'pending', 'failed')
 * @param string $orderStatus Status comandă ('completed', 'processing', 'cancelled')
 * @return bool Succes sau eșec
 */
function finalizeOrderAndDownloads($orderId, $paymentStatus = 'paid', $orderStatus = 'completed') {
    $db = getDB();
    
    try {
        $db->begin_transaction();
        
        // 1. Actualizează statusul comenzii
        $stmt = $db->prepare("UPDATE orders SET payment_status = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssi", $paymentStatus, $orderStatus, $orderId);
        
        if (!$stmt->execute()) {
            throw new Exception("Eroare la actualizarea comenzii");
        }
        $stmt->close();
        
        // 2. Activează descărcările pentru toate itemele comenzii (dacă plata este confirmată)
        if ($paymentStatus === 'paid') {
            $enableDownloads = 1;
            $stmt = $db->prepare("UPDATE order_items SET downloads_enabled = ? WHERE order_id = ?");
            $stmt->bind_param("ii", $enableDownloads, $orderId);
            
            if (!$stmt->execute()) {
                throw new Exception("Eroare la activarea descărcărilor");
            }
            $stmt->close();
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Eroare finalizeOrderAndDownloads: " . $e->getMessage());
        return false;
    }
}

/**
 * Marchează descărcările ca disponibile pentru o comandă
 * (folosită când comanda este deja creată, dar trebuie doar să activezi downloadurile)
 * 
 * @param int $orderId ID-ul comenzii
 * @return bool Succes sau eșec
 */
function enableOrderDownloads($orderId) {
    $db = getDB();
    $enableDownloads = 1;
    
    $stmt = $db->prepare("UPDATE order_items SET downloads_enabled = ? WHERE order_id = ?");
    $stmt->bind_param("ii", $enableDownloads, $orderId);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Verifică dacă o comandă este plătită și descărcările sunt activate
 * 
 * @param int $orderId ID-ul comenzii
 * @return array ['is_paid' => bool, 'downloads_enabled' => bool]
 */
function getOrderDownloadStatus($orderId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT payment_status FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$result) {
        return ['is_paid' => false, 'downloads_enabled' => false];
    }
    
    $isPaid = ($result['payment_status'] === 'paid');
    
    // Verifică dacă cel puțin un item are downloads_enabled
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ? AND downloads_enabled = 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $itemResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $downloadsEnabled = ($itemResult['count'] > 0);
    
    return [
        'is_paid' => $isPaid,
        'downloads_enabled' => $downloadsEnabled
    ];
}

/**
 * Actualizează statusul unei comenzi (pentru admin sau webhook)
 * 
 * @param int $orderId ID-ul comenzii
 * @param string $newStatus Noul status ('completed', 'processing', 'cancelled', etc.)
 * @param bool $autoEnableDownloads Activează automat descărcările dacă status = 'completed'
 * @return bool Succes sau eșec
 */
function updateOrderStatus($orderId, $newStatus, $autoEnableDownloads = true) {
    $db = getDB();
    
    try {
        $db->begin_transaction();
        
        // Actualizează statusul comenzii
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        
        if (!$stmt->execute()) {
            throw new Exception("Eroare la actualizarea statusului");
        }
        $stmt->close();
        
        // Dacă statusul este 'completed' și autoEnableDownloads = true, activează descărcările
        if ($autoEnableDownloads && $newStatus === 'completed') {
            enableOrderDownloads($orderId);
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Eroare updateOrderStatus: " . $e->getMessage());
        return false;
    }
}

/**
 * Procesează o comandă gratuită (0 RON) - activează imediat descărcările
 * 
 * @param int $orderId ID-ul comenzii
 * @return bool Succes sau eșec
 */
function processFreeOrder($orderId) {
    return finalizeOrderAndDownloads($orderId, 'paid', 'completed');
}

/**
 * Sincronizează statusul descărcărilor cu statusul plății
 * Rulează pentru toate comenzile plătite care nu au descărcări activate
 * 
 * @return int Numărul de comenzi actualizate
 */
function syncDownloadsWithPaymentStatus() {
    $db = getDB();
    
    // Găsește comenzi plătite cu descărcări dezactivate
    $query = "SELECT DISTINCT oi.order_id 
              FROM order_items oi
              JOIN orders o ON o.id = oi.order_id
              WHERE o.payment_status = 'paid' 
              AND (oi.downloads_enabled IS NULL OR oi.downloads_enabled = 0)";
    
    $result = $db->query($query);
    
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        if (enableOrderDownloads($row['order_id'])) {
            $count++;
        }
    }
    
    return $count;
}
?>

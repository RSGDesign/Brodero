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

/**
 * Trimite email de confirmare comandă cu instrucțiuni de plată
 * 
 * @param array $order Datele comenzii (order_number, customer_email, customer_name, total_amount, payment_method)
 * @return bool Succes sau eșec
 */
function sendOrderConfirmationEmail($order) {
    if (empty($order['customer_email'])) {
        error_log("sendOrderConfirmationEmail: Email lipsă pentru comanda #{$order['order_number']}");
        return false;
    }

    $to = $order['customer_email'];
    $subject = "Confirmare Comandă #" . $order['order_number'] . " - " . SITE_NAME;
    
    // Email Headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . SITE_NAME . " <" . SITE_EMAIL . ">" . "\r\n";
    $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
    
    // Email Body
    $message = getOrderEmailTemplate($order);
    
    // Send email
    try {
        $sent = mail($to, $subject, $message, $headers);
        
        if ($sent) {
            error_log("Email trimis cu succes pentru comanda #{$order['order_number']} către {$to}");
            return true;
        } else {
            error_log("Eroare la trimiterea emailului pentru comanda #{$order['order_number']}");
            return false;
        }
    } catch (Exception $e) {
        error_log("Excepție la trimiterea emailului: " . $e->getMessage());
        return false;
    }
}

/**
 * Generează template HTML pentru emailul de confirmare
 * 
 * @param array $order Datele comenzii
 * @return string HTML email template
 */
function getOrderEmailTemplate($order) {
    $orderNumber = htmlspecialchars($order['order_number']);
    $customerName = htmlspecialchars($order['customer_name'] ?? 'Client');
    $totalAmount = number_format($order['total_amount'], 2);
    $paymentMethod = $order['payment_method'];
    $orderUrl = SITE_URL . "/pages/comanda.php?id=" . $order['id'];
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmare Comandă</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { padding: 30px 20px; }
        .order-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea; }
        .order-info p { margin: 8px 0; }
        .order-info strong { color: #667eea; }
        .payment-instructions { background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .payment-instructions h3 { color: #856404; margin-top: 0; }
        .bank-details { background: white; padding: 15px; border-radius: 5px; margin-top: 15px; }
        .bank-details table { width: 100%; }
        .bank-details td { padding: 8px 0; }
        .bank-details td:first-child { font-weight: bold; width: 40%; color: #666; }
        .highlight { background: #e7f3ff; padding: 2px 6px; border-radius: 3px; color: #0066cc; font-family: monospace; }
        .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #666; }
        .steps { counter-reset: step-counter; list-style: none; padding: 0; }
        .steps li { counter-increment: step-counter; margin: 10px 0; padding-left: 35px; position: relative; }
        .steps li::before { content: counter(step-counter); position: absolute; left: 0; top: 0; background: #667eea; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✓ Comandă Confirmată</h1>
            <p style="margin: 10px 0 0 0;">Mulțumim pentru comanda ta!</p>
        </div>
        
        <div class="content">
            <p>Bună <strong>{$customerName}</strong>,</p>
            <p>Comanda ta a fost înregistrată cu succes în sistemul nostru.</p>
            
            <div class="order-info">
                <p><strong>Număr Comandă:</strong> #{$orderNumber}</p>
                <p><strong>Total de plată:</strong> <span style="font-size: 20px; color: #dc3545;">{$totalAmount} RON</span></p>
                <p><strong>Metodă de plată:</strong> {getPaymentMethodName($paymentMethod)}</p>
            </div>
HTML;

    // Adaugă instrucțiuni doar pentru transfer bancar
    if ($paymentMethod === 'bank_transfer') {
        $html .= <<<HTML
            
            <div class="payment-instructions">
                <h3>📋 Instrucțiuni de Plată - Transfer Bancar</h3>
                <p>Pentru a finaliza comanda, te rugăm să efectuezi transferul bancar folosind datele de mai jos:</p>
                
                <div class="bank-details">
                    <table>
                        <tr>
                            <td>Beneficiar:</td>
                            <td><strong>Brodero SRL</strong></td>
                        </tr>
                        <tr>
                            <td>Banca:</td>
                            <td>Banca Transilvania</td>
                        </tr>
                        <tr>
                            <td>IBAN:</td>
                            <td><span class="highlight">RO12 BTRL 0000 1234 5678 901</span></td>
                        </tr>
                        <tr>
                            <td>Sumă:</td>
                            <td><strong style="color: #dc3545; font-size: 18px;">{$totalAmount} RON</strong></td>
                        </tr>
                        <tr>
                            <td>Referință:</td>
                            <td><span class="highlight">Comanda #{$orderNumber}</span></td>
                        </tr>
                    </table>
                </div>
                
                <h4 style="margin-top: 20px; color: #856404;">Pași Următori:</h4>
                <ol class="steps">
                    <li>Efectuează transferul bancar cu datele de mai sus</li>
                    <li>Menționează <strong>obligatoriu</strong> "Comanda #{$orderNumber}" în detaliile transferului</li>
                    <li>Trimite-ne confirmarea la <a href="mailto:{SITE_EMAIL}">{SITE_EMAIL}</a></li>
                    <li>Vom verifica plata și activa descărcările în maxim 24 ore</li>
                </ol>
                
                <p style="margin-top: 15px; padding: 10px; background: #fff; border-left: 3px solid #dc3545;">
                    <strong>⚠️ Important:</strong> Fără referința corectă a comenzii, procesarea poate întârzia!
                </p>
            </div>
HTML;
    } else {
        $html .= <<<HTML
            <div style="background: #d4edda; border: 2px solid #28a745; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <p style="margin: 0; color: #155724;"><strong>✓ Plata procesată cu succes!</strong></p>
                <p style="margin: 10px 0 0 0; color: #155724;">Poți descărca fișierele accesând contul tău.</p>
            </div>
HTML;
    }

    $html .= <<<HTML
            
            <div style="text-align: center;">
                <a href="{$orderUrl}" class="btn">Vezi Detalii Comandă</a>
            </div>
            
            <p>Dacă ai întrebări, ne poți contacta oricând la <a href="mailto:{SITE_EMAIL}">{SITE_EMAIL}</a> sau la telefon {SITE_PHONE}.</p>
            <p>Cu stimă,<br><strong>Echipa {SITE_NAME}</strong></p>
        </div>
        
        <div class="footer">
            <p>Acest email a fost trimis automat. Te rugăm să nu răspunzi direct la acest mesaj.</p>
            <p>&copy; {date('Y')} {SITE_NAME}. Toate drepturile rezervate.</p>
            <p><a href="{SITE_URL}" style="color: #667eea;">Vizitează Website-ul</a></p>
        </div>
    </div>
</body>
</html>
HTML;

    return $html;
}

/**
 * Helper pentru numele metodei de plată (folosit în email)
 * @param string $method
 * @return string
 */
function getPaymentMethodName($method) {
    $methods = [
        'bank_transfer' => 'Transfer Bancar',
        'stripe' => 'Card Bancar (Stripe)',
        'card' => 'Card Bancar'
    ];
    return $methods[$method] ?? 'Necunoscută';
}
?>

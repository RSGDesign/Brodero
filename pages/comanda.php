<?php
/**
 * Vizualizare Detalii Comandă - User
 * Afișare completă informații comandă pentru utilizator
 */

$pageTitle = "Detalii Comandă";

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificare login
if (!isLoggedIn()) {
    setMessage("Trebuie să fii autentificat pentru a vizualiza comenzile.", "danger");
    redirect('/pages/login.php');
}

$db = getDB();

// Verificare ID comandă
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMessage("ID comandă invalid.", "danger");
    redirect('/pages/cont.php?section=comenzi');
}

$orderId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

// Obține detalii comandă (doar pentru utilizatorul autentificat)
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setMessage("Comanda nu a fost găsită sau nu ai acces la ea.", "danger");
    redirect('/pages/cont.php?section=comenzi');
}

$order = $result->fetch_assoc();
$stmt->close();

// Include header DUPĂ toate verificările
require_once __DIR__ . '/../includes/header.php';

// Obține produsele din comandă
$orderItems = $db->query("SELECT oi.*, p.name as product_name, p.image as product_image 
                          FROM order_items oi 
                          LEFT JOIN products p ON oi.product_id = p.id 
                          WHERE oi.order_id = $orderId")->fetch_all(MYSQLI_ASSOC);

// Helper functions pentru badge-uri
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning text-dark fs-6">În așteptare</span>',
        'processing' => '<span class="badge bg-info fs-6">În procesare</span>',
        'completed' => '<span class="badge bg-success fs-6">Finalizată</span>',
        'cancelled' => '<span class="badge bg-danger fs-6">Anulată</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary fs-6">Necunoscut</span>';
}

function getPaymentStatusBadge($status) {
    $badges = [
        'unpaid' => '<span class="badge bg-danger fs-6">Neplătit</span>',
        'paid' => '<span class="badge bg-success fs-6">Plătit</span>',
        'refunded' => '<span class="badge bg-warning text-dark fs-6">Rambursat</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary fs-6">Necunoscut</span>';
}
?>

<!-- Page Header -->
<section class="bg-primary text-white py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-receipt me-2"></i>Comanda #<?php echo htmlspecialchars($order['order_number']); ?>
                </h1>
                <p class="mb-0 opacity-75">
                    Plasată la: <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                </p>
            </div>
            <a href="<?php echo SITE_URL; ?>/pages/cont.php?section=comenzi" class="btn btn-outline-light">
                <i class="bi bi-arrow-left me-2"></i>Înapoi la Comenzi
            </a>
        </div>
    </div>
</section>

<!-- Order Details -->
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Order Info -->
            <div class="col-lg-8">
                <!-- Order Items -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 p-4">
                        <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2"></i>Produse Comandate</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produs</th>
                                        <th class="text-center">Cantitate</th>
                                        <th class="text-end">Preț</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['product_image'])): ?>
                                                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($item['product_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                     class="me-3 rounded"
                                                     style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle"><?php echo (int)$item['quantity']; ?></td>
                                        <td class="text-end align-middle"><?php echo number_format($item['price'], 2); ?> RON</td>
                                        <td class="text-end align-middle fw-bold">
                                            <?php echo number_format($item['price'] * $item['quantity'], 2); ?> RON
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">TOTAL:</td>
                                        <td class="text-end fw-bold text-primary fs-5">
                                            <?php echo number_format($order['total_amount'], 2); ?> RON
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Payment Instructions (if bank transfer and unpaid) -->
                <?php if ($order['payment_method'] === 'bank_transfer' && $order['payment_status'] === 'unpaid'): ?>
                <div class="card shadow-sm border-0 border-start border-warning border-4 mb-4">
                    <div class="card-header bg-warning bg-opacity-10 border-0 p-4">
                        <h5 class="fw-bold text-warning mb-0">
                            <i class="bi bi-bank me-2"></i>Instrucțiuni de Plată - Transfer Bancar
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Comanda ta este în așteptarea plății.</strong> 
                            După efectuarea transferului, te rugăm să ne trimiți confirmarea la 
                            <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-borderless mb-4">
                                <tbody>
                                    <tr>
                                        <td class="fw-bold text-muted" style="width: 180px;">
                                            <i class="bi bi-building me-2"></i>Beneficiar:
                                        </td>
                                        <td class="fw-bold">Brodero SRL</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="bi bi-bank me-2"></i>Banca:
                                        </td>
                                        <td>Banca Transilvania</td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td class="fw-bold text-muted">
                                            <i class="bi bi-credit-card-2-back me-2"></i>IBAN:
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <code id="iban-code" class="fs-6 me-2">RO12 BTRL 0000 1234 5678 901</code>
                                                <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('iban-code', this)">
                                                    <i class="bi bi-clipboard"></i> Copiază
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="bi bi-cash-coin me-2"></i>Sumă de plată:
                                        </td>
                                        <td>
                                            <span class="badge bg-danger fs-6">
                                                <?php echo number_format($order['total_amount'], 2); ?> RON
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="bi bi-currency-exchange me-2"></i>Moneda:
                                        </td>
                                        <td><strong>RON (Lei Românești)</strong></td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td class="fw-bold text-muted">
                                            <i class="bi bi-tag me-2"></i>Referință/Detalii:
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <code id="ref-code" class="fs-6 me-2">Comanda #<?php echo htmlspecialchars($order['order_number']); ?></code>
                                                <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('ref-code', this)">
                                                    <i class="bi bi-clipboard"></i> Copiază
                                                </button>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                ⚠️ Foarte important să incluzi acest număr în detalii transfer!
                                            </small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="border-top pt-3">
                            <h6 class="fw-bold mb-2"><i class="bi bi-check2-circle me-2"></i>Pași următori:</h6>
                            <ol class="mb-0 small">
                                <li>Efectuează transferul bancar folosind datele de mai sus</li>
                                <li>Menționează obligatoriu <strong>Comanda #<?php echo htmlspecialchars($order['order_number']); ?></strong> în detaliile transferului</li>
                                <li>Trimite-ne confirmarea plății la <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a></li>
                                <li>Vom verifica plata și activa descărcările în maxim 24 ore</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Download Section (if paid) -->
                <?php if ($order['payment_status'] === 'paid'): ?>
                <div class="card shadow-sm border-0 border-start border-success border-4 mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-success mb-3">
                            <i class="bi bi-check-circle-fill me-2"></i>Plata Confirmată
                        </h5>
                        <p class="mb-3">
                            <i class="bi bi-download me-2"></i>
                            Comanda ta a fost plătită! Poți descărca fișierele din secțiunea dedicată.
                        </p>
                        <a href="<?php echo SITE_URL; ?>/pages/cont.php?tab=fisiere" class="btn btn-success">
                            <i class="bi bi-folder-fill me-2"></i>Vezi Fișiere Descărcabile
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <!-- Status Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 p-4">
                        <h5 class="fw-bold mb-0"><i class="bi bi-info-circle me-2"></i>Status Comandă</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Status Comandă</small>
                            <?php echo getStatusBadge($order['status']); ?>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Status Plată</small>
                            <?php echo getPaymentStatusBadge($order['payment_status']); ?>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Metodă Plată</small>
                            <div class="fw-bold">
                                <?php 
                                $paymentMethods = [
                                    'card' => '<i class="bi bi-credit-card me-1"></i>Card',
                                    'bank_transfer' => '<i class="bi bi-bank me-1"></i>Transfer Bancar'
                                ];
                                echo $paymentMethods[$order['payment_method']] ?? 'Necunoscut';
                                ?>
                            </div>
                        </div>
                        <?php if ($order['payment_method'] === 'bank_transfer' && $order['payment_status'] === 'unpaid'): ?>
                        <div class="alert alert-warning mb-0">
                            <small>
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                În așteptarea confirmării plății
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Info Card -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 p-4">
                        <h5 class="fw-bold mb-0"><i class="bi bi-receipt-cutoff me-2"></i>Informații</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Număr Comandă</small>
                            <div class="fw-bold">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Data Plasării</small>
                            <div><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Total Comandă</small>
                            <div class="text-primary fw-bold fs-5">
                                <?php echo number_format($order['total_amount'], 2); ?> LEI
                            </div>
                        </div>
                        <?php if (!empty($order['notes'])): ?>
                        <div>
                            <small class="text-muted d-block mb-1">Note</small>
                            <div class="text-muted small"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Copy to clipboard function
function copyToClipboard(elementId, button) {
    const element = document.getElementById(elementId);
    const text = element.textContent;
    
    // Create temporary textarea
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        
        // Change button appearance
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check2"></i> Copiat!';
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-success');
        
        // Reset after 2 seconds
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-primary');
        }, 2000);
    } catch (err) {
        alert('Nu s-a putut copia textul. Selectează manual și copiază.');
    }
    
    document.body.removeChild(textarea);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

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
                                        <td class="text-end align-middle"><?php echo number_format($item['price'], 2); ?> LEI</td>
                                        <td class="text-end align-middle fw-bold">
                                            <?php echo number_format($item['price'] * $item['quantity'], 2); ?> LEI
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">TOTAL:</td>
                                        <td class="text-end fw-bold text-primary fs-5">
                                            <?php echo number_format($order['total_amount'], 2); ?> LEI
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Download Section (if paid) -->
                <?php if ($order['payment_status'] === 'paid'): ?>
                <div class="card shadow-sm border-0 border-start border-success border-4 mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-success mb-3">
                            <i class="bi bi-download me-2"></i>Descarcă Fișierele
                        </h5>
                        <p class="mb-3">Comanda ta a fost plătită! Poți descărca fișierele din secțiunea dedicată.</p>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

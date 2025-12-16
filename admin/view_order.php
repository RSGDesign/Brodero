<?php
/**
 * Vizualizare Detalii Comandă
 * Afișare completă informații comandă, client, status
 */

$pageTitle = "Detalii Comandă";

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificare acces admin
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
    }
}
if (!isAdmin()) {
    // CRITICAL: Save session before redirect
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

require_once __DIR__ . '/../includes/functions_orders.php';
$db = getDB();

// Verificare ID comandă
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMessage("ID comandă invalid.", "danger");
    redirect('/admin/admin_orders.php');
}

$orderId = (int)$_GET['id'];

// Procesare actualizare status ÎNAINTE de header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = cleanInput($_POST['status']);
    $paymentStatus = cleanInput($_POST['payment_status']);
    $notes = cleanInput($_POST['notes']);
    
    $stmt = $db->prepare("UPDATE orders SET status = ?, payment_status = ?, notes = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sssi", $newStatus, $paymentStatus, $notes, $orderId);
    
    if ($stmt->execute()) {
        // ✅ FOLOSEȘTE FUNCȚIA CENTRALIZATĂ pentru sincronizare descărcări
        if ($paymentStatus === 'paid') {
            enableOrderDownloads($orderId);
        }

        setMessage("Comanda a fost actualizată cu succes! " . ($paymentStatus === 'paid' ? "Descărcările au fost activate." : ""), "success");
        redirect('/admin/view_order.php?id=' . $orderId);
    } else {
        setMessage("Eroare la actualizarea comenzii.", "danger");
    }
    $stmt->close();
}

// Obține detalii comandă
$stmt = $db->prepare("SELECT o.*, u.username, u.email, u.first_name, u.last_name, u.phone 
                      FROM orders o 
                      LEFT JOIN users u ON o.user_id = u.id 
                      WHERE o.id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setMessage("Comanda nu a fost găsită.", "danger");
    redirect('/admin/admin_orders.php');
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
<section class="bg-dark text-white py-4">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-receipt me-2"></i>Detalii Comandă #<?php echo htmlspecialchars($order['order_number']); ?>
                </h1>
                <p class="mb-0 text-white-50">
                    Plasată la: <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                </p>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-outline-light me-2">
                    <i class="bi bi-printer me-2"></i>Printează
                </button>
                <a href="<?php echo SITE_URL; ?>/admin/admin_orders.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-2"></i>Înapoi la Comenzi
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Order Details -->
<section class="py-4">
    <div class="container-fluid">
        <div class="row g-4">
            <!-- Informații Comandă și Produse -->
            <div class="col-lg-8">
                <!-- Status și Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informații Comandă</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-1">Număr comandă:</p>
                                <h5 class="mb-0">#<?php echo htmlspecialchars($order['order_number']); ?></h5>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-1">Status comandă:</p>
                                <?php echo getStatusBadge($order['status']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-1">Status plată:</p>
                                <?php echo getPaymentStatusBadge($order['payment_status']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-1">Metodă plată:</p>
                                <p class="mb-0"><?php echo $order['payment_method'] ? htmlspecialchars($order['payment_method']) : 'Nespecificat'; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-1">Data plasării:</p>
                                <p class="mb-0"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-1">Ultima actualizare:</p>
                                <p class="mb-0"><?php echo $order['updated_at'] ? date('d.m.Y H:i', strtotime($order['updated_at'])) : '-'; ?></p>
                            </div>
                        </div>
                        
                        <?php if ($order['notes']): ?>
                            <hr>
                            <div class="alert alert-info mb-0">
                                <h6 class="alert-heading"><i class="bi bi-sticky me-2"></i>Notițe comandă:</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Produse din comandă -->
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-bag me-2"></i>Produse Comandate</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produs</th>
                                        <th class="text-center">Cantitate</th>
                                        <th class="text-end">Preț unitar</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $subtotal = 0;
                                    foreach ($orderItems as $item): 
                                        $itemSubtotal = $item['quantity'] * $item['price'];
                                        $subtotal += $itemSubtotal;
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($item['product_image']): ?>
                                                        <img src="<?php echo SITE_URL . '/uploads/' . $item['product_image']; ?>" 
                                                             alt="" 
                                                             title=""
                                                             class="img-thumbnail me-3"
                                                             style="width: 60px; height: 60px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                                             style="width: 60px; height: 60px;">
                                                            <i class="bi bi-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">ID Produs: #<?php echo $item['product_id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge bg-secondary"><?php echo $item['quantity']; ?></span>
                                            </td>
                                            <td class="text-end align-middle">
                                                <?php echo number_format($item['price'], 2); ?> LEI
                                            </td>
                                            <td class="text-end align-middle">
                                                <strong><?php echo number_format($itemSubtotal, 2); ?> LEI</strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end">
                                            <h5 class="mb-0 text-primary"><?php echo number_format($order['total_amount'], 2); ?> LEI</h5>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informații Client și Acțiuni -->
            <div class="col-lg-4">
                <!-- Informații Client -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-person me-2"></i>Informații Client</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($order['first_name'] && $order['last_name']): ?>
                            <p class="mb-2">
                                <i class="bi bi-person-circle me-2 text-muted"></i>
                                <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                            </p>
                        <?php endif; ?>
                        
                        <p class="mb-2">
                            <i class="bi bi-at me-2 text-muted"></i>
                            <?php echo htmlspecialchars($order['email']); ?>
                        </p>
                        
                        <?php if ($order['phone']): ?>
                            <p class="mb-2">
                                <i class="bi bi-telephone me-2 text-muted"></i>
                                <?php echo htmlspecialchars($order['phone']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <p class="mb-0">
                            <i class="bi bi-person-badge me-2 text-muted"></i>
                            <small class="text-muted">Username: <?php echo htmlspecialchars($order['username']); ?></small>
                        </p>
                        
                        <hr>
                        
                        <a href="<?php echo SITE_URL; ?>/admin/dashboard.php?section=utilizatori&user=<?php echo $order['user_id']; ?>" 
                           class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-eye me-2"></i>Vezi toate comenzile clientului
                        </a>
                    </div>
                </div>

                <!-- Actualizare Status -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Actualizare Comandă</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Status comandă:</label>
                                <select name="status" class="form-select" required>
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>În așteptare</option>
                                    <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>În procesare</option>
                                    <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Finalizată</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Anulată</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status plată:</label>
                                <select name="payment_status" class="form-select" required>
                                    <option value="unpaid" <?php echo $order['payment_status'] === 'unpaid' ? 'selected' : ''; ?>>Neplătit</option>
                                    <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Plătit</option>
                                    <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Rambursat</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notițe (opțional):</label>
                                <textarea name="notes" class="form-control" rows="3" 
                                          placeholder="Adaugă notițe despre comandă..."><?php echo htmlspecialchars($order['notes']); ?></textarea>
                            </div>

                            <button type="submit" name="update_status" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle me-2"></i>Salvează Modificările
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Acțiuni Rapide -->
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Acțiuni Rapide</h5>
                    </div>
                    <div class="card-body">
                        <a href="?delete=<?php echo $order['id']; ?>" 
                           class="btn btn-danger w-100 mb-2"
                           onclick="return confirm('Ești sigur că vrei să ștergi această comandă?\n\nComanda #<?php echo $order['order_number']; ?>\nClient: <?php echo $order['email']; ?>\nTotal: <?php echo number_format($order['total_amount'], 2); ?> LEI\n\nAceastă acțiune nu poate fi anulată!');">
                            <i class="bi bi-trash me-2"></i>Șterge Comandă
                        </a>
                        
                        <button onclick="window.print()" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-printer me-2"></i>Printează Comandă
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
@media print {
    .no-print, nav, footer, .btn, .card-header {
        display: none !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

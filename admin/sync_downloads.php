<?php
/**
 * Script de Sincronizare - Activează descărcările pentru comenzile plătite
 * 
 * Rulează acest script pentru a corecta comenzile vechi care au plata confirmată
 * dar descărcările nu sunt activate.
 * 
 * Acces: /admin/sync_downloads.php (doar pentru admin)
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

$pageTitle = "Sincronizare Descărcări";
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Procesare sincronizare
if (isset($_POST['sync_now'])) {
    $updatedCount = syncDownloadsWithPaymentStatus();
    
    echo '<div class="alert alert-success mt-3">';
    echo '<h4><i class="bi bi-check-circle"></i> Sincronizare Completă!</h4>';
    echo '<p>Au fost actualizate <strong>' . $updatedCount . '</strong> comenzi.</p>';
    echo '</div>';
}

// Obține comenzi plătite fără descărcări activate
$query = "SELECT DISTINCT o.id, o.order_number, o.created_at, o.total_amount, 
          u.email, u.first_name, u.last_name,
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id AND downloads_enabled = 0) as blocked_items
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.id
          WHERE o.payment_status = 'paid'
          AND EXISTS (
              SELECT 1 FROM order_items oi 
              WHERE oi.order_id = o.id 
              AND (oi.downloads_enabled IS NULL OR oi.downloads_enabled = 0)
          )
          ORDER BY o.created_at DESC";

$result = $db->query($query);
$affectedOrders = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">
                    <i class="bi bi-arrow-repeat"></i> Sincronizare Descărcări
                </h1>
                <a href="<?php echo SITE_URL; ?>/admin/admin_orders.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Înapoi
                </a>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle text-primary"></i> Despre acest script
                    </h5>
                    <p class="card-text">
                        Acest script identifică comenzile care au <strong>statusul plată = "paid"</strong> 
                        dar au <strong>descărcările dezactivate</strong> pentru produse digitale.
                    </p>
                    <p class="card-text">
                        După rulare, toate comenzile plătite vor avea descărcările activate automat.
                    </p>
                </div>
            </div>

            <?php if (count($affectedOrders) > 0): ?>
                <div class="alert alert-warning">
                    <h5><i class="bi bi-exclamation-triangle"></i> Atenție!</h5>
                    <p>Au fost găsite <strong><?php echo count($affectedOrders); ?></strong> comenzi plătite cu descărcări blocate.</p>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <strong>Comenzi Afectate</strong>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Număr Comandă</th>
                                        <th>Client</th>
                                        <th>Total</th>
                                        <th>Data</th>
                                        <th>Items Blocate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($affectedOrders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/admin/view_order.php?id=<?php echo $order['id']; ?>">
                                                <?php echo htmlspecialchars($order['order_number']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($order['first_name'] || $order['last_name']) {
                                                echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
                                            } else {
                                                echo htmlspecialchars($order['email'] ?? 'N/A');
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo number_format($order['total_amount'], 2); ?> RON</td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-danger">
                                                <?php echo $order['blocked_items']; ?> items
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <form method="POST">
                    <div class="d-grid">
                        <button type="submit" name="sync_now" class="btn btn-primary btn-lg">
                            <i class="bi bi-arrow-repeat"></i> Sincronizează Acum (<?php echo count($affectedOrders); ?> comenzi)
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <div class="alert alert-success">
                    <h5><i class="bi bi-check-circle"></i> Totul este sincronizat!</h5>
                    <p>Nu există comenzi plătite cu descărcări blocate.</p>
                </div>

                <a href="<?php echo SITE_URL; ?>/admin/admin_orders.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Înapoi la Comenzi
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

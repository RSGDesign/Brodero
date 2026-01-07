<?php
/**
 * Gestionare Comenzi Admin
 * Listare, vizualizare, actualizare status, ștergere comenzi
 */

$pageTitle = "Gestionare Comenzi";

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

$db = getDB();

// Inițializare CSRF token dacă nu există
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include order functions pentru activare descărcări
require_once __DIR__ . '/../includes/functions_orders.php';
require_once __DIR__ . '/../includes/functions_referral.php';

// Procesare actualizare status (form POST simplu)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    // Validare CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setMessage("Token CSRF invalid.", "danger");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    $orderId = (int)$_POST['order_id'];
    $newStatus = cleanInput($_POST['status'] ?? '');
    $newPaymentStatus = cleanInput($_POST['payment_status'] ?? '');
    
    $db = getDB();
    $updated = false;
    $downloadsActivated = false;
    
    // Actualizare status comandă
    if (!empty($newStatus)) {
        $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
        if (in_array($newStatus, $validStatuses)) {
            $stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $orderId);
            $stmt->execute();
            $stmt->close();
            $updated = true;
        }
    }
    
    // Actualizare status plată
    if (!empty($newPaymentStatus)) {
        $validPaymentStatuses = ['unpaid', 'paid', 'refunded'];
        if (in_array($newPaymentStatus, $validPaymentStatuses)) {
            $stmt = $db->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $newPaymentStatus, $orderId);
            $stmt->execute();
            $stmt->close();
            $updated = true;
            
            // ✅ ACTIVARE AUTOMATĂ DESCĂRCĂRI când plata e marcată ca "paid"
            if ($newPaymentStatus === 'paid') {
                if (enableOrderDownloads($orderId)) {
                    $downloadsActivated = true;
                }
                
                // ═══════════════════════════════════════════════════════════════════════════
                // REFERRAL REWARD - Activează recompensa dacă e prima comandă plătită
                // ═══════════════════════════════════════════════════════════════════════════
                // Obține user_id al comenzii
                $stmt = $db->prepare("SELECT user_id FROM orders WHERE id = ?");
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($result && $result['user_id']) {
                    $orderUserId = $result['user_id'];
                    
                    // Verifică dacă e prima comandă plătită a utilizatorului
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as paid_orders_count 
                        FROM orders 
                        WHERE user_id = ? AND payment_status = 'paid'
                    ");
                    $stmt->bind_param("i", $orderUserId);
                    $stmt->execute();
                    $countResult = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    // Dacă e prima comandă plătită, activează referral reward
                    if ($countResult['paid_orders_count'] == 1) {
                        activateReferralReward($orderUserId);
                        error_log("REFERRAL: Checking first paid order for user $orderUserId");
                    }
                }
            }
        }
    }
    
    if ($updated) {
        $message = "Status actualizat cu succes!";
        if ($downloadsActivated) {
            $message .= " Descărcările au fost activate automat pentru client.";
        }
        setMessage($message, "success");
    } else {
        setMessage("Nu s-a selectat nimic de actualizat.", "warning");
    }
    
    // Redirect pentru a preveni re-submit
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Procesare ștergere comandă
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    
    // Șterge itemii comenzii mai întâi
    $db->query("DELETE FROM order_items WHERE order_id = $deleteId");
    
    // Șterge comanda
    $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    
    if ($stmt->execute()) {
        setMessage("Comanda a fost ștearsă cu succes!", "success");
    } else {
        setMessage("Eroare la ștergerea comenzii.", "danger");
    }
    $stmt->close();
    
    redirect('/admin/admin_orders.php');
}

require_once __DIR__ . '/../includes/header.php';

// Afișare mesaje
if (isset($_SESSION['message'])) {
    $messageType = $_SESSION['message_type'] ?? 'success';
    echo '<div class="alert alert-' . htmlspecialchars($messageType) . ' alert-dismissible fade show m-3" role="alert">';
    echo htmlspecialchars($_SESSION['message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Filtre și căutare
$whereConditions = [];
$searchQuery = '';
$statusFilter = '';
$dateFrom = '';
$dateTo = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchQuery = cleanInput($_GET['search']);
    $whereConditions[] = "(o.order_number LIKE '%$searchQuery%' OR u.username LIKE '%$searchQuery%' OR u.email LIKE '%$searchQuery%')";
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $statusFilter = cleanInput($_GET['status']);
    $whereConditions[] = "o.status = '$statusFilter'";
}

if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $dateFrom = cleanInput($_GET['date_from']);
    $whereConditions[] = "DATE(o.created_at) >= '$dateFrom'";
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $dateTo = cleanInput($_GET['date_to']);
    $whereConditions[] = "DATE(o.created_at) <= '$dateTo'";
}

$whereSQL = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Paginare
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Count total comenzi
$countQuery = "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.user_id = u.id $whereSQL";
$totalOrders = $db->query($countQuery)->fetch_assoc()['total'];
$totalPages = ceil($totalOrders / $perPage);

// Obține comenzi
$query = "SELECT o.*, u.username, u.email, u.first_name, u.last_name,
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          $whereSQL
          ORDER BY o.created_at DESC 
          LIMIT $perPage OFFSET $offset";
$orders = $db->query($query)->fetch_all(MYSQLI_ASSOC);

// Statistici
$stats = [
    'total' => $db->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'],
    'pending' => $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'],
    'processing' => $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'processing'")->fetch_assoc()['count'],
    'completed' => $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")->fetch_assoc()['count'],
    'cancelled' => $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")->fetch_assoc()['count'],
    'revenue' => $db->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0
];

// Helper function pentru badge status
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning text-dark">În așteptare</span>',
        'processing' => '<span class="badge bg-info">În procesare</span>',
        'completed' => '<span class="badge bg-success">Finalizată</span>',
        'cancelled' => '<span class="badge bg-danger">Anulată</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Necunoscut</span>';
}

// Helper function pentru badge payment status
function getPaymentStatusBadge($status) {
    $badges = [
        'unpaid' => '<span class="badge bg-danger">Neplătit</span>',
        'paid' => '<span class="badge bg-success">Plătit</span>',
        'refunded' => '<span class="badge bg-warning text-dark">Rambursat</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Necunoscut</span>';
}
?>

<!-- Page Header -->
<section class="bg-dark text-white py-4">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-cart-check me-2"></i>Gestionare Comenzi
                </h1>
                <p class="mb-0 text-white-50">Total: <?php echo $totalOrders; ?> comenzi</p>
            </div>
            <div class="btn-group">
                <a href="<?php echo SITE_URL; ?>/admin/sync_downloads.php" class="btn btn-warning">
                    <i class="bi bi-arrow-repeat me-2"></i>Sincronizare Descărcări
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline-light">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Orders Management -->
<section class="py-4">
    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-cart text-primary mb-2" style="font-size: 2rem;"></i>
                        <h4 class="fw-bold mb-0"><?php echo $stats['total']; ?></h4>
                        <small class="text-muted">Total Comenzi</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-clock text-warning mb-2" style="font-size: 2rem;"></i>
                        <h4 class="fw-bold mb-0"><?php echo $stats['pending']; ?></h4>
                        <small class="text-muted">În așteptare</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-hourglass-split text-info mb-2" style="font-size: 2rem;"></i>
                        <h4 class="fw-bold mb-0"><?php echo $stats['processing']; ?></h4>
                        <small class="text-muted">În procesare</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle text-success mb-2" style="font-size: 2rem;"></i>
                        <h4 class="fw-bold mb-0"><?php echo $stats['completed']; ?></h4>
                        <small class="text-muted">Finalizate</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-x-circle text-danger mb-2" style="font-size: 2rem;"></i>
                        <h4 class="fw-bold mb-0"><?php echo $stats['cancelled']; ?></h4>
                        <small class="text-muted">Anulate</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
                    <div class="card-body text-center">
                        <i class="bi bi-cash-stack text-primary mb-2" style="font-size: 2rem;"></i>
                        <h4 class="fw-bold mb-0"><?php echo number_format($stats['revenue'], 2); ?> LEI</h4>
                        <small class="text-muted">Vânzări totale</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Căutare comandă, client..." 
                               value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">Toate statusurile</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>În așteptare</option>
                            <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>În procesare</option>
                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Finalizată</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Anulată</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_from" class="form-control" placeholder="De la data" 
                               value="<?php echo htmlspecialchars($dateFrom); ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" class="form-control" placeholder="Până la data" 
                               value="<?php echo htmlspecialchars($dateTo); ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-1"></i>Caută
                        </button>
                        <a href="<?php echo SITE_URL; ?>/admin/admin_orders.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nr. Comandă</th>
                                <th>Client</th>
                                <th>Data</th>
                                <th>Produse</th>
                                <th>Total</th>
                                <th>Plată</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 250px;">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($order['first_name'] && $order['last_name']): ?>
                                                <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                                                <br>
                                            <?php endif; ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                        </td>
                                        <td>
                                            <small>
                                                <?php echo date('d.m.Y', strtotime($order['created_at'])); ?>
                                                <br>
                                                <?php echo date('H:i', strtotime($order['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $order['items_count']; ?> produse</span>
                                        </td>
                                        <td>
                                            <strong class="text-primary"><?php echo number_format($order['total_amount'], 2); ?> LEI</strong>
                                        </td>
                                        <td>
                                            <?php echo getPaymentStatusBadge($order['payment_status']); ?>
                                        </td>
                                        <td>
                                            <?php echo getStatusBadge($order['status']); ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?php echo SITE_URL; ?>/admin/view_order.php?id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-outline-info"
                                                   title="Vizualizare detalii">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                
                                                <?php if ($order['payment_method'] === 'bank_transfer' && $order['payment_status'] === 'unpaid'): ?>
                                                <button type="button" 
                                                        class="btn btn-outline-success"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#quickPayModal<?php echo $order['id']; ?>"
                                                        title="✓ Marcare ca PLĂTITĂ">
                                                    <i class="bi bi-check2-circle"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <button type="button" 
                                                        class="btn btn-outline-primary"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#statusModal<?php echo $order['id']; ?>"
                                                        title="Actualizare status">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                                <a href="?delete=<?php echo $order['id']; ?>" 
                                                   class="btn btn-outline-danger"
                                                   title="Ștergere"
                                                   onclick="return confirm('Ești sigur că vrei să ștergi această comandă?\n\nComanda #<?php echo $order['order_number']; ?>\nClient: <?php echo $order['email']; ?>\nTotal: <?php echo number_format($order['total_amount'], 2); ?> LEI\n\nAceastă acțiune nu poate fi anulată!');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3">Nu există comenzi în baza de date.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Modals Update Status (placed at end of body) -->
<?php if (!empty($orders)): ?>
    <?php foreach ($orders as $order): ?>
        <!-- Quick Pay Confirmation Modal (doar pentru bank_transfer + unpaid) -->
        <?php if ($order['payment_method'] === 'bank_transfer' && $order['payment_status'] === 'unpaid'): ?>
        <div class="modal fade" id="quickPayModal<?php echo $order['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-check-circle-fill me-2"></i>Confirmare Plată Transfer Bancar
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Atenție!</strong> Confirmă doar după ce ai verificat transferul în contul bancar.
                            </div>
                            
                            <div class="border-start border-success border-4 ps-3 mb-3">
                                <p class="mb-1"><strong>Comandă:</strong> #<?php echo htmlspecialchars($order['order_number']); ?></p>
                                <p class="mb-1"><strong>Client:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                                <p class="mb-0"><strong>Sumă:</strong> <span class="text-success fs-5"><?php echo number_format($order['total_amount'], 2); ?> RON</span></p>
                            </div>
                            
                            <p class="mb-0 small text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Această acțiune va:
                            </p>
                            <ul class="small text-muted mb-0">
                                <li>Marca comanda ca <strong>PLĂTITĂ</strong></li>
                                <li>Schimba statusul în <strong>FINALIZATĂ</strong></li>
                                <li>Activa descărcările pentru client</li>
                            </ul>
                            
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="payment_status" value="paid">
                            <input type="hidden" name="status" value="completed">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-2"></i>Anulează
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check2-circle me-2"></i>Confirmă Plata
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Regular Status Update Modal -->
        <div class="modal fade" id="statusModal<?php echo $order['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Actualizare Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <p><strong>Comandă:</strong> #<?php echo htmlspecialchars($order['order_number']); ?></p>
                            <p><strong>Client:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                            <hr>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>Status Comandă</strong></label>
                                <select name="status" class="form-select">
                                    <option value="">--- Nu modifica ---</option>
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>În așteptare</option>
                                    <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>În procesare</option>
                                    <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Finalizată</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Anulată</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>Status Plată</strong></label>
                                <select name="payment_status" class="form-select">
                                    <option value="">--- Nu modifica ---</option>
                                    <option value="unpaid" <?php echo $order['payment_status'] === 'unpaid' ? 'selected' : ''; ?>>Neachitat</option>
                                    <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Plătit</option>
                                    <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Rambursat</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Actualizează
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

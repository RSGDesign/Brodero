<?php
/**
 * Admin - Gestionare Cupoane
 * Vizualizare, adăugare, editare, ștergere cupoane de reducere
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Protecție acces: doar admin (ÎNAINTE de orice output)
if (!function_exists('isAdmin') || !isAdmin()) {
    header("Location: " . SITE_URL . "/pages/login.php");
    exit();
}

$db = getDB();

// Asigură funcțiile de mesaje există (fallback minimal)
if (!function_exists('hasMessage')) {
    function hasMessage() {
        return !empty($_SESSION['flash_message']);
    }
}
if (!function_exists('displayMessage')) {
    function displayMessage() {
        if (!empty($_SESSION['flash_message'])) {
            $msg = $_SESSION['flash_message'];
            echo '<div class="alert alert-' . htmlspecialchars($msg['type'] ?? 'info') . ' alert-dismissible fade show">'
                . htmlspecialchars($msg['text'] ?? '') .
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' .
                '</div>';
            unset($_SESSION['flash_message']);
        }
    }
}
if (!function_exists('setMessage')) {
    function setMessage($text, $type = 'info') {
        $_SESSION['flash_message'] = ['text' => $text, 'type' => $type];
    }
}

// Handle POST actions (ÎNAINTE de orice output HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_status') {
        $couponId = (int)$_POST['coupon_id'];
        $newStatus = (int)$_POST['new_status'];
        
        $stmt = $db->prepare("UPDATE coupons SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $newStatus, $couponId);
        
        if ($stmt->execute()) {
            setMessage("Status cupon actualizat cu succes.", "success");
        } else {
            setMessage("Eroare la actualizare status.", "danger");
        }
        header("Location: " . SITE_URL . "/admin/admin_coupons.php");
        exit();
    }
    
    if ($action === 'delete') {
        $couponId = (int)$_POST['coupon_id'];
        
        // Verifică dacă cuponul este folosit în comenzi
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE coupon_code = (SELECT code FROM coupons WHERE id = ?)");
        $stmt->bind_param("i", $couponId);
        $stmt->execute();
        $usageCount = $stmt->get_result()->fetch_assoc()['count'];
        
        if ($usageCount > 0) {
            setMessage("Nu poți șterge acest cupon. Este folosit în $usageCount comenzi.", "danger");
        } else {
            $stmt = $db->prepare("DELETE FROM coupons WHERE id = ?");
            $stmt->bind_param("i", $couponId);
            
            if ($stmt->execute()) {
                setMessage("Cupon șters cu succes.", "success");
            } else {
                setMessage("Eroare la ștergere cupon.", "danger");
            }
        }
        header("Location: " . SITE_URL . "/admin/admin_coupons.php");
        exit();
    }
}

$pageTitle = "Gestionare Cupoane";
// Folosește header-ul general dacă admin_header.php nu există
if (file_exists(__DIR__ . '/../includes/admin_header.php')) {
    require_once __DIR__ . '/../includes/admin_header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}

// Parametri pentru filtrare și căutare
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$filterType = isset($_GET['type']) ? cleanInput($_GET['type']) : '';
$filterStatus = isset($_GET['status']) ? cleanInput($_GET['status']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Construire query
$whereConditions = ["1=1"];
$params = [];
$types = "";

if (!empty($search)) {
    $whereConditions[] = "code LIKE ?";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $types .= "s";
}

if (!empty($filterType)) {
    $whereConditions[] = "discount_type = ?";
    $params[] = $filterType;
    $types .= "s";
}

if ($filterStatus === 'active') {
    $whereConditions[] = "is_active = 1";
} elseif ($filterStatus === 'inactive') {
    $whereConditions[] = "is_active = 0";
} elseif ($filterStatus === 'expired') {
    $whereConditions[] = "expires_at < NOW()";
}

$whereClause = implode(" AND ", $whereConditions);

// Statistici generale
$stats = [
    'total' => $db->query("SELECT COUNT(*) as count FROM coupons")->fetch_assoc()['count'],
    'active' => $db->query("SELECT COUNT(*) as count FROM coupons WHERE is_active = 1")->fetch_assoc()['count'],
    'percent' => $db->query("SELECT COUNT(*) as count FROM coupons WHERE discount_type = 'percent'")->fetch_assoc()['count'],
    'fixed' => $db->query("SELECT COUNT(*) as count FROM coupons WHERE discount_type = 'fixed'")->fetch_assoc()['count'],
    'total_uses' => $db->query("SELECT SUM(used_count) as count FROM coupons")->fetch_assoc()['count'] ?? 0
];

// Obține cupoane
if (!empty($types)) {
    $stmt = $db->prepare("SELECT * FROM coupons WHERE $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $params[] = $perPage;
    $params[] = $offset;
    $types .= "ii";
    $stmt->bind_param($types, ...$params);
} else {
    $stmt = $db->prepare("SELECT * FROM coupons WHERE $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
}

$stmt->execute();
$coupons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Număr total pentru paginare
if (!empty($types) && count($params) > 2) {
    $countTypes = substr($types, 0, -2);
    $countParams = array_slice($params, 0, -2);
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM coupons WHERE $whereClause");
    if (!empty($countParams)) {
        $stmt->bind_param($countTypes, ...$countParams);
    }
    $stmt->execute();
    $totalCoupons = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $totalCoupons = $db->query("SELECT COUNT(*) as total FROM coupons WHERE $whereClause")->fetch_assoc()['total'];
}

$totalPages = ceil($totalCoupons / $perPage);

// Helper functions
function getDiscountBadge($type, $value) {
    if ($type === 'percent') {
        return "<span class='badge bg-info'>{$value}%</span>";
    } else {
        return "<span class='badge bg-success'>{$value} LEI</span>";
    }
}

function getStatusBadge($isActive, $expiresAt) {
    if (!$isActive) {
        return "<span class='badge bg-secondary'>Inactiv</span>";
    }
    if ($expiresAt && strtotime($expiresAt) < time()) {
        return "<span class='badge bg-danger'>Expirat</span>";
    }
    return "<span class='badge bg-success'>Activ</span>";
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-ticket-perforated me-2"></i>Gestionare Cupoane
        </h1>
        <a href="/admin/add_coupon.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Adaugă Cupon
        </a>
    </div>

    <?php if (hasMessage()): ?>
        <?php displayMessage(); ?>
    <?php endif; ?>

    <!-- Statistici -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Cupoane</h6>
                    <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Active</h6>
                    <h2 class="mb-0"><?php echo $stats['active']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Tip Procent</h6>
                    <h2 class="mb-0"><?php echo $stats['percent']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Utilizări Totale</h6>
                    <h2 class="mb-0"><?php echo $stats['total_uses']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtre -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Căutare Cod</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Caută după cod...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tip Discount</label>
                    <select class="form-select" name="type">
                        <option value="">Toate</option>
                        <option value="percent" <?php echo $filterType === 'percent' ? 'selected' : ''; ?>>Procent</option>
                        <option value="fixed" <?php echo $filterType === 'fixed' ? 'selected' : ''; ?>>Fix (LEI)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Toate</option>
                        <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="expired" <?php echo $filterStatus === 'expired' ? 'selected' : ''; ?>>Expirate</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-2"></i>Filtrează
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Cupoane -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($coupons)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-ticket-perforated text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">Nu există cupoane.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Cod</th>
                                <th>Discount</th>
                                <th>Min. Comandă</th>
                                <th>Utilizări</th>
                                <th>Expiră</th>
                                <th>Status</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coupons as $coupon): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($coupon['code']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo getDiscountBadge($coupon['discount_type'], $coupon['discount_value']); ?>
                                    </td>
                                    <td>
                                        <?php if ($coupon['min_order_amount'] > 0): ?>
                                            <?php echo number_format($coupon['min_order_amount'], 2); ?> LEI
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $coupon['used_count']; ?>
                                        <?php if ($coupon['max_uses']): ?>
                                            / <?php echo $coupon['max_uses']; ?>
                                        <?php else: ?>
                                            / <span class="text-muted">∞</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($coupon['expires_at']): ?>
                                            <small><?php echo date('d.m.Y', strtotime($coupon['expires_at'])); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Fără</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo getStatusBadge($coupon['is_active'], $coupon['expires_at']); ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/admin/edit_coupon.php?id=<?php echo $coupon['id']; ?>" 
                                               class="btn btn-outline-primary" title="Editează">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-warning toggle-status-btn"
                                                    data-id="<?php echo $coupon['id']; ?>"
                                                    data-status="<?php echo $coupon['is_active']; ?>"
                                                    title="<?php echo $coupon['is_active'] ? 'Dezactivează' : 'Activează'; ?>">
                                                <i class="bi bi-<?php echo $coupon['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger delete-btn"
                                                    data-id="<?php echo $coupon['id']; ?>"
                                                    data-code="<?php echo htmlspecialchars($coupon['code']); ?>"
                                                    title="Șterge">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginare -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                        Anterior
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                        Următor
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Delete -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmare Ștergere</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Sigur vrei să ștergi cuponul <strong id="deleteCouponCode"></strong>?</p>
                <p class="text-muted mb-0">Această acțiune este ireversibilă.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="coupon_id" id="deleteCouponId">
                    <button type="submit" class="btn btn-danger">Șterge</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle status
document.querySelectorAll('.toggle-status-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const couponId = this.dataset.id;
        const currentStatus = this.dataset.status;
        const newStatus = currentStatus === '1' ? '0' : '1';
        
        if (confirm('Sigur vrei să schimbi statusul acestui cupon?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="coupon_id" value="${couponId}">
                <input type="hidden" name="new_status" value="${newStatus}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
});

// Delete modal
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('deleteCouponId').value = this.dataset.id;
        document.getElementById('deleteCouponCode').textContent = this.dataset.code;
        deleteModal.show();
    });
});
</script>

<?php
if (file_exists(__DIR__ . '/../includes/admin_footer.php')) {
    require_once __DIR__ . '/../includes/admin_footer.php';
} else {
    require_once __DIR__ . '/../includes/footer.php';
}
?>

<?php
/**
 * Admin Newsletter Management
 * Gestionare abonați newsletter - vizualizare, filtrare, dezabonare, ștergere
 */

$pageTitle = "Gestionare Newsletter";

require_once __DIR__ . '/../includes/header.php';

// Verificare acces admin
if (!isAdmin()) {
    setMessage("Nu ai acces la această pagină.", "danger");
    redirect('/');
}

$db = getDB();

// Procesare acțiuni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Toggle status abonat
    if (isset($_POST['toggle_status'])) {
        $subscriberId = (int)$_POST['subscriber_id'];
        
        $stmt = $db->prepare("UPDATE newsletter SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
        $stmt->bind_param("i", $subscriberId);
        
        if ($stmt->execute()) {
            setMessage("Status abonat actualizat cu succes!", "success");
        } else {
            setMessage("Eroare la actualizarea statusului.", "danger");
        }
        redirect('/admin/admin_newsletter.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
    }
    
    // Ștergere abonat
    if (isset($_POST['delete_subscriber'])) {
        $subscriberId = (int)$_POST['subscriber_id'];
        
        $stmt = $db->prepare("DELETE FROM newsletter WHERE id = ?");
        $stmt->bind_param("i", $subscriberId);
        
        if ($stmt->execute()) {
            setMessage("Abonat șters cu succes!", "success");
        } else {
            setMessage("Eroare la ștergerea abonatului.", "danger");
        }
        redirect('/admin/admin_newsletter.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
    }
    
    // Adăugare abonat manual
    if (isset($_POST['add_subscriber'])) {
        $email = trim($_POST['email']);
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setMessage("Email invalid.", "danger");
        } else {
            $stmt = $db->prepare("INSERT INTO newsletter (email, is_active) VALUES (?, 1)");
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                setMessage("Abonat adăugat cu succes!", "success");
            } else {
                setMessage("Email-ul este deja în listă sau a apărut o eroare.", "danger");
            }
        }
        redirect('/admin/admin_newsletter.php');
    }
}

// Filtre
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Statistici
$totalSubscribers = $db->query("SELECT COUNT(*) as total FROM newsletter")->fetch_assoc()['total'];
$activeSubscribers = $db->query("SELECT COUNT(*) as total FROM newsletter WHERE is_active = 1")->fetch_assoc()['total'];
$unsubscribedCount = $db->query("SELECT COUNT(*) as total FROM newsletter WHERE is_active = 0")->fetch_assoc()['total'];

// Statistici suplimentare
$todaySubscribers = $db->query("SELECT COUNT(*) as total FROM newsletter WHERE DATE(subscribed_at) = CURDATE()")->fetch_assoc()['total'];
$thisMonthSubscribers = $db->query("SELECT COUNT(*) as total FROM newsletter WHERE MONTH(subscribed_at) = MONTH(CURRENT_DATE()) AND YEAR(subscribed_at) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'];

// Construire query cu filtre
$query = "SELECT * FROM newsletter WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND email LIKE ?";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $types .= "s";
}

if ($statusFilter !== '') {
    $query .= " AND is_active = ?";
    $params[] = $statusFilter;
    $types .= "i";
}

$query .= " ORDER BY subscribed_at DESC";

// Paginare
$itemsPerPage = 20;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

$queryWithLimit = $query . " LIMIT $itemsPerPage OFFSET $offset";

// Execută query
if ($params) {
    $stmt = $db->prepare($queryWithLimit);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $subscribers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Total pentru paginare
    $stmtCount = $db->prepare(str_replace("*", "COUNT(*) as total", $query));
    $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $totalItems = $stmtCount->get_result()->fetch_assoc()['total'];
} else {
    $subscribers = $db->query($queryWithLimit)->fetch_all(MYSQLI_ASSOC);
    $totalItems = $db->query(str_replace("*", "COUNT(*) as total", $query))->fetch_assoc()['total'];
}

$totalPages = ceil($totalItems / $itemsPerPage);

// Helper function pentru badge status
function getSubscriberStatusBadge($isActive) {
    return $isActive 
        ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Activ</span>'
        : '<span class="badge bg-warning text-dark"><i class="bi bi-x-circle me-1"></i>Dezabonat</span>';
}
?>

<!-- Page Header -->
<section class="bg-dark text-white py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h2 fw-bold mb-0">
                    <i class="bi bi-envelope-paper-fill me-2"></i>Gestionare Newsletter
                </h1>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="<?php echo SITE_URL; ?>/admin/send_newsletter.php" class="btn btn-success me-2">
                    <i class="bi bi-send-fill me-2"></i>Trimite Newsletter
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-2"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Cards -->
<section class="py-4 bg-light">
    <div class="container">
        <div class="row g-3">
            <div class="col-md-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total Abonați</p>
                                <h3 class="fw-bold mb-0"><?php echo $totalSubscribers; ?></h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-people fs-4 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Abonați Activi</p>
                                <h3 class="fw-bold mb-0 text-success"><?php echo $activeSubscribers; ?></h3>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-check-circle fs-4 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Dezabonați</p>
                                <h3 class="fw-bold mb-0 text-warning"><?php echo $unsubscribedCount; ?></h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="bi bi-x-circle fs-4 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Noi Astăzi</p>
                                <h3 class="fw-bold mb-0 text-info"><?php echo $todaySubscribers; ?></h3>
                            </div>
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="bi bi-calendar-day fs-4 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Luna Curentă</p>
                                <h3 class="fw-bold mb-0 text-primary"><?php echo $thisMonthSubscribers; ?></h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-calendar-month fs-4 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Management -->
<section class="py-5">
    <div class="container">
        <!-- Add Subscriber & Filters -->
        <div class="row mb-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">
                            <i class="bi bi-person-plus me-2"></i>Adaugă Abonat
                        </h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="exemplu@email.com" required>
                            </div>
                            <button type="submit" name="add_subscriber" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle me-2"></i>Adaugă
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8 mt-4 mt-lg-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">
                            <i class="bi bi-funnel me-2"></i>Filtre
                        </h5>
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Căutare Email</label>
                                <input type="text" name="search" class="form-control" placeholder="Caută după email..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Toate statusurile</option>
                                    <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Activ</option>
                                    <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Dezabonat</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                            <?php if ($search || $statusFilter !== ''): ?>
                            <div class="col-12">
                                <a href="<?php echo SITE_URL; ?>/admin/admin_newsletter.php" class="btn btn-link text-decoration-none">
                                    <i class="bi bi-x-circle me-1"></i>Resetează filtrele
                                </a>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscribers Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Listă Abonați (<?php echo $totalItems; ?>)</h5>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($subscribers)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-3">Nu există abonați care să corespundă criteriilor de căutare.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3">ID</th>
                                    <th class="py-3">Email</th>
                                    <th class="py-3">Data Înscrierii</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3 text-end pe-4">Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subscribers as $subscriber): ?>
                                <tr>
                                    <td class="px-4 py-3 fw-semibold">#<?php echo $subscriber['id']; ?></td>
                                    <td class="py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                <i class="bi bi-envelope-fill text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($subscriber['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <div><?php echo date('d.m.Y', strtotime($subscriber['subscribed_at'])); ?></div>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($subscriber['subscribed_at'])); ?></small>
                                    </td>
                                    <td class="py-3"><?php echo getSubscriberStatusBadge($subscriber['is_active']); ?></td>
                                    <td class="py-3 text-end pe-4">
                                        <div class="btn-group" role="group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#toggleModal<?php echo $subscriber['id']; ?>"
                                                    title="<?php echo $subscriber['is_active'] ? 'Dezabonează' : 'Reactivează'; ?>">
                                                <i class="bi bi-<?php echo $subscriber['is_active'] ? 'toggle-off' : 'toggle-on'; ?>"></i>
                                            </button>
                                            
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal<?php echo $subscriber['id']; ?>"
                                                    title="Șterge abonat">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Toggle Status Modal -->
                                <div class="modal fade" id="toggleModal<?php echo $subscriber['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <?php echo $subscriber['is_active'] ? 'Dezabonează' : 'Reactivează'; ?> Abonat
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Ești sigur că vrei să <?php echo $subscriber['is_active'] ? 'dezabonezi' : 'reactivezi'; ?> acest abonat?</p>
                                                <div class="alert alert-info">
                                                    <strong><?php echo htmlspecialchars($subscriber['email']); ?></strong>
                                                </div>
                                                <?php if ($subscriber['is_active']): ?>
                                                <p class="text-muted small">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Acest abonat nu va mai primi newslettere până la reactivare.
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-<?php echo $subscriber['is_active'] ? 'warning' : 'success'; ?>">
                                                        <i class="bi bi-<?php echo $subscriber['is_active'] ? 'toggle-off' : 'toggle-on'; ?> me-2"></i>
                                                        <?php echo $subscriber['is_active'] ? 'Dezabonează' : 'Reactivează'; ?>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteModal<?php echo $subscriber['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>Ștergere Abonat
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="fw-semibold">Ești sigur că vrei să ștergi complet acest abonat?</p>
                                                <div class="alert alert-danger">
                                                    <strong><?php echo htmlspecialchars($subscriber['email']); ?></strong>
                                                </div>
                                                <div class="alert alert-warning">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                    <strong>Atenție!</strong> Această acțiune va șterge permanent abonatul din baza de date.
                                                    <p class="mb-0 mt-2 fw-semibold">Această acțiune este IREVERSIBILĂ!</p>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                                    <button type="submit" name="delete_subscriber" class="btn btn-danger">
                                                        <i class="bi bi-trash me-2"></i>Șterge Definitiv
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-0 p-4">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . $statusFilter : ''; ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i === 1 || $i === $totalPages || abs($i - $currentPage) <= 2): ?>
                            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . $statusFilter : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php elseif (abs($i - $currentPage) === 3): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . $statusFilter : ''; ?>">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * Admin Users Management
 * Gestionare completă utilizatori - vizualizare, editare, dezactivare, ștergere
 */

// Include config ÎNAINTE de orice output
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificare acces admin ÎNAINTE de header
if (!isLoggedIn() || !isAdmin()) {
    setMessage("Nu ai acces la această pagină.", "danger");
    redirect('/');
}

$db = getDB();

// Procesare acțiuni POST ÎNAINTE de includerea header.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Toggle status utilizator
    if (isset($_POST['toggle_status'])) {
        $userId = (int)$_POST['user_id'];
        
        // Verificare: nu poate dezactiva propriul cont
        if ($userId === $_SESSION['user_id']) {
            setMessage("Nu poți dezactiva propriul cont!", "danger");
        } else {
            // Adăugăm coloana is_active dacă nu există
            $db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1");
            
            // Toggle status
            $stmt = $db->prepare("UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
            $stmt->bind_param("i", $userId);
            
            if ($stmt->execute()) {
                setMessage("Status utilizator actualizat cu succes!", "success");
            } else {
                setMessage("Eroare la actualizarea statusului.", "danger");
            }
        }
        redirect('/admin/admin_users.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
        exit;
    }
    
    // Ștergere utilizator
    if (isset($_POST['delete_user'])) {
        $userId = (int)$_POST['user_id'];
        
        // Verificare: nu poate șterge propriul cont
        if ($userId === $_SESSION['user_id']) {
            setMessage("Nu poți șterge propriul cont!", "danger");
        } else {
            // Verificare: nu poate șterge singurul admin
            $adminCount = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'")->fetch_assoc()['total'];
            $userRole = $db->query("SELECT role FROM users WHERE id = $userId")->fetch_assoc()['role'];
            
            if ($userRole === 'admin' && $adminCount <= 1) {
                setMessage("Nu poți șterge singurul administrator!", "danger");
            } else {
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                
                if ($stmt->execute()) {
                    setMessage("Utilizator șters cu succes!", "success");
                } else {
                    setMessage("Eroare la ștergerea utilizatorului.", "danger");
                }
            }
        }
        redirect('/admin/admin_users.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
        exit;
    }
}

// Acum includem header.php DUPĂ procesarea acțiunilor
$pageTitle = "Gestionare Utilizatori";
require_once __DIR__ . '/../includes/header.php';

// Adăugăm coloana is_active dacă nu există (pentru compatibilitate)
$db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1");

// Filtre
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Statistici
$totalUsers = $db->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$activeUsers = $db->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1")->fetch_assoc()['total'];
$blockedUsers = $db->query("SELECT COUNT(*) as total FROM users WHERE is_active = 0")->fetch_assoc()['total'];
$adminUsers = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'")->fetch_assoc()['total'];

// Construire query cu filtre
$query = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR username LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= "ssss";
}

if ($roleFilter) {
    $query .= " AND role = ?";
    $params[] = $roleFilter;
    $types .= "s";
}

if ($statusFilter !== '') {
    $query .= " AND is_active = ?";
    $params[] = $statusFilter;
    $types .= "i";
}

$query .= " ORDER BY created_at DESC";

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
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Total pentru paginare
    $stmtCount = $db->prepare(str_replace("*", "COUNT(*) as total", $query));
    $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $totalItems = $stmtCount->get_result()->fetch_assoc()['total'];
} else {
    $users = $db->query($queryWithLimit)->fetch_all(MYSQLI_ASSOC);
    $totalItems = $db->query(str_replace("*", "COUNT(*) as total", $query))->fetch_assoc()['total'];
}

$totalPages = ceil($totalItems / $itemsPerPage);

// Helper function pentru badge rol
function getRoleBadge($role) {
    $badges = [
        'admin' => '<span class="badge bg-danger"><i class="bi bi-shield-fill-check me-1"></i>Administrator</span>',
        'user' => '<span class="badge bg-primary"><i class="bi bi-person-fill me-1"></i>Client</span>'
    ];
    return $badges[$role] ?? '<span class="badge bg-secondary">Necunoscut</span>';
}

// Helper function pentru badge status
function getStatusBadge($isActive) {
    return $isActive 
        ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Activ</span>'
        : '<span class="badge bg-warning text-dark"><i class="bi bi-x-circle me-1"></i>Blocat</span>';
}
?>

<!-- Page Header -->
<section class="bg-dark text-white py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h2 fw-bold mb-0">
                    <i class="bi bi-people-fill me-2"></i>Gestionare Utilizatori
                </h1>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-2"></i>Înapoi la Dashboard
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Cards -->
<section class="py-4 bg-light">
    <div class="container">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Utilizatori</p>
                                <h3 class="fw-bold mb-0"><?php echo $totalUsers; ?></h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-people fs-3 text-primary"></i>
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
                                <p class="text-muted mb-1">Conturi Active</p>
                                <h3 class="fw-bold mb-0 text-success"><?php echo $activeUsers; ?></h3>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-check-circle fs-3 text-success"></i>
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
                                <p class="text-muted mb-1">Conturi Blocate</p>
                                <h3 class="fw-bold mb-0 text-warning"><?php echo $blockedUsers; ?></h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="bi bi-x-circle fs-3 text-warning"></i>
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
                                <p class="text-muted mb-1">Administratori</p>
                                <h3 class="fw-bold mb-0 text-danger"><?php echo $adminUsers; ?></h3>
                            </div>
                            <div class="bg-danger bg-opacity-10 p-3 rounded">
                                <i class="bi bi-shield-fill-check fs-3 text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Users Management -->
<section class="py-5">
    <div class="container">
        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Căutare</label>
                        <input type="text" name="search" class="form-control" placeholder="Nume, email, username..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Rol</label>
                        <select name="role" class="form-select">
                            <option value="">Toate rolurile</option>
                            <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>Client</option>
                            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status Cont</label>
                        <select name="status" class="form-select">
                            <option value="">Toate statusurile</option>
                            <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Activ</option>
                            <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Blocat</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-2"></i>Filtrează
                        </button>
                    </div>
                    <?php if ($search || $roleFilter || $statusFilter !== ''): ?>
                    <div class="col-12">
                        <a href="<?php echo SITE_URL; ?>/admin/admin_users.php" class="btn btn-link text-decoration-none">
                            <i class="bi bi-x-circle me-1"></i>Resetează filtrele
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Listă Utilizatori (<?php echo $totalItems; ?>)</h5>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($users)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-3">Nu există utilizatori care să corespundă criteriilor de căutare.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3">ID</th>
                                    <th class="py-3">Nume Complet</th>
                                    <th class="py-3">Email</th>
                                    <th class="py-3">Username</th>
                                    <th class="py-3">Telefon</th>
                                    <th class="py-3">Rol</th>
                                    <th class="py-3">Status Cont</th>
                                    <th class="py-3">Data Înregistrării</th>
                                    <th class="py-3 text-end pe-4">Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="px-4 py-3 fw-semibold">#<?php echo $user['id']; ?></td>
                                    <td class="py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                <i class="bi bi-person-fill text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                                    <small class="text-muted">(Tu)</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="py-3"><code><?php echo htmlspecialchars($user['username']); ?></code></td>
                                    <td class="py-3"><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td class="py-3"><?php echo getRoleBadge($user['role']); ?></td>
                                    <td class="py-3"><?php echo getStatusBadge($user['is_active']); ?></td>
                                    <td class="py-3">
                                        <div><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></div>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($user['created_at'])); ?></small>
                                    </td>
                                    <td class="py-3 text-end pe-4">
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo SITE_URL; ?>/admin/edit_user.php?id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Editează utilizator">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#toggleModal<?php echo $user['id']; ?>"
                                                    title="<?php echo $user['is_active'] ? 'Blochează' : 'Reactivează'; ?> cont">
                                                <i class="bi bi-<?php echo $user['is_active'] ? 'lock' : 'unlock'; ?>"></i>
                                            </button>
                                            
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal<?php echo $user['id']; ?>"
                                                    title="Șterge utilizator">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Toggle Status Modal -->
                                <div class="modal fade" id="toggleModal<?php echo $user['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <?php echo $user['is_active'] ? 'Blochează' : 'Reactivează'; ?> Cont
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Ești sigur că vrei să <?php echo $user['is_active'] ? 'blochezi' : 'reactivezi'; ?> contul utilizatorului:</p>
                                                <div class="alert alert-info">
                                                    <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($user['email']); ?></small>
                                                </div>
                                                <?php if ($user['is_active']): ?>
                                                <p class="text-muted small">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Utilizatorul nu va mai putea accesa contul său până la reactivare.
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>">
                                                        <i class="bi bi-<?php echo $user['is_active'] ? 'lock' : 'unlock'; ?> me-2"></i>
                                                        <?php echo $user['is_active'] ? 'Blochează' : 'Reactivează'; ?>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>Ștergere Utilizator
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="fw-semibold">Ești sigur că vrei să ștergi complet acest utilizator?</p>
                                                <div class="alert alert-danger">
                                                    <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($user['email']); ?></small>
                                                </div>
                                                <div class="alert alert-warning">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                    <strong>Atenție!</strong> Această acțiune va șterge:
                                                    <ul class="mb-0 mt-2">
                                                        <li>Toate datele utilizatorului</li>
                                                        <li>Comenzile asociate</li>
                                                        <li>Adresele salvate</li>
                                                    </ul>
                                                    <p class="mb-0 mt-2 fw-semibold">Această acțiune este IREVERSIBILĂ!</p>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-danger">
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
                            <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $roleFilter ? '&role=' . $roleFilter : ''; ?><?php echo $statusFilter !== '' ? '&status=' . $statusFilter : ''; ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i === 1 || $i === $totalPages || abs($i - $currentPage) <= 2): ?>
                            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $roleFilter ? '&role=' . $roleFilter : ''; ?><?php echo $statusFilter !== '' ? '&status=' . $statusFilter : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php elseif (abs($i - $currentPage) === 3): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $roleFilter ? '&role=' . $roleFilter : ''; ?><?php echo $statusFilter !== '' ? '&status=' . $statusFilter : ''; ?>">
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

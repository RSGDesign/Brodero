<?php
/**
 * Admin - Custom Orders Management
 * Gestionare comenzi personalizate din dashboard
 */

$pageTitle = "Comenzi Personalizate - Admin";

require_once __DIR__ . '/../includes/header.php';

// Verificare acces admin
if (!isAdmin()) {
    setMessage("Nu ai acces la această pagină.", "danger");
    redirect('/');
}

$db = getDB();

// ============================================================================
// ACȚIUNI
// ============================================================================

// Ștergere comandă
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    
    // Obține fișierul pentru ștergere
    $stmt = $db->prepare("SELECT file_path FROM custom_orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order && $order['file_path']) {
        $filePath = __DIR__ . '/../' . $order['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Șterge din DB
    $stmt = $db->prepare("DELETE FROM custom_orders WHERE id = ?");
    $stmt->execute([$orderId]);
    
    setMessage("Comanda a fost ștearsă cu succes.", "success");
    redirect('/admin/admin_custom_orders.php');
}

// Schimbare status
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    
    $allowedStatuses = ['new', 'in_progress', 'completed', 'cancelled'];
    if (in_array($newStatus, $allowedStatuses)) {
        $stmt = $db->prepare("UPDATE custom_orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        
        setMessage("Status actualizat cu succes.", "success");
        redirect('/admin/admin_custom_orders.php');
    }
}

// Salvare note admin
if (isset($_POST['action']) && $_POST['action'] === 'save_notes') {
    $orderId = (int)$_POST['order_id'];
    $notes = trim($_POST['admin_notes']);
    
    $stmt = $db->prepare("UPDATE custom_orders SET admin_notes = ? WHERE id = ?");
    $stmt->execute([$notes, $orderId]);
    
    echo json_encode(['success' => true]);
    exit;
}

// ============================================================================
// OBȚINERE DATE
// ============================================================================

// Filtrare după status
$filterStatus = $_GET['status'] ?? 'all';

$query = "SELECT * FROM custom_orders";
$params = [];

if ($filterStatus !== 'all') {
    $query .= " WHERE status = ?";
    $params[] = $filterStatus;
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistici
$statsQuery = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_orders,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM custom_orders
";
$stats = $db->query($statsQuery)->fetch(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<section class="bg-dark text-white py-4">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="h2 fw-bold mb-0">
                    <i class="bi bi-palette me-2"></i>Comenzi Personalizate
                </h1>
                <p class="mb-0 mt-2 opacity-75">Gestionează cererile de modele la comandă</p>
            </div>
        </div>
    </div>
</section>

<!-- Admin Section -->
<section class="py-5">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_products.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-box-seam me-2"></i>Produse
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_orders.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-cart-check me-2"></i>Comenzi
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_custom_orders.php" 
                               class="list-group-item list-group-item-action active">
                                <i class="bi bi-palette me-2"></i>Comenzi Personalizate
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_users.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-people me-2"></i>Utilizatori
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/seo-pages.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-search me-2"></i>SEO Pages
                            </a>
                            <a href="<?php echo SITE_URL; ?>/pages/cont.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-arrow-left me-2"></i>Înapoi la Cont
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <!-- Messages -->
                <?php if (hasMessage()): ?>
                    <div class="alert alert-<?php echo getMessageType(); ?> alert-dismissible fade show" role="alert">
                        <?php echo getMessage(); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistici -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Total Cereri</p>
                                        <h3 class="mb-0 fw-bold"><?php echo $stats['total']; ?></h3>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                        <i class="bi bi-inbox-fill text-primary fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Noi</p>
                                        <h3 class="mb-0 fw-bold text-warning"><?php echo $stats['new_orders']; ?></h3>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                        <i class="bi bi-star-fill text-warning fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">În Lucru</p>
                                        <h3 class="mb-0 fw-bold text-info"><?php echo $stats['in_progress']; ?></h3>
                                    </div>
                                    <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                        <i class="bi bi-hourglass-split text-info fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Finalizate</p>
                                        <h3 class="mb-0 fw-bold text-success"><?php echo $stats['completed']; ?></h3>
                                    </div>
                                    <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                        <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtre -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0">
                                    <i class="bi bi-funnel me-2"></i>Filtrează comenzi
                                </h5>
                            </div>
                            <div class="col-md-6">
                                <div class="btn-group w-100" role="group">
                                    <a href="?status=all" class="btn btn-sm <?php echo $filterStatus === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                        Toate (<?php echo $stats['total']; ?>)
                                    </a>
                                    <a href="?status=new" class="btn btn-sm <?php echo $filterStatus === 'new' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                        Noi (<?php echo $stats['new_orders']; ?>)
                                    </a>
                                    <a href="?status=in_progress" class="btn btn-sm <?php echo $filterStatus === 'in_progress' ? 'btn-info' : 'btn-outline-info'; ?>">
                                        În Lucru (<?php echo $stats['in_progress']; ?>)
                                    </a>
                                    <a href="?status=completed" class="btn btn-sm <?php echo $filterStatus === 'completed' ? 'btn-success' : 'btn-outline-success'; ?>">
                                        Finalizate (<?php echo $stats['completed']; ?>)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista comenzi -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                                <p class="text-muted">Nu există comenzi personalizate<?php echo $filterStatus !== 'all' ? ' cu statusul selectat' : ''; ?>.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="px-4 py-3">ID</th>
                                            <th class="py-3">Client</th>
                                            <th class="py-3">Descriere</th>
                                            <th class="py-3">Fișier</th>
                                            <th class="py-3">Status</th>
                                            <th class="py-3">Data</th>
                                            <th class="py-3 text-end pe-4">Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td class="px-4">
                                                    <strong>#<?php echo $order['id']; ?></strong>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($order['name']); ?></strong><br>
                                                        <small class="text-muted">
                                                            <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($order['email']); ?>
                                                        </small>
                                                        <?php if ($order['phone']): ?>
                                                            <br><small class="text-muted">
                                                                <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($order['phone']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 300px;" title="<?php echo htmlspecialchars($order['description']); ?>">
                                                        <?php echo htmlspecialchars(substr($order['description'], 0, 100)) . '...'; ?>
                                                    </div>
                                                    <?php if ($order['budget']): ?>
                                                        <small class="badge bg-light text-dark">
                                                            Buget: <?php echo number_format($order['budget'], 2); ?> RON
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($order['file_path']): ?>
                                                        <a href="<?php echo SITE_URL . '/' . $order['file_path']; ?>" 
                                                           download class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-download me-1"></i>
                                                            <?php echo htmlspecialchars($order['file_original_name']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <small class="text-muted">Fără fișier</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusBadges = [
                                                        'new' => '<span class="badge bg-warning">Nou</span>',
                                                        'in_progress' => '<span class="badge bg-info">În Lucru</span>',
                                                        'completed' => '<span class="badge bg-success">Finalizat</span>',
                                                        'cancelled' => '<span class="badge bg-secondary">Anulat</span>'
                                                    ];
                                                    echo $statusBadges[$order['status']] ?? $order['status'];
                                                    ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                        <i class="bi bi-eye"></i> Detalii
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalii Comandă #<span id="modalOrderId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="text-center py-4">
                    <span class="spinner-border"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewOrder(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
    document.getElementById('modalOrderId').textContent = orderId;
    
    fetch('<?php echo SITE_URL; ?>/ajax/get_custom_order.php?id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const order = data.order;
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Informații Client</h6>
                            <p><strong>Nume:</strong> ${order.name}</p>
                            <p><strong>Email:</strong> <a href="mailto:${order.email}">${order.email}</a></p>
                            ${order.phone ? `<p><strong>Telefon:</strong> <a href="tel:${order.phone}">${order.phone}</a></p>` : ''}
                            <p><strong>IP Address:</strong> <small class="text-muted">${order.ip_address || 'N/A'}</small></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Detalii Comandă</h6>
                            <p><strong>Status:</strong> 
                                <select class="form-select form-select-sm d-inline-block w-auto" onchange="updateStatus(${order.id}, this.value)">
                                    <option value="new" ${order.status === 'new' ? 'selected' : ''}>Nou</option>
                                    <option value="in_progress" ${order.status === 'in_progress' ? 'selected' : ''}>În Lucru</option>
                                    <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Finalizat</option>
                                    <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Anulat</option>
                                </select>
                            </p>
                            ${order.budget ? `<p><strong>Buget:</strong> ${parseFloat(order.budget).toFixed(2)} RON</p>` : ''}
                            <p><strong>Data:</strong> ${new Date(order.created_at).toLocaleString('ro-RO')}</p>
                        </div>
                    </div>
                    <hr>
                    <h6 class="fw-bold mb-3">Descriere</h6>
                    <div class="p-3 bg-light rounded">
                        <p class="mb-0">${order.description.replace(/\n/g, '<br>')}</p>
                    </div>
                    ${order.file_path ? `
                        <hr>
                        <h6 class="fw-bold mb-3">Fișier Atașat</h6>
                        <p>
                            <a href="${order.file_path}" download class="btn btn-outline-primary">
                                <i class="bi bi-download me-2"></i>${order.file_original_name}
                            </a>
                        </p>
                    ` : ''}
                    <hr>
                    <h6 class="fw-bold mb-3">Note Admin (interne)</h6>
                    <textarea class="form-control" rows="3" id="adminNotes">${order.admin_notes || ''}</textarea>
                    <button class="btn btn-sm btn-primary mt-2" onclick="saveNotes(${order.id})">
                        <i class="bi bi-save me-1"></i>Salvează Note
                    </button>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-outline-danger" onclick="deleteOrder(${order.id})">
                            <i class="bi bi-trash me-1"></i>Șterge Comandă
                        </button>
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
                    </div>
                `;
                document.getElementById('modalContent').innerHTML = html;
            } else {
                document.getElementById('modalContent').innerHTML = '<div class="alert alert-danger">Eroare la încărcarea datelor</div>';
            }
        });
    
    modal.show();
}

function updateStatus(orderId, status) {
    if (confirm('Sigur vrei să schimbi statusul?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="${orderId}">
            <input type="hidden" name="status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function saveNotes(orderId) {
    const notes = document.getElementById('adminNotes').value;
    
    fetch('<?php echo SITE_URL; ?>/admin/admin_custom_orders.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=save_notes&order_id=${orderId}&admin_notes=${encodeURIComponent(notes)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Note salvate cu succes!');
        }
    });
}

function deleteOrder(orderId) {
    if (confirm('Sigur vrei să ștergi această comandă? Acțiunea este ireversibilă!')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="order_id" value="${orderId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

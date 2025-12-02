<?php
/**
 * Dashboard Admin
 * Gestionare produse, comenzi, utilizatori și statistici
 */

$pageTitle = "Dashboard Admin";

require_once __DIR__ . '/../includes/header.php';

// Verificare acces admin
if (!isAdmin()) {
    setMessage("Nu ai acces la această pagină.", "danger");
    redirect('/');
}

$db = getDB();

// Obține statistici
$stats = [];

// Total produse
$result = $db->query("SELECT COUNT(*) as total FROM products");
$stats['products'] = $result->fetch_assoc()['total'];

// Total comenzi
$result = $db->query("SELECT COUNT(*) as total FROM orders");
$stats['orders'] = $result->fetch_assoc()['total'];

// Total utilizatori
$result = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stats['users'] = $result->fetch_assoc()['total'];

// Vânzări totale
$result = $db->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'");
$stats['revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Comenzi recente
$recentOrders = $db->query("SELECT o.*, u.email, u.first_name, u.last_name 
                            FROM orders o 
                            JOIN users u ON o.user_id = u.id 
                            ORDER BY o.created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Produse populare
$popularProducts = $db->query("SELECT * FROM products ORDER BY views DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$activeSection = $_GET['section'] ?? 'dashboard';
?>

<!-- Page Header -->
<section class="bg-dark text-white py-4">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="h2 fw-bold mb-0">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard Admin
                </h1>
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
                            <a href="?section=dashboard" 
                               class="list-group-item list-group-item-action <?php echo $activeSection === 'dashboard' ? 'active' : ''; ?>">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_products.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-box-seam me-2"></i>Produse
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_categories.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-tags me-2"></i>Categorii
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_orders.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-cart-check me-2"></i>Comenzi
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_users.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-people me-2"></i>Utilizatori
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_newsletter.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-envelope-paper me-2"></i>Newsletter
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
                <?php if ($activeSection === 'dashboard'): ?>
                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1">Total Produse</p>
                                            <h3 class="fw-bold mb-0"><?php echo $stats['products']; ?></h3>
                                        </div>
                                        <div class="feature-icon" style="width: 60px; height: 60px;">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1">Total Comenzi</p>
                                            <h3 class="fw-bold mb-0"><?php echo $stats['orders']; ?></h3>
                                        </div>
                                        <div class="feature-icon" style="width: 60px; height: 60px;">
                                            <i class="bi bi-bag-check"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1">Utilizatori</p>
                                            <h3 class="fw-bold mb-0"><?php echo $stats['users']; ?></h3>
                                        </div>
                                        <div class="feature-icon" style="width: 60px; height: 60px;">
                                            <i class="bi bi-people"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1">Vânzări Totale</p>
                                            <h3 class="fw-bold mb-0"><?php echo number_format($stats['revenue'], 2); ?> LEI</h3>
                                        </div>
                                        <div class="feature-icon" style="width: 60px; height: 60px;">
                                            <i class="bi bi-currency-exchange"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-4">
                        <!-- Comenzi Recente -->
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-0 p-4">
                                    <h5 class="fw-bold mb-0">Comenzi Recente</h5>
                                </div>
                                <div class="card-body p-4">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Nr. Comandă</th>
                                                    <th>Client</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Data</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td class="fw-bold">#<?php echo $order['order_number']; ?></td>
                                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                                    <td><?php echo number_format($order['total_amount'], 2); ?> LEI</td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Produse Populare -->
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-0 p-4">
                                    <h5 class="fw-bold mb-0">Produse Populare</h5>
                                </div>
                                <div class="card-body p-4">
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($popularProducts as $product): ?>
                                        <div class="list-group-item px-0">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                    <small class="text-muted"><?php echo $product['views']; ?> vizualizări</small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                
                <?php elseif ($activeSection === 'produse'): ?>
                    <!-- Gestionare Produse -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 p-4 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Gestionare Produse</h5>
                            <a href="<?php echo SITE_URL; ?>/admin/produs-nou.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Adaugă Produs
                            </a>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted">Funcționalitate de gestionare produse - adăugare, editare, ștergere produse.</p>
                            <p class="text-info">Această secțiune poate fi extinsă cu formulare complete pentru CRUD produse.</p>
                        </div>
                    </div>
                
                <?php elseif ($activeSection === 'comenzi'): ?>
                    <!-- Gestionare Comenzi -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 p-4">
                            <h5 class="fw-bold mb-0">Gestionare Comenzi</h5>
                        </div>
                        <div class="card-body p-4">
                            <?php
                            $allOrders = $db->query("SELECT o.*, u.email, u.first_name, u.last_name 
                                                     FROM orders o 
                                                     JOIN users u ON o.user_id = u.id 
                                                     ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);
                            ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Nr. Comandă</th>
                                            <th>Client</th>
                                            <th>Email</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Plată</th>
                                            <th>Data</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allOrders as $order): ?>
                                        <tr>
                                            <td class="fw-bold">#<?php echo $order['order_number']; ?></td>
                                            <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['email']); ?></td>
                                            <td><?php echo number_format($order['total_amount'], 2); ?> LEI</td>
                                            <td><span class="badge bg-info"><?php echo $order['status']; ?></span></td>
                                            <td><span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                <?php echo $order['payment_status']; ?>
                                            </span></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary">Detalii</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                
                <?php elseif ($activeSection === 'utilizatori'): ?>
                    <!-- Gestionare Utilizatori -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 p-4">
                            <h5 class="fw-bold mb-0">Gestionare Utilizatori</h5>
                        </div>
                        <div class="card-body p-4">
                            <?php
                            $allUsers = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
                            ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nume</th>
                                            <th>Email</th>
                                            <th>Telefon</th>
                                            <th>Rol</th>
                                            <th>Înregistrat</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allUsers as $u): ?>
                                        <tr>
                                            <td><?php echo $u['id']; ?></td>
                                            <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td><?php echo htmlspecialchars($u['phone'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $u['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                    <?php echo $u['role']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($u['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary">Editează</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

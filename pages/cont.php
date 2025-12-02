<?php
/**
 * Pagina Contul Meu
 * Dashboard utilizator cu comenzi, fișiere descărcabile și profil
 */

$pageTitle = "Contul Meu";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_downloads.php';

// Verificare autentificare
if (!isLoggedIn()) {
    setMessage("Trebuie să fii autentificat pentru a accesa această pagină.", "danger");
    redirect('/pages/login.php?redirect=/pages/cont.php');
}

$db = getDB();
$userId = $_SESSION['user_id'];

// Obține informații utilizator
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Obține comenzi
$ordersQuery = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$stmt = $db->prepare($ordersQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obține fișiere descărcabile folosind funcția helper
$downloads = getUserDownloadableFiles($userId);

// Procesare actualizare profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = cleanInput($_POST['first_name'] ?? '');
    $lastName = cleanInput($_POST['last_name'] ?? '');
    $phone = cleanInput($_POST['phone'] ?? '');
    
    $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $firstName, $lastName, $phone, $userId);
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $firstName . ' ' . $lastName;
        setMessage("Profilul a fost actualizat cu succes!", "success");
        redirect('/pages/cont.php');
    } else {
        setMessage("Eroare la actualizarea profilului.", "danger");
    }
    $stmt->close();
}

$activeTab = $_GET['tab'] ?? 'comenzi';
?>

<!-- Page Header -->
<section class="bg-light py-4">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="h2 fw-bold mb-0">Contul Meu</h1>
                <p class="mb-0 text-muted">Bun venit, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
            </div>
        </div>
    </div>
</section>

<!-- Account Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="?tab=comenzi" 
                               class="list-group-item list-group-item-action <?php echo $activeTab === 'comenzi' ? 'active' : ''; ?>">
                                <i class="bi bi-bag-check me-2"></i>Comenzile Mele
                            </a>
                            <a href="?tab=fisiere" 
                               class="list-group-item list-group-item-action <?php echo $activeTab === 'fisiere' ? 'active' : ''; ?>">
                                <i class="bi bi-download me-2"></i>Fișiere Descărcabile
                            </a>
                            <a href="?tab=profil" 
                               class="list-group-item list-group-item-action <?php echo $activeTab === 'profil' ? 'active' : ''; ?>">
                                <i class="bi bi-person me-2"></i>Profilul Meu
                            </a>
                            <?php if (isAdmin()): ?>
                            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard Admin
                            </a>
                            <?php endif; ?>
                            <a href="<?php echo SITE_URL; ?>/pages/logout.php" 
                               class="list-group-item list-group-item-action text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>Deconectare
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <?php if ($activeTab === 'comenzi'): ?>
                    <!-- Comenzi -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 p-4">
                            <h4 class="fw-bold mb-0">
                                <i class="bi bi-bag-check me-2"></i>Comenzile Mele
                            </h4>
                        </div>
                        <div class="card-body p-4">
                            <?php if (!empty($orders)): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Nr. Comandă</th>
                                                <th>Data</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Plată</th>
                                                <th>Acțiuni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td class="fw-bold">#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                                <td><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></td>
                                                <td><?php echo number_format($order['total_amount'], 2); ?> LEI</td>
                                                <td>
                                                    <?php
                                                    $statusClass = [
                                                        'pending' => 'warning',
                                                        'processing' => 'info',
                                                        'completed' => 'success',
                                                        'cancelled' => 'danger'
                                                    ];
                                                    $statusText = [
                                                        'pending' => 'În așteptare',
                                                        'processing' => 'În procesare',
                                                        'completed' => 'Completată',
                                                        'cancelled' => 'Anulată'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass[$order['status']]; ?>">
                                                        <?php echo $statusText[$order['status']]; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $paymentClass = [
                                                        'unpaid' => 'danger',
                                                        'paid' => 'success',
                                                        'refunded' => 'secondary'
                                                    ];
                                                    $paymentText = [
                                                        'unpaid' => 'Neplătită',
                                                        'paid' => 'Plătită',
                                                        'refunded' => 'Rambursată'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?php echo $paymentClass[$order['payment_status']]; ?>">
                                                        <?php echo $paymentText[$order['payment_status']]; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="<?php echo SITE_URL; ?>/pages/comanda.php?id=<?php echo $order['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        Detalii
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                                    <h5 class="mt-3">Nu ai comenzi încă</h5>
                                    <p class="text-muted">Explorează magazinul nostru și comandă primele tale design-uri!</p>
                                    <a href="<?php echo SITE_URL; ?>/pages/magazin.php" class="btn btn-primary">
                                        Vizitează Magazinul
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                
                <?php elseif ($activeTab === 'fisiere'): ?>
                    <!-- Fișiere Descărcabile -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 p-4">
                            <h4 class="fw-bold mb-0">
                                <i class="bi bi-download me-2"></i>Fișiere Descărcabile
                            </h4>
                        </div>
                        <div class="card-body p-4">
                            <?php if (!empty($downloads)): ?>
                                <div class="list-group">
                                    <?php foreach ($downloads as $download): ?>
                                    <?php
                                    $remaining = (int)$download['download_limit'] - (int)$download['download_count'];
                                    $limitReached = ((int)$download['download_limit'] > 0) && ($remaining <= 0);
                                    
                                    $isPaid = ($download['payment_status'] === 'paid');
                                    $isEnabled = ($download['downloads_enabled'] == 1);
                                    
                                    $canDownload = $isPaid && $isEnabled && !$limitReached;
                                    ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fw-bold">
                                                    <?php echo htmlspecialchars($download['file_name']); ?>
                                                </h6>
                                                <small class="text-muted">
                                                    Produs: <?php echo htmlspecialchars($download['product_name']); ?> | 
                                                    Comanda: #<?php echo $download['order_id']; ?> | 
                                                    <?php if ((int)$download['download_limit'] > 0): ?>
                                                        Descărcări: <?php echo $download['download_count']; ?>/<?php echo $download['download_limit']; ?>
                                                    <?php else: ?>
                                                        Descărcări: <?php echo $download['download_count']; ?> (nelimitat)
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div>
                                                <?php if ($canDownload): ?>
                                                    <?php 
                                                    $token = generateDownloadToken($download['file_id'], $download['order_id'], $userId);
                                                    ?>
                                                    <a href="<?php echo SITE_URL; ?>/pages/download.php?token=<?php echo urlencode($token); ?>&file=<?php echo $download['file_id']; ?>&order=<?php echo $download['order_id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="bi bi-download me-1"></i>Descarcă
                                                    </a>
                                                <?php else: ?>
                                                    <?php if (!$isPaid): ?>
                                                        <span class="badge bg-warning text-dark">Plată în așteptare</span>
                                                    <?php elseif (!$isEnabled): ?>
                                                        <span class="badge bg-info text-dark">În procesare</span>
                                                    <?php elseif ($limitReached): ?>
                                                        <span class="badge bg-secondary">Limită atinsă</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-file-earmark-zip text-muted" style="font-size: 4rem;"></i>
                                    <h5 class="mt-3">Nu ai fișiere disponibile</h5>
                                    <p class="text-muted">Fișierele descărcabile vor apărea aici după ce cumperi produse digitale.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                
                <?php else: ?>
                    <!-- Profil -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 p-4">
                            <h4 class="fw-bold mb-0">
                                <i class="bi bi-person me-2"></i>Profilul Meu
                            </h4>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">Prenume</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Nume</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                        <div class="form-text">Email-ul nu poate fi modificat</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Telefon</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Cont creat la</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>" disabled>
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="bi bi-check2 me-2"></i>Salvează Modificările
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

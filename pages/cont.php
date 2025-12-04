<?php
/**
 * Pagina Contul Meu
 * Dashboard utilizator cu comenzi, fișiere descărcabile și profil
 */

$pageTitle = "Contul Meu";

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions_downloads.php';

// Verificare autentificare
if (!isLoggedIn()) {
    setMessage("Trebuie să fii autentificat pentru a accesa această pagină.", "danger");
    redirect('/pages/login.php?redirect=/pages/cont.php');
}

$db = getDB();
$userId = $_SESSION['user_id'];

// Procesare actualizare profil ÎNAINTE de header
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

$activeTab = $_GET['tab'] ?? 'comenzi';

require_once __DIR__ . '/../includes/header.php';
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
                                    
                                    // Permite descărcarea dacă este activată (indiferent de statusul plății pentru transfer bancar)
                                    $canDownload = $isEnabled && !$limitReached;
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
                        <div class="card-body p-0">
                            <!-- Navigation Tabs -->
                            <ul class="nav nav-tabs nav-fill border-0" id="profileTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" 
                                            data-bs-target="#personal" type="button" role="tab">
                                        <i class="bi bi-person-fill me-2"></i>Date Personale
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" 
                                            data-bs-target="#password" type="button" role="tab">
                                        <i class="bi bi-key-fill me-2"></i>Schimbare Parolă
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="preferences-tab" data-bs-toggle="tab" 
                                            data-bs-target="#preferences" type="button" role="tab">
                                        <i class="bi bi-gear-fill me-2"></i>Preferințe
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" 
                                            data-bs-target="#security" type="button" role="tab">
                                        <i class="bi bi-shield-fill me-2"></i>Securitate
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content p-4" id="profileTabContent">
                                <!-- Date Personale -->
                                <div class="tab-pane fade show active" id="personal" role="tabpanel">
                                    <div class="row">
                                        <!-- Avatar Upload -->
                                        <div class="col-md-12 mb-4">
                                            <div class="text-center">
                                                <div class="mb-3">
                                                    <?php 
                                                    $avatarUrl = !empty($user['avatar']) 
                                                        ? SITE_URL . '/uploads/' . $user['avatar'] 
                                                        : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']) . '&size=200&background=6366f1&color=fff';
                                                    ?>
                                                    <img src="<?php echo $avatarUrl; ?>" 
                                                         alt="Avatar" 
                                                         class="rounded-circle border border-3 border-primary"
                                                         id="avatarPreview"
                                                         style="width: 150px; height: 150px; object-fit: cover;">
                                                </div>
                                                <div>
                                                    <label for="avatarInput" class="btn btn-outline-primary btn-sm">
                                                        <i class="bi bi-camera me-1"></i>Schimbă Avatar
                                                    </label>
                                                    <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                                                    <p class="text-muted small mt-2">JPG sau PNG. Max 5MB.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Formular Date Personale -->
                                        <div class="col-md-12">
                                            <form id="personalDataForm">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label for="first_name" class="form-label">Prenume *</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="last_name" class="form-label">Nume *</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="username" class="form-label">Username</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                                                            <input type="text" class="form-control" id="username" 
                                                                   value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" disabled>
                                                        </div>
                                                        <small class="text-muted">Username-ul nu poate fi modificat</small>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="email" class="form-label">Email</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                                            <input type="email" class="form-control" id="email" 
                                                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                                                        </div>
                                                        <small class="text-muted">Email-ul nu poate fi modificat</small>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="phone" class="form-label">Telefon</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label for="country" class="form-label">Țara</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                                            <input type="text" class="form-control" id="country" name="country" 
                                                                   value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-12">
                                                        <label for="city" class="form-label">Oraș</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                                            <input type="text" class="form-control" id="city" name="city" 
                                                                   value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <div class="alert alert-info">
                                                            <i class="bi bi-info-circle me-2"></i>
                                                            Cont creat la: <strong><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></strong>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="bi bi-check2 me-2"></i>Salvează Modificările
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Schimbare Parolă -->
                                <div class="tab-pane fade" id="password" role="tabpanel">
                                    <form id="changePasswordForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="alert alert-warning">
                                                    <i class="bi bi-shield-exclamation me-2"></i>
                                                    <strong>Cerințe parolă:</strong> Minim 8 caractere, cel puțin o literă mare și o cifră.
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-12">
                                                <label for="current_password" class="form-label">Parola Curentă *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                                    <input type="password" class="form-control" id="current_password" 
                                                           name="current_password" required>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="new_password" class="form-label">Parola Nouă *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                                    <input type="password" class="form-control" id="new_password" 
                                                           name="new_password" required minlength="8">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="confirm_password" class="form-label">Confirmă Parola *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                                    <input type="password" class="form-control" id="confirm_password" 
                                                           name="confirm_password" required minlength="8">
                                                </div>
                                            </div>
                                            
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-shield-check me-2"></i>Schimbă Parola
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Preferințe -->
                                <div class="tab-pane fade" id="preferences" role="tabpanel">
                                    <form id="preferencesForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        
                                        <div class="row g-4">
                                            <div class="col-12">
                                                <h6 class="fw-bold">Notificări Email</h6>
                                            </div>
                                            
                                            <div class="col-12">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="newsletter" 
                                                           name="newsletter" <?php echo ($user['newsletter'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="newsletter">
                                                        <strong>Newsletter</strong>
                                                        <p class="text-muted small mb-0">Primește oferte speciale și noutăți</p>
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="notifications" 
                                                           name="notifications" <?php echo ($user['notifications'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="notifications">
                                                        <strong>Notificări comenzi</strong>
                                                        <p class="text-muted small mb-0">Primește actualizări despre comenzile tale</p>
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-check2 me-2"></i>Salvează Preferințe
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Securitate -->
                                <div class="tab-pane fade" id="security" role="tabpanel">
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <h6 class="fw-bold">Informații Cont</h6>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="bi bi-calendar3 me-2"></i>
                                                            <strong>Cont creat:</strong> 
                                                            <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($user['last_login'])): ?>
                                        <div class="col-12">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="bi bi-clock me-2"></i>
                                                            <strong>Ultima autentificare:</strong> 
                                                            <?php echo date('d.m.Y H:i', strtotime($user['last_login'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="col-12">
                                            <hr>
                                            <h6 class="fw-bold text-danger">Zonă Periculoasă</h6>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="card border-danger">
                                                <div class="card-body">
                                                    <h6 class="card-title">Dezactivare Cont</h6>
                                                    <p class="card-text text-muted">
                                                        Odată ce îți dezactivezi contul, nu vei mai putea accesa comenzile și fișierele tale.
                                                        Această acțiune poate fi reversată contactând suportul.
                                                    </p>
                                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" 
                                                            data-bs-target="#deleteAccountModal">
                                                        <i class="bi bi-exclamation-triangle me-2"></i>Dezactivează Contul
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Modal Dezactivare Cont -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAccountModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Dezactivare Cont
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <strong>Atenție!</strong> Această acțiune va dezactiva contul tău. Nu vei mai putea accesa comenzile și fișierele.
                </div>
                
                <form id="deleteAccountForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="mb-3">
                        <label for="delete_password" class="form-label">Confirmă parola pentru a continua:</label>
                        <input type="password" class="form-control" id="delete_password" name="password" required>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                        <label class="form-check-label" for="confirmDelete">
                            Înțeleg că această acțiune va dezactiva contul meu
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Dezactivează Contul</button>
            </div>
        </div>
    </div>
</div>

<script>
// AJAX pentru Date Personale
document.getElementById('personalDataForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('<?php echo SITE_URL; ?>/ajax/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Eroare la salvare. Încearcă din nou.', 'danger');
    });
});

// AJAX pentru Preferințe (folosește același endpoint ca Date Personale)
document.getElementById('preferencesForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    // Adaugă câmpurile required pentru a nu cauza erori de validare
    formData.append('first_name', document.getElementById('first_name').value);
    formData.append('last_name', document.getElementById('last_name').value);
    
    fetch('<?php echo SITE_URL; ?>/ajax/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Eroare la salvare. Încearcă din nou.', 'danger');
    });
});

// AJAX pentru Schimbare Parolă
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    // Validare parolă nouă
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        showNotification('Parolele noi nu coincid.', 'danger');
        return;
    }
    
    fetch('<?php echo SITE_URL; ?>/ajax/change_password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            this.reset();
        } else {
            showNotification(data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Eroare la salvare. Încearcă din nou.', 'danger');
    });
});

// Upload Avatar
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Validare tip fișier
    if (!file.type.match('image.*')) {
        showNotification('Selectează o imagine validă (JPG sau PNG).', 'danger');
        return;
    }
    
    // Validare mărime (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showNotification('Imaginea este prea mare. Mărimea maximă este 5MB.', 'danger');
        return;
    }
    
    // Preview local
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('avatarPreview').src = e.target.result;
    };
    reader.readAsDataURL(file);
    
    // Upload
    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
    
    fetch('<?php echo SITE_URL; ?>/ajax/upload_avatar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            if (data.avatar_url) {
                document.getElementById('avatarPreview').src = data.avatar_url;
            }
        } else {
            showNotification(data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Eroare la upload. Încearcă din nou.', 'danger');
    });
});

// Dezactivare Cont
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    const password = document.getElementById('delete_password').value;
    const confirmed = document.getElementById('confirmDelete').checked;
    
    if (!password) {
        showNotification('Introdu parola pentru a continua.', 'danger');
        return;
    }
    
    if (!confirmed) {
        showNotification('Trebuie să confirmi dezactivarea contului.', 'danger');
        return;
    }
    
    const formData = new FormData();
    formData.append('password', password);
    formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
    
    fetch('<?php echo SITE_URL; ?>/ajax/delete_account.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => {
                window.location.href = data.redirect || '<?php echo SITE_URL; ?>';
            }, 2000);
        } else {
            showNotification(data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Eroare la dezactivare. Încearcă din nou.', 'danger');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


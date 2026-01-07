<?php
/**
 * Edit User
 * Editare detalii utilizator - nume, email, parolă, rol, status
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificare acces admin
if (!isAdmin()) {
    setMessage("Nu ai acces la această pagină.", "danger");
    redirect('/');
}

$db = getDB();

// Verificare ID utilizator
if (!isset($_GET['id'])) {
    setMessage("ID utilizator lipsă.", "danger");
    redirect('/admin/admin_users.php');
}

$userId = (int)$_GET['id'];

// Adăugăm coloana is_active dacă nu există
$db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1");

// Obține datele utilizatorului
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    setMessage("Utilizatorul nu a fost găsit.", "danger");
    redirect('/admin/admin_users.php');
}

// Statistici utilizator
$orderCount = $db->query("SELECT COUNT(*) as total FROM orders WHERE user_id = $userId")->fetch_assoc()['total'];
$totalSpent = $db->query("SELECT SUM(total_amount) as total FROM orders WHERE user_id = $userId AND payment_status = 'paid'")->fetch_assoc()['total'] ?? 0;
$lastOrder = $db->query("SELECT created_at FROM orders WHERE user_id = $userId ORDER BY created_at DESC LIMIT 1")->fetch_assoc();

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $newPassword = trim($_POST['password']);
    
    $errors = [];
    
    // Validări
    if (empty($firstName)) {
        $errors[] = "Prenumele este obligatoriu.";
    }
    
    if (empty($lastName)) {
        $errors[] = "Numele este obligatoriu.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalid.";
    }
    
    if (empty($username) || strlen($username) < 3) {
        $errors[] = "Username-ul trebuie să aibă minimum 3 caractere.";
    }
    
    // Verificare email duplicat
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email-ul este deja folosit de alt utilizator.";
    }
    
    // Verificare username duplicat
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username-ul este deja folosit de alt utilizator.";
    }
    
    // Validare parolă (dacă este completată)
    if (!empty($newPassword) && strlen($newPassword) < 6) {
        $errors[] = "Parola trebuie să aibă minimum 6 caractere.";
    }
    
    // Verificare: nu poate schimba propriul rol sau status
    if ($userId === $_SESSION['user_id']) {
        if ($role !== $user['role']) {
            $errors[] = "Nu poți schimba propriul rol.";
        }
        if ($isActive != $user['is_active']) {
            $errors[] = "Nu poți dezactiva propriul cont.";
        }
    }
    
    // Verificare: nu poate elimina ultimul admin
    if ($user['role'] === 'admin' && $role !== 'admin') {
        $adminCount = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'")->fetch_assoc()['total'];
        if ($adminCount <= 1) {
            $errors[] = "Nu poți schimba rolul ultimului administrator.";
        }
    }
    
    if (empty($errors)) {
        // Actualizare utilizator
        if (!empty($newPassword)) {
            // Cu parolă nouă
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, username = ?, phone = ?, role = ?, is_active = ?, password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssssssi", $firstName, $lastName, $email, $username, $phone, $role, $isActive, $hashedPassword, $userId);
        } else {
            // Fără parolă nouă
            $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, username = ?, phone = ?, role = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssssii", $firstName, $lastName, $email, $username, $phone, $role, $isActive, $userId);
        }
        
        if ($stmt->execute()) {
            setMessage("Utilizator actualizat cu succes!", "success");
            redirect('/admin/admin_users.php');
        } else {
            $errors[] = "Eroare la actualizarea utilizatorului.";
        }
    }
    
    // Afișare erori
    if (!empty($errors)) {
        foreach ($errors as $error) {
            setMessage($error, "danger");
        }
    }
}

// Include header DUPĂ procesarea POST
$pageTitle = "Editare Utilizator";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<section class="bg-dark text-white py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h2 fw-bold mb-0">
                    <i class="bi bi-person-badge me-2"></i>Editare Utilizator
                </h1>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="<?php echo SITE_URL; ?>/admin/admin_users.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-2"></i>Înapoi la Listă
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Edit Form -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 p-4">
                        <h5 class="fw-bold mb-0">Detalii Utilizator</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="row g-3">
                                <!-- Prenume -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        Prenume <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="first_name" class="form-control" required 
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                </div>
                                
                                <!-- Nume -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        Nume <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="last_name" class="form-control" required 
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                </div>
                                
                                <!-- Email -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" name="email" class="form-control" required 
                                           value="<?php echo htmlspecialchars($user['email']); ?>">
                                    <small class="text-muted">Trebuie să fie unic în sistem</small>
                                </div>
                                
                                <!-- Username -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        Username <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="username" class="form-control" required 
                                           value="<?php echo htmlspecialchars($user['username']); ?>">
                                    <small class="text-muted">Minim 3 caractere, trebuie să fie unic</small>
                                </div>
                                
                                <!-- Telefon -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Telefon</label>
                                    <input type="text" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                
                                <!-- Parolă Nouă -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Parolă Nouă</label>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="Lasă gol pentru a păstra parola actuală">
                                    <small class="text-muted">Minim 6 caractere (opțional)</small>
                                </div>
                                
                                <!-- Rol -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        Rol <span class="text-danger">*</span>
                                    </label>
                                    <select name="role" class="form-select" required
                                            <?php echo $userId === $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>
                                            Client
                                        </option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>
                                            Administrator
                                        </option>
                                    </select>
                                    <?php if ($userId === $_SESSION['user_id']): ?>
                                        <input type="hidden" name="role" value="<?php echo $user['role']; ?>">
                                        <small class="text-warning">Nu poți schimba propriul rol</small>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Status Cont -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Status Cont</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" 
                                               <?php echo $user['is_active'] ? 'checked' : ''; ?>
                                               <?php echo $userId === $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <label class="form-check-label" for="isActive">
                                            Cont activ
                                        </label>
                                    </div>
                                    <?php if ($userId === $_SESSION['user_id']): ?>
                                        <input type="hidden" name="is_active" value="1">
                                        <small class="text-warning d-block mt-1">Nu poți dezactiva propriul cont</small>
                                    <?php else: ?>
                                        <small class="text-muted">Conturile blocate nu pot accesa platforma</small>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Buttons -->
                                <div class="col-12 mt-4 pt-3 border-top">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-2"></i>Salvează Modificările
                                        </button>
                                        <a href="<?php echo SITE_URL; ?>/admin/admin_users.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-circle me-2"></i>Anulează
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar Info -->
            <div class="col-lg-4">
                <!-- User Info Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 p-4">
                        <h5 class="fw-bold mb-0">Informații Generale</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="text-muted small">ID Utilizator</label>
                            <div class="fw-semibold">#<?php echo $user['id']; ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-muted small">Înregistrat la</label>
                            <div class="fw-semibold">
                                <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                                <br>
                                <small class="text-muted"><?php echo date('H:i', strtotime($user['created_at'])); ?></small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-muted small">Ultima actualizare</label>
                            <div class="fw-semibold">
                                <?php echo date('d.m.Y', strtotime($user['updated_at'])); ?>
                                <br>
                                <small class="text-muted"><?php echo date('H:i', strtotime($user['updated_at'])); ?></small>
                            </div>
                        </div>
                        
                        <div class="mb-0">
                            <label class="text-muted small">Status actual</label>
                            <div>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Cont Activ
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-x-circle me-1"></i>Cont Blocat
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge bg-danger ms-1">
                                        <i class="bi bi-shield-fill-check me-1"></i>Admin
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 p-4">
                        <h5 class="fw-bold mb-0">Statistici Comenzi</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="text-muted small">Total comenzi</label>
                            <div class="h4 fw-bold mb-0"><?php echo $orderCount; ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-muted small">Valoare totală</label>
                            <div class="h5 fw-bold mb-0 text-success">
                                <?php echo number_format($totalSpent, 2); ?> LEI
                            </div>
                        </div>
                        
                        <?php if ($lastOrder): ?>
                        <div class="mb-0">
                            <label class="text-muted small">Ultima comandă</label>
                            <div class="fw-semibold">
                                <?php echo date('d.m.Y', strtotime($lastOrder['created_at'])); ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-3">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted small mb-0 mt-2">Nicio comandă plasată</p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($orderCount > 0): ?>
                        <div class="mt-3 pt-3 border-top">
                            <a href="<?php echo SITE_URL; ?>/admin/admin_orders.php?search=<?php echo urlencode($user['email']); ?>" 
                               class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-eye me-2"></i>Vezi toate comenzile
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

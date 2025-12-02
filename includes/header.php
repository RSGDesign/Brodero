<?php
/**
 * Header pentru site-ul Brodero
 * Include navigare, logo și iconițe pentru coș și cont
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificare coș
$cartCount = 0;
if (isLoggedIn()) {
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cartCount = $row['total'] ?? 0;
    $stmt->close();
} elseif (isset($_SESSION['session_id'])) {
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = ?");
    $stmt->bind_param("s", $_SESSION['session_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cartCount = $row['total'] ?? 0;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?> - Design de Broderie</title>
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Brodero - Magazine online de design-uri de broderie premium. Descoperă modele unice și creative.'; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>">
                <i class="bi bi-flower3 text-primary"></i>
                <span class="text-primary">Brodero</span>
            </a>
            
            <!-- Toggle button pentru mobil -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>">Acasă</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/despre.php">Despre Noi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/magazin.php">Magazin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/contact.php">Contact</a>
                    </li>
                </ul>
                
                <!-- Iconițe -->
                <div class="d-flex align-items-center gap-3">
                    <!-- Coș -->
                    <a href="<?php echo SITE_URL; ?>/pages/cos.php" class="position-relative text-decoration-none text-dark">
                        <i class="bi bi-cart3 fs-5"></i>
                        <?php if ($cartCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                <?php echo $cartCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Contul Meu -->
                    <?php if (isLoggedIn()): ?>
                        <div class="dropdown">
                            <a class="text-decoration-none text-dark dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle fs-5"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/cont.php">Contul Meu</a></li>
                                <?php if (isAdmin()): ?>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard Admin</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/logout.php">Deconectare</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/pages/login.php" class="text-decoration-none text-dark">
                            <i class="bi bi-person-circle fs-5"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Mesaje de notificare -->
    <?php
    $message = getMessage();
    if ($message):
    ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $message['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo $message['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main>

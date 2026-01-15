<?php
/**
 * Header pentru site-ul Brodero
 * Include navigare, logo și iconițe pentru coș și cont
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Generare CSRF token pentru formulare
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
    
    <!-- SEO Meta Tags -->
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Brodero - Magazine online de design-uri de broderie premium. Descoperă modele unice și creative pentru proiectele tale.'; ?>">
    <meta name="keywords" content="<?php echo isset($pageKeywords) ? htmlspecialchars($pageKeywords) : 'broderie, design broderie, modele broderie, broderie digitală, pattern broderie'; ?>">
    <meta name="author" content="Brodero">
    <meta name="robots" content="<?php echo isset($seoNoIndex) && $seoNoIndex ? 'noindex, follow' : 'index, follow'; ?>">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php 
        $currentUrl = SITE_URL . $_SERVER['REQUEST_URI'];
        // Curăță parametrii de tracking
        $currentUrl = strtok($currentUrl, '?');
        echo htmlspecialchars($currentUrl);
    ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($currentUrl); ?>">
    <meta property="og:title" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Brodero - Design-uri de broderie premium'; ?>">
    <meta property="og:image" content="<?php echo isset($pageImage) ? htmlspecialchars($pageImage) : SITE_URL . '/assets/images/og-default.jpg'; ?>">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    <meta property="og:locale" content="ro_RO">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo htmlspecialchars($currentUrl); ?>">
    <meta name="twitter:title" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo SITE_NAME; ?>">
    <meta name="twitter:description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Brodero - Design-uri de broderie premium'; ?>">
    <meta name="twitter:image" content="<?php echo isset($pageImage) ? htmlspecialchars($pageImage) : SITE_URL . '/assets/images/og-default.jpg'; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/favicon.ico">
    
    <!-- Preload font-uri critice pentru LCP -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    
    <!-- Critical CSS inline pentru fastest rendering -->
    <style><?php include(__DIR__ . '/../assets/css/critical.css'); ?></style>
    
    <!-- Bootstrap CSS (defer - elimină 900ms render blocking!) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></noscript>
    
    <!-- Custom CSS (defer - elimină 160ms render blocking!) -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css"></noscript>
    
    <!-- Performance CSS (defer) -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/performance.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/performance.css"></noscript>
    
    <!-- Bootstrap Icons (defer - non-critic) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"></noscript>
    
    <!-- Google Fonts (cu font-display swap) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
    
    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "<?php echo SITE_NAME; ?>",
      "url": "<?php echo SITE_URL; ?>",
      "logo": "<?php echo SITE_URL; ?>/assets/images/logo.png",
      "description": "Magazine online de design-uri de broderie premium",
      "address": {
        "@type": "PostalAddress",
        "addressCountry": "RO"
      }
    }
    </script>
    
    <?php
    // Google Analytics 4 Integration (GDPR Compliant)
    require_once __DIR__ . '/analytics.php';
    renderGA4Code();
    ?>
    
    <!-- Cookie Consent Banner Styles -->
    <style>
    #cookieConsentBanner {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        background: rgba(0, 0, 0, 0.95);
        color: white;
        padding: 15px 20px;
        z-index: 999999 !important;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
        transform: translateZ(0);
        -webkit-transform: translateZ(0);
    }
    #cookieConsentBanner.show { 
        display: block !important; 
        visibility: visible !important;
        opacity: 1 !important;
    }
    .cookie-consent-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
    }
    .cookie-consent-text { 
        flex: 1; 
        min-width: 200px; 
    }
    .cookie-consent-text p { 
        margin: 0; 
        font-size: 14px; 
        line-height: 1.5; 
    }
    .cookie-consent-buttons { 
        display: flex; 
        gap: 10px; 
        flex-wrap: wrap;
    }
    .cookie-consent-btn {
        padding: 10px 24px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s;
        white-space: nowrap;
    }
    .cookie-consent-btn.accept { background: #28a745; color: white; }
    .cookie-consent-btn.accept:hover { background: #218838; }
    .cookie-consent-btn.deny { background: #6c757d; color: white; }
    .cookie-consent-btn.deny:hover { background: #5a6268; }
    
    /* Mobile optimizations */
    @media (max-width: 768px) {
        #cookieConsentBanner {
            padding: 15px;
        }
        .cookie-consent-content { 
            flex-direction: column; 
            text-align: center; 
            gap: 12px;
        }
        .cookie-consent-text {
            min-width: 100%;
        }
        .cookie-consent-text p {
            font-size: 13px;
        }
        .cookie-consent-buttons { 
            width: 100%; 
            justify-content: center; 
            gap: 8px;
        }
        .cookie-consent-btn {
            flex: 1;
            min-width: 100px;
            padding: 12px 16px;
        }
    }
    
    @media (max-width: 480px) {
        .cookie-consent-buttons {
            flex-direction: column;
            width: 100%;
        }
        .cookie-consent-btn {
            width: 100%;
        }
    }
    </style>
    
    <div id="cookieConsentBanner">
        <div class="cookie-consent-content">
            <div class="cookie-consent-text">
                <p>🍪 Folosim cookies pentru a îmbunătăți experiența ta pe site și pentru a analiza traficul. Datele sunt anonimizate și procesate conform GDPR.</p>
            </div>
            <div class="cookie-consent-buttons">
                <button class="cookie-consent-btn deny" onclick="cookieConsent.deny()">Refuză</button>
                <button class="cookie-consent-btn accept" onclick="cookieConsent.accept()">Accept</button>
            </div>
        </div>
    </div>
    
    <script>
    const cookieConsent = {
        cookieName: 'cookie_consent',
        cookieExpireDays: 365,
        hasConsent: function() { return this.getCookie(this.cookieName) !== null; },
        getConsentStatus: function() { return this.getCookie(this.cookieName); },
        init: function() {
            if (!this.hasConsent()) {
                this.showBanner();
            } else if (this.getConsentStatus() === 'granted') {
                this.updateGAConsent('granted');
            }
        },
        showBanner: function() {
            const banner = document.getElementById('cookieConsentBanner');
            if (banner) banner.classList.add('show');
        },
        hideBanner: function() {
            const banner = document.getElementById('cookieConsentBanner');
            if (banner) banner.classList.remove('show');
        },
        accept: function() {
            this.setCookie(this.cookieName, 'granted', this.cookieExpireDays);
            this.hideBanner();
            this.updateGAConsent('granted');
            window.location.reload();
        },
        deny: function() {
            this.setCookie(this.cookieName, 'denied', this.cookieExpireDays);
            this.hideBanner();
            this.updateGAConsent('denied');
        },
        updateGAConsent: function(status) {
            if (typeof gtag === 'function') {
                gtag('consent', 'update', { 'analytics_storage': status });
            }
        },
        setCookie: function(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Lax';
        },
        getCookie: function(name) {
            const nameEQ = name + '=';
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }
    };
    document.addEventListener('DOMContentLoaded', function() { cookieConsent.init(); });
    </script>
</head>
<body>
    
    <!-- Cookie Consent Banner HTML -->
    <div id="cookieConsentBanner">
        <div class="cookie-consent-content">
            <div class="cookie-consent-text">
                <p>🍪 Folosim cookies pentru a îmbunătăți experiența ta pe site și pentru a analiza traficul. Datele sunt anonimizate și procesate conform GDPR.</p>
            </div>
            <div class="cookie-consent-buttons">
                <button class="cookie-consent-btn deny" onclick="cookieConsent.deny()">Refuză</button>
                <button class="cookie-consent-btn accept" onclick="cookieConsent.accept()">Accept</button>
            </div>
        </div>
    </div>
    
    <!-- Header with Navigation -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm" role="navigation" aria-label="Main navigation">
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
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/program-referral.php">
                            <i class="bi bi-gift me-1"></i>Program Referral
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/modele-la-comanda.php">
                            <i class="bi bi-palette me-1"></i>Modele la Comandă
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/contact.php">Contact</a>
                    </li>
                </ul>
                
                <!-- Iconițe -->
                <div class="d-flex align-items-center gap-3">
                    <!-- Coș -->
                    <a href="<?php echo SITE_URL; ?>/pages/cart.php" class="position-relative text-decoration-none text-dark">
                        <i class="bi bi-cart3 fs-5"></i>
                        <?php if ($cartCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark cart-count">
                                <?php echo $cartCount; ?>
                            </span>
                        <?php else: ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark cart-count" style="display: none;">
                                0
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
        </nav>
    </header>
    
    <!-- Mesaje de notificare -->
    <?php
    $message = getMessage();
    if ($message):
    ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $message['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo $message['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main id="main-content" role="main">

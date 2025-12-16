<?php
/**
 * Configurare principală aplicație Brodero
 * Definește constante și setări globale
 */

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVARE OUTPUT BUFFERING - CRITICAL!
// Previne eroarea "Cannot modify header information - headers already sent"
// 
// Output buffering captează tot conținutul HTML și îl trimite doar la final,
// permițând astfel apelarea funcției header() oriunde în cod.
// Această setare este esențială pentru redirect-uri și trimiterea de headere HTTP.
// ═══════════════════════════════════════════════════════════════════════════
if (!ob_get_level()) {
    ob_start();
}

// Pornire sesiune dacă nu este deja pornită
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurare bază de date
define('DB_HOST', 'localhost');
define('DB_USER', 'u107933880_brodero');
define('DB_PASS', 'Grasul1500!');
define('DB_NAME', 'u107933880_brodero');

// Configurare site
define('SITE_NAME', 'Brodero');
define('SITE_URL', 'https://brodero.online');
define('SITE_EMAIL', 'contact@brodero.online');
define('SITE_PHONE', '0741133343');

// Configurare paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('ASSETS_PATH', BASE_PATH . '/assets/');

// Creare directoare necesare
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!is_dir(UPLOAD_PATH . 'products/')) {
    mkdir(UPLOAD_PATH . 'products/', 0755, true);
}
if (!is_dir(UPLOAD_PATH . 'products/gallery/')) {
    mkdir(UPLOAD_PATH . 'products/gallery/', 0755, true);
}

// Configurare upload
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'zip']);

// Configurare pagination
define('PRODUCTS_PER_PAGE', 12);

// Configurare Stripe (opțional - dacă nu e instalat SDK-ul, plata cu card va fi dezactivată)
define('STRIPE_SECRET_KEY', 'de compșetat'); // Adaugă cheia ta Stripe aici când instalezi SDK
define('STRIPE_PUBLISHABLE_KEY', 'de completat'); // Pentru frontend

// Configurare social media
define('FACEBOOK_URL', 'https://www.facebook.com/Brodero2020/');
define('INSTAGRAM_URL', 'https://instagram.com/brodero2020');
define('TWITTER_URL', 'https://twitter.com/brodero');
define('PINTEREST_URL', 'https://pinterest.com/brodero');

// Setări PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Bucharest');

// Funcție pentru afișare mesaje
function setMessage($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'];
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Verificare dacă utilizatorul este autentificat
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Verificare dacă utilizatorul este admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirecționare
function redirect($url) {
    // CRITICAL: Save session before flushing output buffer
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    // Clean output buffer if exists
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header("Location: " . SITE_URL . $url);
    exit();
}

// Sanitizare input
function cleanInput($data) {
    // PHP 8.1+ compatibility: handle null values
    if ($data === null || $data === '') {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Include funcții pentru categorii many-to-many
if (file_exists(__DIR__ . '/../includes/category_functions.php')) {
    require_once __DIR__ . '/../includes/category_functions.php';
}

// ═══════════════════════════════════════════════════════════════════════════
// COMING SOON PROTECTION - Protecție pagină "Coming Soon"
// ═══════════════════════════════════════════════════════════════════════════
// Activează/Dezactivează modul "Coming Soon"
define('COMING_SOON_MODE', true); // Schimbă în false pentru a dezactiva protecția

// Data lansării (după această dată, modul se dezactivează automat)
define('LAUNCH_DATE', '2025-12-22 23:59:59');

/**
 * Verifică dacă utilizatorul curent poate accesa site-ul în modul "Coming Soon"
 * 
 * @return bool True dacă utilizatorul poate accesa, False dacă trebuie redirecționat
 */
function canAccessDuringComingSoon() {
    // Dacă modul "Coming Soon" este dezactivat, toată lumea poate accesa
    if (!COMING_SOON_MODE) {
        return true;
    }
    
    // Dacă am trecut de data lansării, toată lumea poate accesa
    $now = new DateTime();
    $launchDate = new DateTime(LAUNCH_DATE);
    if ($now >= $launchDate) {
        return true;
    }
    
    // Doar adminii logați pot accesa în modul "Coming Soon"
    return isAdmin();
}

/**
 * Aplică protecția "Coming Soon" - Redirecționează utilizatorii non-admin către coming-soon.html
 * Această funcție trebuie apelată la începutul fiecărei pagini
 */
function applyComingSoonProtection() {
    // Obține calea curentă
    $currentPath = $_SERVER['PHP_SELF'];
    $currentFile = basename($currentPath);
    
    // Lista fișierelor excluse de la redirecționare
    $excludedFiles = [
        'coming-soon.html',
        'login.php',
        'logout.php',
        'register.php'
    ];
    
    // Nu redirecționa dacă suntem deja pe o pagină exclusă
    if (in_array($currentFile, $excludedFiles)) {
        return;
    }
    
    // Nu redirecționa dacă e un request AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        return;
    }
    
    // Verifică dacă utilizatorul poate accesa site-ul
    if (!canAccessDuringComingSoon()) {
        // Salvează sesiunea
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Curăță output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Redirecționează către coming-soon.html
        header("Location: " . SITE_URL . "/coming-soon.html");
        exit();
    }
}

// Aplică automat protecția "Coming Soon" pentru toate paginile
// (Se execută doar dacă nu suntem în admin sau pe pagini excluse)
if (!defined('SKIP_COMING_SOON_CHECK')) {
    applyComingSoonProtection();
}

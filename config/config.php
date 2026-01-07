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

// ═══════════════════════════════════════════════════════════════════════════
// ÎNCĂRCARE CONFIGURARE SECURIZATĂ
// ═══════════════════════════════════════════════════════════════════════════
$configLocalPath = __DIR__ . '/../includes/config.local.php';

// Declarăm $localConfig ca global pentru a fi accesibil din funcții
global $localConfig;

if (file_exists($configLocalPath)) {
    // Încarcă configurarea locală (nu este în Git)
    $localConfig = require $configLocalPath;
} else {
    // Fallback pentru debugging - afișează eroare doar dacă debug e activ
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die('ERROR: config.local.php missing. Copy config.example.php to config.local.php and configure it.');
    }
    // Configurare fallback pentru a preveni crash-ul aplicației
    $localConfig = [
        'database' => [
            'host' => 'localhost',
            'user' => '',
            'password' => '',
            'name' => '',
        ],
        'stripe' => [
            'secret_key' => '',
            'publishable_key' => '',
        ],
        'analytics' => [
            'ga4_measurement_id' => '',
        ],
        'environment' => [
            'debug_mode' => false,
            'display_errors' => false,
        ],
    ];
}

// Configurare bază de date
define('DB_HOST', $localConfig['database']['host']);
define('DB_USER', $localConfig['database']['user']);
define('DB_PASS', $localConfig['database']['password']);
define('DB_NAME', $localConfig['database']['name']);

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
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'zip', 'emb']);

// Configurare pagination
define('PRODUCTS_PER_PAGE', 12);

// Configurare Stripe
define('STRIPE_SECRET_KEY', $localConfig['stripe']['secret_key'] ?? '');
define('STRIPE_PUBLISHABLE_KEY', $localConfig['stripe']['publishable_key'] ?? '');

// Configurare Google Analytics
define('GA4_MEASUREMENT_ID', $localConfig['analytics']['ga4_measurement_id'] ?? '');

// Configurare environment
define('DEBUG_MODE', $localConfig['environment']['debug_mode'] ?? false);

// Configurare social media
define('FACEBOOK_URL', 'https://www.facebook.com/Brodero2020/');
define('INSTAGRAM_URL', 'https://instagram.com/brodero2020');
define('TWITTER_URL', 'https://twitter.com/brodero');
define('PINTEREST_URL', 'https://pinterest.com/brodero');

// Setări PHP - folosește valorile din config
error_reporting($localConfig['environment']['debug_mode'] ? E_ALL : E_ALL & ~E_NOTICE);
ini_set('display_errors', $localConfig['environment']['display_errors'] ? '1' : '0');
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
    
    // Check if URL is already absolute (starts with http:// or https://)
    if (preg_match('/^https?:\/\//', $url)) {
        header("Location: " . $url);
    } else {
        header("Location: " . SITE_URL . $url);
    }
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
define('LAUNCH_DATE', '2025-12-22 00:00:00');

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

/**
 * Verifică dacă utilizatorul curent a cumpărat deja un produs
 * MVP: bazat pe comenzi plătite cu status 'paid' sau 'completed'
 * 
 * @param int $productId ID-ul produsului de verificat
 * @return bool True dacă produsul a fost cumpărat, false altfel
 */
function hasUserPurchasedProduct($productId) {
    // Verificare utilizator autentificat
    if (!isLoggedIn()) {
        return false;
    }
    
    $userId = $_SESSION['user_id'];
    $db = getDB();
    
    // Query: verifică dacă există comenzi plătite care conțin produsul
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ? 
        AND oi.product_id = ?
        AND o.status IN ('paid', 'completed')
        AND o.payment_status = 'paid'
    ");
    
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['count'] > 0;
}

/**
 * Verifică dacă o cheie secretă este configurată
 * 
 * @param string $key Numele constantei (ex: 'STRIPE_SECRET_KEY')
 * @return bool
 */
function hasSecretKey($key) {
    return defined($key) && !empty(constant($key)) && constant($key) !== 'de completat';
}

/**
 * Obține configurația locală (pentru funcții care au nevoie de mai multe valori)
 * 
 * @return array
 */
function getLocalConfig() {
    global $localConfig;
    return $localConfig ?? [];
}

// Aplică automat protecția "Coming Soon" pentru toate paginile
// (Se execută doar dacă nu suntem în admin sau pe pagini excluse)
if (!defined('SKIP_COMING_SOON_CHECK')) {
    applyComingSoonProtection();
}

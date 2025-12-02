<?php
/**
 * Configurare principală aplicație Brodero
 * Definește constante și setări globale
 */

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

// Configurare upload
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'zip']);

// Configurare pagination
define('PRODUCTS_PER_PAGE', 12);

// Configurare social media
define('FACEBOOK_URL', 'https://facebook.com/brodero');
define('INSTAGRAM_URL', 'https://instagram.com/brodero');
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
    header("Location: " . SITE_URL . $url);
    exit();
}

// Sanitizare input
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

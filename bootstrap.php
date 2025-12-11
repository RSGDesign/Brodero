<?php
/**
 * Brodero - Bootstrap Autoloader
 * 
 * Include acest fișier din ORICE script PHP pentru a încărca:
 * - Composer autoload (PHPMailer, Stripe, etc.)
 * - Configurații (config.php, database.php, smtp_config.php)
 * 
 * UTILIZARE:
 * require_once __DIR__ . '/bootstrap.php';
 * 
 * SAU din subdirectoare:
 * require_once __DIR__ . '/../bootstrap.php';
 * require_once __DIR__ . '/../../bootstrap.php';
 */

// ================================================================
// 1. DETECTARE DIRECTOR RĂDĂCINĂ
// ================================================================

/**
 * Găsește directorul rădăcină al proiectului
 * (directorul care conține vendor/ și config/)
 */
function findProjectRoot($startPath = __DIR__) {
    $currentPath = $startPath;
    $maxLevels = 10; // Prevent infinite loop
    
    for ($i = 0; $i < $maxLevels; $i++) {
        // Verifică dacă am găsit rădăcina (conține vendor/ și config/)
        if (is_dir($currentPath . '/vendor') && is_dir($currentPath . '/config')) {
            return $currentPath;
        }
        
        // Urcă un nivel
        $parentPath = dirname($currentPath);
        
        // Dacă am ajuns la root filesystem, oprim
        if ($parentPath === $currentPath) {
            break;
        }
        
        $currentPath = $parentPath;
    }
    
    return false;
}

// Găsește rădăcina proiectului
$PROJECT_ROOT = findProjectRoot(__DIR__);

if (!$PROJECT_ROOT) {
    die("ERROR: Could not find project root directory (vendor/ and config/ folders)!");
}

// Definește constanta globală
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', $PROJECT_ROOT);
}

// ================================================================
// 2. ÎNCĂRCARE COMPOSER AUTOLOAD
// ================================================================

$autoloadPath = PROJECT_ROOT . '/vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    $error = "ERROR: Composer autoload not found!\n";
    $error .= "Expected: $autoloadPath\n";
    $error .= "Solution: Run 'composer install' or 'composer update' in: " . PROJECT_ROOT . "\n";
    die($error);
}

require_once $autoloadPath;

// ================================================================
// 3. VERIFICARE PHPMAILER
// ================================================================

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $error = "ERROR: PHPMailer class not found!\n";
    $error .= "Composer autoload loaded from: $autoloadPath\n";
    $error .= "Solution: Run 'composer require phpmailer/phpmailer' in: " . PROJECT_ROOT . "\n";
    die($error);
}

// ================================================================
// 4. ÎNCĂRCARE CONFIGURAȚII
// ================================================================

// Config principal
$configPath = PROJECT_ROOT . '/config/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    die("ERROR: Config file not found: $configPath");
}

// Database
$databasePath = PROJECT_ROOT . '/config/database.php';
if (file_exists($databasePath)) {
    require_once $databasePath;
}

// SMTP Config (opțional - poate să nu existe în dev)
$smtpConfigPath = PROJECT_ROOT . '/config/smtp_config.php';
if (file_exists($smtpConfigPath)) {
    require_once $smtpConfigPath;
}

// ================================================================
// 5. HELPER FUNCTIONS
// ================================================================

/**
 * Include un fișier relativ la rădăcina proiectului
 * @param string $relativePath Calea relativă (ex: 'includes/functions.php')
 * @return bool True dacă fișierul a fost inclus cu succes
 */
function includeProjectFile($relativePath) {
    $fullPath = PROJECT_ROOT . '/' . ltrim($relativePath, '/');
    
    if (file_exists($fullPath)) {
        require_once $fullPath;
        return true;
    }
    
    return false;
}

/**
 * Obține calea absolută a unui fișier din proiect
 * @param string $relativePath Calea relativă
 * @return string Calea absolută
 */
function getProjectPath($relativePath = '') {
    if (empty($relativePath)) {
        return PROJECT_ROOT;
    }
    
    return PROJECT_ROOT . '/' . ltrim($relativePath, '/');
}

// ================================================================
// 6. FUNCȚII LOGGING (dacă SMTP config nu este încărcat încă)
// ================================================================

if (!function_exists('logMail')) {
    /**
     * Log pentru operațiuni email
     * @param string $message Mesajul de log
     * @param string $level Nivel: INFO, SUCCESS, WARNING, ERROR, DEBUG
     */
    function logMail($message, $level = 'INFO') {
        if (!defined('MAIL_LOG_ENABLED') || !MAIL_LOG_ENABLED) {
            return;
        }
        
        $logFile = defined('MAIL_LOG_FILE') ? MAIL_LOG_FILE : PROJECT_ROOT . '/logs/mail.log';
        $logDir = dirname($logFile);
        
        // Creează director dacă nu există
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $logEntry = "[$timestamp] [$level] [$ip] $message" . PHP_EOL;
        
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// ================================================================
// MESAJ SUCCESS (doar în development)
// ================================================================

if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    // Debug info - doar în dev
    if (php_sapi_name() === 'cli') {
        echo "✅ Bootstrap loaded successfully\n";
        echo "   Project Root: " . PROJECT_ROOT . "\n";
        echo "   PHPMailer: " . (class_exists('PHPMailer\PHPMailer\PHPMailer') ? 'LOADED' : 'NOT LOADED') . "\n";
    }
}

// ================================================================
// RETURN PROJECT ROOT (pentru uz extern)
// ================================================================

return PROJECT_ROOT;

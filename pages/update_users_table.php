<?php
/**
 * Script de actualizare tabel users cu câmpuri noi pentru profil
 * Rulează acest script o singură dată pentru a adăuga câmpurile noi
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificare admin (opțional - comentează dacă rulezi manual)
// if (!isAdmin()) die('Access denied');

$db = getDB();

echo "<h2>Actualizare tabel users - START</h2><br>";

// Array cu toate coloanele de adăugat
$columns = [
    "email_temp VARCHAR(100) DEFAULT NULL COMMENT 'Email temporar pentru verificare'",
    "country VARCHAR(100) DEFAULT NULL COMMENT 'Țara utilizatorului'",
    "city VARCHAR(100) DEFAULT NULL COMMENT 'Orașul utilizatorului'",
    "avatar VARCHAR(255) DEFAULT NULL COMMENT 'Calea către avatar'",
    "newsletter TINYINT(1) DEFAULT 1 COMMENT 'Abonat newsletter'",
    "notifications TINYINT(1) DEFAULT 1 COMMENT 'Notificări email activate'",
    "last_login TIMESTAMP NULL DEFAULT NULL COMMENT 'Ultima autentificare'",
    "2fa_secret VARCHAR(32) DEFAULT NULL COMMENT 'Secret pentru 2FA'",
    "2fa_enabled TINYINT(1) DEFAULT 0 COMMENT '2FA activat'",
    "deleted_account TINYINT(1) DEFAULT 0 COMMENT 'Cont dezactivat'",
    "deleted_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Data dezactivării contului'"
];

foreach ($columns as $column) {
    $columnName = explode(' ', $column)[0];
    
    // Verifică dacă coloana există deja
    $checkQuery = "SHOW COLUMNS FROM users LIKE '$columnName'";
    $result = $db->query($checkQuery);
    
    if ($result->num_rows == 0) {
        // Coloana nu există, o adăugăm
        $alterQuery = "ALTER TABLE users ADD COLUMN $column";
        if ($db->query($alterQuery)) {
            echo "✅ Coloană adăugată: <strong>$columnName</strong><br>";
        } else {
            echo "❌ Eroare la adăugarea coloanei <strong>$columnName</strong>: " . $db->error . "<br>";
        }
    } else {
        echo "ℹ️ Coloană existentă: <strong>$columnName</strong> (skip)<br>";
    }
}

echo "<br><h2>Actualizare COMPLETĂ!</h2>";
echo "<p><a href='../index.php'>Înapoi la site</a></p>";
?>

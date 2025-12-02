<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();

$queries = [
    "ALTER TABLE orders ADD COLUMN customer_name VARCHAR(255) AFTER user_id",
    "ALTER TABLE orders ADD COLUMN customer_email VARCHAR(255) AFTER customer_name",
    "ALTER TABLE orders ADD COLUMN customer_phone VARCHAR(20) AFTER customer_email",
    "ALTER TABLE orders ADD COLUMN shipping_address TEXT AFTER customer_phone",
    "ALTER TABLE orders ADD COLUMN stripe_session_id VARCHAR(255) AFTER payment_method"
];

echo "<h3>Actualizare structură bază de date...</h3>";

foreach ($queries as $sql) {
    try {
        if ($db->query($sql) === TRUE) {
            echo "<div style='color:green'>SUCCES: $sql</div>";
        } else {
            // Ignorăm eroarea dacă coloana există deja (Duplicate column name)
            if ($db->errno == 1060) {
                echo "<div style='color:orange'>INFO: Coloana există deja (" . $db->error . ")</div>";
            } else {
                echo "<div style='color:red'>EROARE: " . $db->error . "</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div style='color:red'>EXCEPȚIE: " . $e->getMessage() . "</div>";
    }
}

echo "<br><b>Actualizare finalizată!</b> Acum poți șterge acest fișier.";
?>
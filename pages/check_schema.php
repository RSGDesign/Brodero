<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$result = $db->query("SHOW COLUMNS FROM orders");

echo "<h3>Structura tabelului 'orders':</h3><ul>";
while ($row = $result->fetch_assoc()) {
    echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
}
echo "</ul>";
?>
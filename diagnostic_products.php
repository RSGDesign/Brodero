<?php
/**
 * DIAGNOSTIC SCRIPT - Check Product Duplicates
 * Run this to see if products are being duplicated in the database
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();

echo "<h1>🔍 Diagnostic Products Duplicates</h1>";
echo "<style>table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background:#4CAF50;color:white;}</style>";

// Check 1: Total products
$total = $db->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
echo "<h2>Total produse în baza de date: <strong>$total</strong></h2>";

// Check 2: Unique names
$uniqueNames = $db->query("SELECT COUNT(DISTINCT name) as unique_names FROM products")->fetch_assoc()['unique_names'];
echo "<h3>Nume unice: <strong>$uniqueNames</strong></h3>";

if ($uniqueNames < $total) {
    echo "<p style='color:red;'>⚠️ <strong>ATENȚIE:</strong> Există produse duplicate! ($total produse dar doar $uniqueNames nume unice)</p>";
} else {
    echo "<p style='color:green;'>✅ Nu există duplicate (fiecare produs are nume unic)</p>";
}

// Check 3: Show duplicates
echo "<h2>Produse Duplicate:</h2>";
$duplicates = $db->query("
    SELECT name, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
    FROM products 
    GROUP BY name 
    HAVING COUNT(*) > 1
");

if ($duplicates->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Nume Produs</th><th>Număr duplicate</th><th>IDs</th></tr>";
    while ($row = $duplicates->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['name']}</td>";
        echo "<td style='color:red;font-weight:bold;'>{$row['count']}</td>";
        echo "<td>{$row['ids']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:green;'>✅ Nu există produse duplicate</p>";
}

// Check 4: Recent products
echo "<h2>Ultimele 10 produse adăugate:</h2>";
$recent = $db->query("SELECT id, name, slug, created_at FROM products ORDER BY id DESC LIMIT 10");

echo "<table>";
echo "<tr><th>ID</th><th>Nume</th><th>Slug</th><th>Data creare</th></tr>";
while ($row = $recent->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$row['slug']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check 5: Check for triggers
echo "<h2>MySQL Triggers pe tabela products:</h2>";
$triggers = $db->query("SHOW TRIGGERS WHERE `Table` = 'products'");

if ($triggers->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Trigger</th><th>Event</th><th>Timing</th></tr>";
    while ($row = $triggers->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='color:orange;font-weight:bold;'>{$row['Trigger']}</td>";
        echo "<td>{$row['Event']}</td>";
        echo "<td>{$row['Timing']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color:orange;'>⚠️ <strong>ATENȚIE:</strong> Există trigger-e pe tabela products! Acestea pot cauza probleme.</p>";
} else {
    echo "<p style='color:green;'>✅ Nu există trigger-e pe tabela products</p>";
}

// Check 6: Detailed view of all products
echo "<h2>Toate produsele (detaliat):</h2>";
$all = $db->query("SELECT id, name, slug, price, created_at FROM products ORDER BY id ASC");

if ($all->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Nume</th><th>Slug</th><th>Preț</th><th>Data creare</th></tr>";
    while ($row = $all->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['slug']}</td>";
        echo "<td>{$row['price']} RON</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Nu există produse în baza de date.</p>";
}

// Instructions
echo "<hr>";
echo "<h2>📋 Instrucțiuni:</h2>";
echo "<ol>";
echo "<li><strong>Șterge toate produsele</strong> din Admin Panel</li>";
echo "<li><strong>Refresh această pagină</strong> - ar trebui să arate 0 produse</li>";
echo "<li><strong>Adaugă UN produs nou</strong> din Admin Panel</li>";
echo "<li><strong>Refresh această pagină din nou</strong></li>";
echo "<li><strong>Verifică:</strong> Ar trebui să vezi DOAR 1 produs, nu mai multe</li>";
echo "</ol>";
echo "<p><a href='?' style='padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;'>🔄 Refresh Diagnostic</a></p>";
?>

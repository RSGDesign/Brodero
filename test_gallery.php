<?php
// Test pentru verificare galerie
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 1;

$result = $db->query("SELECT id, name, image, gallery FROM products WHERE id = $productId");
$product = $result->fetch_assoc();

echo "<h1>Debug Galerie Produs #" . $productId . "</h1>";
echo "<h2>" . htmlspecialchars($product['name']) . "</h2>";

echo "<h3>Imagine principală:</h3>";
echo "<pre>" . htmlspecialchars($product['image']) . "</pre>";
if ($product['image']) {
    echo "<img src='" . SITE_URL . "/uploads/" . $product['image'] . "' style='max-width: 300px;'><br>";
}

echo "<h3>Gallery (RAW):</h3>";
echo "<pre>" . htmlspecialchars($product['gallery']) . "</pre>";

echo "<h3>Gallery (Decoded):</h3>";
$gallery = json_decode($product['gallery'], true);
echo "<pre>";
print_r($gallery);
echo "</pre>";

if (is_array($gallery)) {
    echo "<h3>Imagini din galerie:</h3>";
    foreach ($gallery as $img) {
        echo "<p>Path: " . htmlspecialchars($img) . "</p>";
        echo "<img src='" . SITE_URL . "/uploads/" . $img . "' style='max-width: 200px; margin: 10px;'><br>";
    }
}

echo "<h3>Array complet (principală + galerie):</h3>";
$allImages = [];
if (!empty($product['image'])) {
    $allImages[] = $product['image'];
}
if (is_array($gallery)) {
    foreach ($gallery as $img) {
        $allImages[] = $img;
    }
}
echo "<pre>";
print_r($allImages);
echo "</pre>";

echo "<h3>Thumbails finale:</h3>";
foreach ($allImages as $index => $img) {
    echo "<div style='display: inline-block; margin: 10px;'>";
    echo "<p>Index: $index</p>";
    echo "<img src='" . SITE_URL . "/uploads/" . $img . "' style='width: 100px; height: 100px; object-fit: cover; border: 2px solid #ccc;'>";
    echo "</div>";
}
?>

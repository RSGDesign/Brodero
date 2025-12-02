<?php
/**
 * Script pentru curățare path-uri galerie duplicat
 * Rulează o singură dată pentru a corecta path-urile existente
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();

// Obține toate produsele cu galerie
$result = $db->query("SELECT id, name, gallery FROM products WHERE gallery IS NOT NULL AND gallery != ''");

$fixed = 0;
$errors = 0;

echo "<h1>Curățare Path-uri Galerie</h1>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Produs</th><th>Înainte</th><th>După</th><th>Status</th></tr>";

while ($product = $result->fetch_assoc()) {
    $oldGallery = $product['gallery'];
    $galleryArray = json_decode($oldGallery, true);
    
    if (is_array($galleryArray)) {
        $newGalleryArray = [];
        $hasChanges = false;
        
        foreach ($galleryArray as $imgPath) {
            // Verifică dacă path-ul conține duplicat "gallery/products/gallery/"
            if (strpos($imgPath, 'gallery/products/gallery/') === 0) {
                // Elimină primul "gallery/"
                $correctedPath = str_replace('gallery/products/gallery/', 'products/gallery/', $imgPath);
                $newGalleryArray[] = $correctedPath;
                $hasChanges = true;
            } else {
                $newGalleryArray[] = $imgPath;
            }
        }
        
        if ($hasChanges) {
            $newGallery = json_encode($newGalleryArray);
            
            // Update în baza de date
            $stmt = $db->prepare("UPDATE products SET gallery = ? WHERE id = ?");
            $stmt->bind_param("si", $newGallery, $product['id']);
            
            if ($stmt->execute()) {
                echo "<tr>";
                echo "<td>{$product['id']}</td>";
                echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                echo "<td><pre>" . htmlspecialchars($oldGallery) . "</pre></td>";
                echo "<td><pre>" . htmlspecialchars($newGallery) . "</pre></td>";
                echo "<td style='color: green;'>✓ Corectat</td>";
                echo "</tr>";
                $fixed++;
            } else {
                echo "<tr>";
                echo "<td>{$product['id']}</td>";
                echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                echo "<td colspan='2'>-</td>";
                echo "<td style='color: red;'>✗ Eroare: " . $stmt->error . "</td>";
                echo "</tr>";
                $errors++;
            }
            $stmt->close();
        } else {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td colspan='2'><pre>" . htmlspecialchars($oldGallery) . "</pre></td>";
            echo "<td style='color: blue;'>- OK (fără modificări)</td>";
            echo "</tr>";
        }
    }
}

echo "</table>";
echo "<h2>Rezultate:</h2>";
echo "<p><strong>Produse corectate:</strong> $fixed</p>";
echo "<p><strong>Erori:</strong> $errors</p>";

if ($fixed > 0) {
    echo "<p style='color: green; font-size: 18px;'><strong>✓ Path-urile au fost corectate! Acum galeria ar trebui să funcționeze.</strong></p>";
    echo "<p><a href='pages/produs.php?id=1'>Testează pagina produs</a></p>";
} else {
    echo "<p style='color: blue;'>Nicio corectare necesară - toate path-urile sunt deja corecte.</p>";
}

echo "<hr>";
echo "<p><strong>IMPORTANT:</strong> După ce ai verificat că totul funcționează, șterge acest fișier (fix_gallery_paths.php) din siguranță.</p>";
?>

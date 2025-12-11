<?php
/**
 * Script de Migrare: Generare Slug-uri pentru Produse Existente
 * Rulează acest script o singură dată pentru a genera slug-uri unice pentru toate produsele
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();

echo "═══════════════════════════════════════════════════════════════\n";
echo "  MIGRARE: Generare Slug-uri Unice pentru Produse\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

/**
 * Funcție pentru generare slug
 */
function generateSlug($text) {
    $text = strtolower($text);
    $text = str_replace(['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'], ['a', 'a', 'i', 's', 't', 'a', 'a', 'i', 's', 't'], $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * Funcție pentru generare slug unic
 */
function generateUniqueSlug($db, $text, $exclude_id = null, $usedSlugs = []) {
    $slug = generateSlug($text);
    $originalSlug = $slug;
    $counter = 1;
    
    while (true) {
        // Verifică în array-ul local (pentru batch updates)
        if (in_array($slug, $usedSlugs)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
            continue;
        }
        
        // Verifică în baza de date
        if ($exclude_id) {
            $stmt = $db->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
            $stmt->bind_param("si", $slug, $exclude_id);
        } else {
            $stmt = $db->prepare("SELECT id FROM products WHERE slug = ?");
            $stmt->bind_param("s", $slug);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            break;
        }
        
        $stmt->close();
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

try {
    // STEP 1: Obține toate produsele cu slug gol sau NULL
    echo "STEP 1: Căutare produse fără slug...\n";
    $result = $db->query("SELECT id, name, slug FROM products WHERE slug IS NULL OR slug = ''");
    $products = $result->fetch_all(MYSQLI_ASSOC);
    
    $totalProducts = count($products);
    echo "✓ Găsite $totalProducts produse fără slug\n\n";
    
    if ($totalProducts === 0) {
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  ✅ TOATE PRODUSELE AU DEJA SLUG-URI!\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        exit(0);
    }
    
    // STEP 2: Generare și actualizare slug-uri
    echo "STEP 2: Generare slug-uri unice...\n";
    $updated = 0;
    $errors = 0;
    $usedSlugs = [];
    
    foreach ($products as $product) {
        $productId = $product['id'];
        $productName = $product['name'];
        
        try {
            // Generare slug unic
            $slug = generateUniqueSlug($db, $productName, $productId, $usedSlugs);
            $usedSlugs[] = $slug;
            
            // Actualizare în baza de date
            $stmt = $db->prepare("UPDATE products SET slug = ? WHERE id = ?");
            $stmt->bind_param("si", $slug, $productId);
            
            if ($stmt->execute()) {
                $updated++;
                echo "  ✓ ID $productId: '$productName' → '$slug'\n";
            } else {
                $errors++;
                echo "  ✗ ID $productId: EROARE - " . $stmt->error . "\n";
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $errors++;
            echo "  ✗ ID $productId: EXCEPȚIE - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  REZUMAT MIGRARE\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  Total produse procesate: $totalProducts\n";
    echo "  ✓ Actualizate cu succes:  $updated\n";
    echo "  ✗ Erori:                  $errors\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    
    if ($errors === 0) {
        echo "\n✅ MIGRARE COMPLETATĂ CU SUCCES!\n";
        echo "Toate produsele au acum slug-uri unice.\n\n";
    } else {
        echo "\n⚠️  MIGRARE COMPLETATĂ CU ERORI!\n";
        echo "Verifică produsele cu erori și rerulează scriptul.\n\n";
    }
    
    // STEP 3: Verificare finală
    echo "STEP 3: Verificare finală...\n";
    $checkResult = $db->query("SELECT COUNT(*) as cnt FROM products WHERE slug IS NULL OR slug = ''");
    $remaining = $checkResult->fetch_assoc()['cnt'];
    
    if ($remaining > 0) {
        echo "⚠️  Atenție: Încă există $remaining produse fără slug!\n";
    } else {
        echo "✓ Toate produsele au slug-uri valide!\n";
    }
    
    // Verificare duplicates
    $duplicatesResult = $db->query("
        SELECT slug, COUNT(*) as cnt 
        FROM products 
        WHERE slug != '' 
        GROUP BY slug 
        HAVING cnt > 1
    ");
    
    if ($duplicatesResult->num_rows > 0) {
        echo "\n⚠️  ATENȚIE: Slug-uri duplicate detectate:\n";
        while ($dup = $duplicatesResult->fetch_assoc()) {
            echo "  - '{$dup['slug']}' (apare de {$dup['cnt']} ori)\n";
        }
    } else {
        echo "✓ Nu există slug-uri duplicate!\n";
    }
    
} catch (Exception $e) {
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "  ❌ EROARE CRITICĂ\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n";
?>

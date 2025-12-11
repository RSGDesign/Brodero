<?php
/**
 * Migrare Many-to-Many pentru Categorii Produse
 * Creează tabel product_categories și migrează datele existente
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();

echo "=== MIGRARE CATEGORII MANY-TO-MANY ===\n\n";

// Pas 1: Creează tabelul product_categories
echo "1. Creare tabel product_categories...\n";

$createTable = "CREATE TABLE IF NOT EXISTS product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_category (product_id, category_id),
    INDEX idx_product (product_id),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($db->query($createTable)) {
    echo "   ✅ Tabel product_categories creat cu succes!\n";
} else {
    echo "   ⚠️ Eroare la crearea tabelului: " . $db->error . "\n";
}

// Pas 2: Migrează datele existente din products.category_id
echo "\n2. Migrare date existente...\n";

$products = $db->query("SELECT id, category_id FROM products WHERE category_id IS NOT NULL AND category_id > 0");

if ($products) {
    $migrated = 0;
    $errors = 0;
    
    while ($product = $products->fetch_assoc()) {
        $stmt = $db->prepare("INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $product['id'], $product['category_id']);
        
        if ($stmt->execute()) {
            $migrated++;
        } else {
            $errors++;
            echo "   ⚠️ Eroare la migrarea produsului {$product['id']}: " . $stmt->error . "\n";
        }
    }
    
    echo "   ✅ Migrat $migrated produse\n";
    if ($errors > 0) {
        echo "   ⚠️ $errors erori\n";
    }
} else {
    echo "   ⚠️ Nu s-au putut citi produsele: " . $db->error . "\n";
}

// Pas 3: Verificare
echo "\n3. Verificare date migrate...\n";

$check = $db->query("SELECT COUNT(*) as total FROM product_categories");
$total = $check->fetch_assoc()['total'];
echo "   📊 Total relații în product_categories: $total\n";

$checkProducts = $db->query("SELECT COUNT(DISTINCT product_id) as total FROM product_categories");
$totalProducts = $checkProducts->fetch_assoc()['total'];
echo "   📊 Produse cu categorii: $totalProducts\n";

// Pas 4: Informații despre category_id
echo "\n4. Informații despre coloana category_id...\n";
echo "   ℹ️ Coloana products.category_id NU va fi ștearsă (pentru compatibilitate)\n";
echo "   ℹ️ Sistemul va folosi tabelul product_categories de acum înainte\n";
echo "   ℹ️ Poți șterge manual coloana category_id mai târziu dacă dorești\n";

echo "\n=== MIGRARE COMPLETĂ! ===\n";
echo "\nPașii următori:\n";
echo "1. Testează adăugarea/editarea produselor în admin\n";
echo "2. Testează filtrarea pe categorii în magazin\n";
echo "3. Verifică afișarea produselor\n";
echo "4. După teste, poți rula: ALTER TABLE products DROP COLUMN category_id;\n";

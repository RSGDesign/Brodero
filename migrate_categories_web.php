<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrare Many-to-Many Categorii</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #2196F3;
            border-radius: 5px;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .error {
            color: #f44336;
            font-weight: bold;
        }
        .info {
            color: #2196F3;
            font-weight: bold;
        }
        .warning {
            color: #FF9800;
            font-weight: bold;
        }
        .code {
            background: #263238;
            color: #00ff00;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 10px 0;
        }
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }
        button:hover {
            background: #45a049;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .result {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Migrare Many-to-Many pentru Categorii</h1>
        
        <?php
        require_once __DIR__ . '/config/config.php';
        require_once __DIR__ . '/config/database.php';
        
        $migrationRun = false;
        $errors = [];
        $success = [];
        $warnings = [];
        
        if (isset($_POST['run_migration'])) {
            $migrationRun = true;
            $db = getDB();
            
            echo '<div class="result">';
            
            // Pas 1: Verifică dacă tabelul există deja
            echo '<div class="step">';
            echo '<h3>Pas 1: Verificare tabel existent</h3>';
            $checkTable = $db->query("SHOW TABLES LIKE 'product_categories'");
            if ($checkTable->num_rows > 0) {
                echo '<p class="warning">⚠️ Tabelul product_categories există deja.</p>';
                $countExisting = $db->query("SELECT COUNT(*) as total FROM product_categories")->fetch_assoc()['total'];
                echo "<p class='info'>📊 Există deja $countExisting relații în tabel.</p>";
                $warnings[] = "Tabelul există - se va încerca să adauge doar date lipsă.";
            } else {
                echo '<p class="info">ℹ️ Tabelul nu există - va fi creat.</p>';
            }
            echo '</div>';
            
            // Pas 2: Creează tabelul
            echo '<div class="step">';
            echo '<h3>Pas 2: Creare tabel product_categories</h3>';
            
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
                echo '<p class="success">✅ Tabel creat/verificat cu succes!</p>';
                $success[] = "Tabel product_categories creat.";
            } else {
                echo '<p class="error">❌ Eroare: ' . $db->error . '</p>';
                $errors[] = "Eroare la crearea tabelului: " . $db->error;
            }
            echo '</div>';
            
            // Pas 3: Migrare date
            if (empty($errors)) {
                echo '<div class="step">';
                echo '<h3>Pas 3: Migrare date existente</h3>';
                
                // Verifică dacă coloana category_id există în products
                $checkColumn = $db->query("SHOW COLUMNS FROM products LIKE 'category_id'");
                
                if ($checkColumn->num_rows > 0) {
                    echo '<p class="info">ℹ️ Coloana category_id găsită în products.</p>';
                    
                    $products = $db->query("SELECT id, category_id FROM products WHERE category_id IS NOT NULL AND category_id > 0");
                    
                    if ($products) {
                        $migrated = 0;
                        $skipped = 0;
                        $migrationErrors = 0;
                        
                        while ($product = $products->fetch_assoc()) {
                            $stmt = $db->prepare("INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)");
                            $stmt->bind_param("ii", $product['id'], $product['category_id']);
                            
                            if ($stmt->execute()) {
                                if ($stmt->affected_rows > 0) {
                                    $migrated++;
                                } else {
                                    $skipped++;
                                }
                            } else {
                                $migrationErrors++;
                            }
                        }
                        
                        echo "<p class='success'>✅ Migrat cu succes: $migrated produse</p>";
                        if ($skipped > 0) {
                            echo "<p class='info'>ℹ️ Sărit (duplicate): $skipped produse</p>";
                        }
                        if ($migrationErrors > 0) {
                            echo "<p class='error'>❌ Erori: $migrationErrors produse</p>";
                        }
                        
                        $success[] = "Date migrate: $migrated produse.";
                    } else {
                        echo '<p class="error">❌ Nu s-au putut citi produsele: ' . $db->error . '</p>';
                        $errors[] = "Eroare la citirea produselor.";
                    }
                } else {
                    echo '<p class="warning">⚠️ Coloana category_id nu există în products - migrare omisă.</p>';
                    $warnings[] = "Coloana category_id lipsește - probabil a fost ștearsă.";
                }
                echo '</div>';
            }
            
            // Pas 4: Verificare finală
            echo '<div class="step">';
            echo '<h3>Pas 4: Verificare finală</h3>';
            
            $totalRelations = $db->query("SELECT COUNT(*) as total FROM product_categories")->fetch_assoc()['total'];
            $totalProducts = $db->query("SELECT COUNT(DISTINCT product_id) as total FROM product_categories")->fetch_assoc()['total'];
            $totalCategories = $db->query("SELECT COUNT(DISTINCT category_id) as total FROM product_categories")->fetch_assoc()['total'];
            
            echo "<div class='code'>";
            echo "📊 STATISTICI FINALE:\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "Total relații: $totalRelations\n";
            echo "Produse cu categorii: $totalProducts\n";
            echo "Categorii folosite: $totalCategories\n";
            
            // Produse cu multiple categorii
            $multiCat = $db->query("SELECT COUNT(*) as total FROM (SELECT product_id FROM product_categories GROUP BY product_id HAVING COUNT(*) > 1) as subq")->fetch_assoc()['total'];
            echo "Produse cu ≥2 categorii: $multiCat\n";
            
            // Media
            if ($totalProducts > 0) {
                $avg = round($totalRelations / $totalProducts, 2);
                echo "Media categorii/produs: $avg\n";
            }
            echo "</div>";
            
            echo '</div>';
            
            // Rezumat final
            echo '<div class="step">';
            echo '<h3>📋 Rezumat</h3>';
            
            if (!empty($success)) {
                echo '<p class="success">✅ SUCCESE:</p><ul>';
                foreach ($success as $msg) {
                    echo "<li>$msg</li>";
                }
                echo '</ul>';
            }
            
            if (!empty($warnings)) {
                echo '<p class="warning">⚠️ AVERTISMENTE:</p><ul>';
                foreach ($warnings as $msg) {
                    echo "<li>$msg</li>";
                }
                echo '</ul>';
            }
            
            if (!empty($errors)) {
                echo '<p class="error">❌ ERORI:</p><ul>';
                foreach ($errors as $msg) {
                    echo "<li>$msg</li>";
                }
                echo '</ul>';
            } else {
                echo '<p class="success" style="font-size: 1.2em;">🎉 MIGRARE COMPLETĂ CU SUCCES!</p>';
                echo '<p class="info">➡️ Următorii pași:</p>';
                echo '<ol>';
                echo '<li>Testează adăugarea unui produs nou cu multiple categorii</li>';
                echo '<li>Testează editarea unui produs existent</li>';
                echo '<li>Verifică filtrarea pe magazin</li>';
                echo '<li>După teste complete, poți șterge coloana category_id din products</li>';
                echo '</ol>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        ?>
        
        <?php if (!$migrationRun): ?>
        <div class="step">
            <h3>ℹ️ Informații</h3>
            <p>Acest script va:</p>
            <ol>
                <li>Crea tabelul <code>product_categories</code> pentru relații many-to-many</li>
                <li>Migra datele existente din <code>products.category_id</code></li>
                <li>Adăuga chei străine (FK) și index-uri pentru performanță</li>
                <li>Afișa statistici despre migrare</li>
            </ol>
            
            <p class="warning"><strong>⚠️ IMPORTANT:</strong></p>
            <ul>
                <li>Fă backup la baza de date înainte!</li>
                <li>Coloana <code>category_id</code> din <code>products</code> NU va fi ștearsă</li>
                <li>Poți rula acest script de mai multe ori (nu va duplica datele)</li>
            </ul>
        </div>
        
        <form method="POST">
            <button type="submit" name="run_migration">▶️ Rulează Migrarea</button>
        </form>
        <?php else: ?>
        <div style="margin-top: 20px;">
            <a href="?" style="text-decoration: none;">
                <button type="button">🔄 Reîncarcare Pagină</button>
            </a>
            <a href="admin/dashboard.php" style="text-decoration: none; margin-left: 10px;">
                <button type="button">📊 Du-te la Dashboard</button>
            </a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

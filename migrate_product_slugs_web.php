<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrare Slug-uri Produse - Brodero</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            color: #155724;
        }
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            color: #721c24;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            color: #856404;
        }
        .product-item {
            padding: 12px;
            background: #f8f9fa;
            margin: 8px 0;
            border-radius: 5px;
            font-family: monospace;
            font-size: 13px;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #dee2e6;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Migrare Slug-uri Produse</h1>
        <p class="subtitle">Generare slug-uri unice pentru produse existente</p>

        <?php
        require_once __DIR__ . '/config/config.php';
        require_once __DIR__ . '/config/database.php';

        // Verificare admin
        if (!isAdmin()) {
            echo '<div class="error-box">';
            echo '<strong>❌ Acces Interzis</strong><br>';
            echo 'Doar administratorii pot accesa această pagină.';
            echo '</div>';
            echo '<a href="' . SITE_URL . '" class="btn">← Înapoi la Site</a>';
            exit;
        }

        $db = getDB();

        /**
         * Funcții pentru generare slug
         */
        function generateSlug($text) {
            $text = strtolower($text);
            $text = str_replace(['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'], ['a', 'a', 'i', 's', 't', 'a', 'a', 'i', 's', 't'], $text);
            $text = preg_replace('/[^a-z0-9]+/', '-', $text);
            $text = trim($text, '-');
            return $text;
        }

        function generateUniqueSlug($db, $text, $exclude_id = null, $usedSlugs = []) {
            $slug = generateSlug($text);
            $originalSlug = $slug;
            $counter = 1;
            
            while (true) {
                if (in_array($slug, $usedSlugs)) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                    continue;
                }
                
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

        // Procesare migrare
        if (isset($_POST['run_migration'])) {
            echo '<div class="info-box">';
            echo '<strong>🔄 Procesare Migrare...</strong>';
            echo '</div>';

            try {
                // Obține produse fără slug
                $result = $db->query("SELECT id, name, slug FROM products WHERE slug IS NULL OR slug = ''");
                $products = $result->fetch_all(MYSQLI_ASSOC);
                
                $totalProducts = count($products);
                $updated = 0;
                $errors = 0;
                $usedSlugs = [];

                echo '<div class="stats">';
                echo '<div class="stat-card">';
                echo '<div class="stat-value">' . $totalProducts . '</div>';
                echo '<div class="stat-label">Produse de migrat</div>';
                echo '</div>';
                echo '</div>';

                if ($totalProducts > 0) {
                    echo '<h3 style="margin-top: 30px; color: #333;">Procesare Produse:</h3>';
                    
                    foreach ($products as $product) {
                        $productId = $product['id'];
                        $productName = $product['name'];
                        
                        try {
                            $slug = generateUniqueSlug($db, $productName, $productId, $usedSlugs);
                            $usedSlugs[] = $slug;
                            
                            $stmt = $db->prepare("UPDATE products SET slug = ? WHERE id = ?");
                            $stmt->bind_param("si", $slug, $productId);
                            
                            if ($stmt->execute()) {
                                $updated++;
                                echo '<div class="product-item" style="border-left: 3px solid #28a745;">';
                                echo '✓ ID ' . $productId . ': <strong>' . htmlspecialchars($productName) . '</strong> → <code>' . htmlspecialchars($slug) . '</code>';
                                echo '</div>';
                            } else {
                                $errors++;
                                echo '<div class="product-item" style="border-left: 3px solid #dc3545;">';
                                echo '✗ ID ' . $productId . ': EROARE - ' . htmlspecialchars($stmt->error);
                                echo '</div>';
                            }
                            
                            $stmt->close();
                        } catch (Exception $e) {
                            $errors++;
                            echo '<div class="product-item" style="border-left: 3px solid #dc3545;">';
                            echo '✗ ID ' . $productId . ': EXCEPȚIE - ' . htmlspecialchars($e->getMessage());
                            echo '</div>';
                        }
                    }

                    // Rezumat
                    echo '<div class="stats" style="margin-top: 30px;">';
                    echo '<div class="stat-card" style="border-color: #28a745;">';
                    echo '<div class="stat-value" style="color: #28a745;">' . $updated . '</div>';
                    echo '<div class="stat-label">Actualizate</div>';
                    echo '</div>';
                    
                    echo '<div class="stat-card" style="border-color: #dc3545;">';
                    echo '<div class="stat-value" style="color: #dc3545;">' . $errors . '</div>';
                    echo '<div class="stat-label">Erori</div>';
                    echo '</div>';
                    echo '</div>';

                    if ($errors === 0) {
                        echo '<div class="success-box">';
                        echo '<strong>✅ MIGRARE COMPLETATĂ CU SUCCES!</strong><br>';
                        echo 'Toate produsele au acum slug-uri unice.';
                        echo '</div>';
                    } else {
                        echo '<div class="warning-box">';
                        echo '<strong>⚠️ MIGRARE COMPLETATĂ CU ERORI</strong><br>';
                        echo 'Verifică produsele cu erori mai sus.';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="success-box">';
                    echo '<strong>✅ TOATE PRODUSELE AU DEJA SLUG-URI!</strong><br>';
                    echo 'Nu este necesară migrarea.';
                    echo '</div>';
                }

                // Verificare finală
                $checkResult = $db->query("SELECT COUNT(*) as cnt FROM products WHERE slug IS NULL OR slug = ''");
                $remaining = $checkResult->fetch_assoc()['cnt'];
                
                $duplicatesResult = $db->query("
                    SELECT slug, COUNT(*) as cnt 
                    FROM products 
                    WHERE slug != '' 
                    GROUP BY slug 
                    HAVING cnt > 1
                ");

                echo '<h3 style="margin-top: 30px; color: #333;">Verificare Finală:</h3>';
                
                if ($remaining > 0) {
                    echo '<div class="warning-box">';
                    echo '⚠️ Atenție: Încă există ' . $remaining . ' produse fără slug!';
                    echo '</div>';
                } else {
                    echo '<div class="success-box">';
                    echo '✓ Toate produsele au slug-uri valide!';
                    echo '</div>';
                }

                if ($duplicatesResult->num_rows > 0) {
                    echo '<div class="error-box">';
                    echo '<strong>⚠️ ATENȚIE: Slug-uri duplicate detectate:</strong><br>';
                    while ($dup = $duplicatesResult->fetch_assoc()) {
                        echo '- <code>' . htmlspecialchars($dup['slug']) . '</code> (apare de ' . $dup['cnt'] . ' ori)<br>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="success-box">';
                    echo '✓ Nu există slug-uri duplicate!';
                    echo '</div>';
                }

            } catch (Exception $e) {
                echo '<div class="error-box">';
                echo '<strong>❌ EROARE CRITICĂ</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }

            echo '<div style="margin-top: 30px;">';
            echo '<a href="admin/admin_products.php" class="btn">📦 Vezi Produsele</a>';
            echo '<a href="admin/dashboard.php" class="btn" style="margin-left: 10px; background: #6c757d;">📊 Dashboard</a>';
            echo '</div>';

        } else {
            // Afișare informații înainte de migrare
            $result = $db->query("SELECT COUNT(*) as cnt FROM products WHERE slug IS NULL OR slug = ''");
            $productsWithoutSlug = $result->fetch_assoc()['cnt'];

            $totalResult = $db->query("SELECT COUNT(*) as cnt FROM products");
            $totalProducts = $totalResult->fetch_assoc()['cnt'];

            echo '<div class="info-box">';
            echo '<strong>ℹ️ Informații:</strong><br>';
            echo 'Acest script va genera slug-uri unice pentru toate produsele care nu au un slug.<br><br>';
            echo '<strong>Ce este un slug?</strong><br>';
            echo 'Un slug este o versiune URL-friendly a numelui produsului (ex: "camasa-barbati-albastra").<br>';
            echo 'Este necesar pentru URL-uri SEO-friendly și pentru evitarea erorilor de duplicate entry.';
            echo '</div>';

            echo '<div class="stats">';
            echo '<div class="stat-card">';
            echo '<div class="stat-value">' . $totalProducts . '</div>';
            echo '<div class="stat-label">Total Produse</div>';
            echo '</div>';
            
            echo '<div class="stat-card" style="border-color: ' . ($productsWithoutSlug > 0 ? '#ffc107' : '#28a745') . ';">';
            echo '<div class="stat-value" style="color: ' . ($productsWithoutSlug > 0 ? '#ffc107' : '#28a745') . ';">' . $productsWithoutSlug . '</div>';
            echo '<div class="stat-label">Fără Slug</div>';
            echo '</div>';
            echo '</div>';

            if ($productsWithoutSlug > 0) {
                echo '<div class="warning-box">';
                echo '<strong>⚠️ Acțiune Necesară</strong><br>';
                echo 'Există ' . $productsWithoutSlug . ' produse fără slug. Rulează migrarea pentru a le actualiza.';
                echo '</div>';

                echo '<form method="POST" style="margin-top: 30px;">';
                echo '<button type="submit" name="run_migration" class="btn">▶️ Rulează Migrarea</button>';
                echo '</form>';
            } else {
                echo '<div class="success-box">';
                echo '<strong>✅ Nu Este Necesară Migrarea</strong><br>';
                echo 'Toate produsele au deja slug-uri valide.';
                echo '</div>';
                
                echo '<div style="margin-top: 30px;">';
                echo '<a href="admin/admin_products.php" class="btn">📦 Vezi Produsele</a>';
                echo '</div>';
            }
        }
        ?>

        <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #e9ecef;">
            <h3 style="color: #333; margin-bottom: 15px;">📚 Informații Tehnice</h3>
            <ul style="color: #666; line-height: 2; padding-left: 20px;">
                <li>Slug-ul este generat automat din numele produsului</li>
                <li>Caracterele speciale românești sunt convertite (ă→a, ș→s, etc.)</li>
                <li>Spațiile și caracterele speciale devin "-"</li>
                <li>Slug-urile duplicate primesc un suffix numeric (-1, -2, etc.)</li>
                <li>Operațiunea este SAFE - nu afectează alte date ale produsului</li>
            </ul>
        </div>
    </div>
</body>
</html>

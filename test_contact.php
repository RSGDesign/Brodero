<?php
/**
 * Test Script - Verificare Configurare Contact Form
 * Accesează: https://brodero.online/test_contact.php
 * 
 * ATENȚIE: Șterge acest fișier după testare!
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Verificare access (doar pentru debugging)
$isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || 
           strpos($_SERVER['REMOTE_ADDR'], '192.168.') === 0;

if (!$isLocal && !isset($_GET['debug_key']) || (isset($_GET['debug_key']) && $_GET['debug_key'] !== 'brodero2025')) {
    die('Access denied. Adaugă ?debug_key=brodero2025 la URL pentru debugging.');
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Contact Form - Brodero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-card { margin-bottom: 20px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">🧪 Test Contact Form Configuration</h1>
        
        <?php
        $tests = [];
        
        // Test 1: Verificare tabel contact_messages
        echo "<div class='card test-card'>";
        echo "<div class='card-header'><strong>Test 1:</strong> Verificare Tabel Database</div>";
        echo "<div class='card-body'>";
        
        try {
            $db = getDB();
            $result = $db->query("SHOW TABLES LIKE 'contact_messages'");
            if ($result->num_rows > 0) {
                echo "<p class='success'>✅ Tabela <code>contact_messages</code> există</p>";
                
                // Verificare coloane
                $columns = $db->query("SHOW COLUMNS FROM contact_messages");
                echo "<p><strong>Coloane:</strong></p><pre>";
                while ($col = $columns->fetch_assoc()) {
                    echo $col['Field'] . " (" . $col['Type'] . ")\n";
                }
                echo "</pre>";
                
                // Număr mesaje
                $count = $db->query("SELECT COUNT(*) as total FROM contact_messages")->fetch_assoc();
                echo "<p class='info'>📊 Total mesaje în DB: <strong>" . $count['total'] . "</strong></p>";
                
                $tests['database'] = true;
            } else {
                echo "<p class='error'>❌ Tabela <code>contact_messages</code> NU există!</p>";
                echo "<p>Rulează: <code>database_contact_messages.sql</code></p>";
                $tests['database'] = false;
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Eroare: " . $e->getMessage() . "</p>";
            $tests['database'] = false;
        }
        
        echo "</div></div>";
        
        // Test 2: Verificare director uploads
        echo "<div class='card test-card'>";
        echo "<div class='card-header'><strong>Test 2:</strong> Verificare Director Uploads</div>";
        echo "<div class='card-body'>";
        
        $uploadDir = UPLOAD_PATH . 'contact/';
        if (is_dir($uploadDir)) {
            echo "<p class='success'>✅ Directorul <code>$uploadDir</code> există</p>";
            
            // Verificare permisiuni
            if (is_writable($uploadDir)) {
                echo "<p class='success'>✅ Directorul este writable</p>";
                $tests['uploads'] = true;
            } else {
                echo "<p class='error'>❌ Directorul NU este writable! Rulează:</p>";
                echo "<pre>chmod 755 " . $uploadDir . "</pre>";
                $tests['uploads'] = false;
            }
            
            // Fișiere existente
            $files = array_diff(scandir($uploadDir), ['.', '..']);
            echo "<p class='info'>📁 Fișiere uploadate: <strong>" . count($files) . "</strong></p>";
        } else {
            echo "<p class='warning'>⚠️ Directorul NU există. Va fi creat automat la primul upload.</p>";
            $tests['uploads'] = true;
        }
        
        echo "</div></div>";
        
        // Test 3: Verificare funcție email
        echo "<div class='card test-card'>";
        echo "<div class='card-header'><strong>Test 3:</strong> Verificare Funcție Email</div>";
        echo "<div class='card-body'>";
        
        if (file_exists(__DIR__ . '/includes/forms/process_contact.php')) {
            echo "<p class='success'>✅ Fișierul <code>process_contact.php</code> există</p>";
            
            require_once __DIR__ . '/includes/forms/process_contact.php';
            
            if (function_exists('sendContactEmail')) {
                echo "<p class='success'>✅ Funcția <code>sendContactEmail()</code> este definită</p>";
                $tests['email_function'] = true;
            } else {
                echo "<p class='error'>❌ Funcția <code>sendContactEmail()</code> NU este definită!</p>";
                $tests['email_function'] = false;
            }
        } else {
            echo "<p class='error'>❌ Fișierul <code>process_contact.php</code> NU există!</p>";
            $tests['email_function'] = false;
        }
        
        echo "</div></div>";
        
        // Test 4: Verificare configurație email
        echo "<div class='card test-card'>";
        echo "<div class='card-header'><strong>Test 4:</strong> Configurație Email</div>";
        echo "<div class='card-body'>";
        
        echo "<p><strong>Email destinatar:</strong> <code>" . SITE_EMAIL . "</code></p>";
        echo "<p><strong>Email expeditor:</strong> <code>no-reply@brodero.online</code></p>";
        
        // Test dacă mail() funcționează
        if (function_exists('mail')) {
            echo "<p class='success'>✅ Funcția PHP <code>mail()</code> este disponibilă</p>";
            $tests['mail_function'] = true;
        } else {
            echo "<p class='error'>❌ Funcția PHP <code>mail()</code> NU este disponibilă!</p>";
            $tests['mail_function'] = false;
        }
        
        // Verificare ini_get pentru mail
        $sendmail = ini_get('sendmail_path');
        echo "<p><strong>Sendmail path:</strong> <code>" . ($sendmail ?: 'Default') . "</code></p>";
        
        echo "</div></div>";
        
        // Test 5: Verificare formular contact.php
        echo "<div class='card test-card'>";
        echo "<div class='card-header'><strong>Test 5:</strong> Verificare Pagină Contact</div>";
        echo "<div class='card-body'>";
        
        if (file_exists(__DIR__ . '/pages/contact.php')) {
            echo "<p class='success'>✅ Fișierul <code>contact.php</code> există</p>";
            
            // Verificare dacă procesare este înainte de header
            $contactContent = file_get_contents(__DIR__ . '/pages/contact.php');
            if (strpos($contactContent, 'require_once __DIR__ . \'/../config/config.php\';') !== false &&
                strpos($contactContent, 'if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\')') !== false) {
                
                $headerPos = strpos($contactContent, 'require_once __DIR__ . \'/../includes/header.php\'');
                $postPos = strpos($contactContent, 'if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\')');
                
                if ($postPos < $headerPos) {
                    echo "<p class='success'>✅ Procesare POST este ÎNAINTE de include header (correct!)</p>";
                    $tests['contact_order'] = true;
                } else {
                    echo "<p class='error'>❌ Include header este ÎNAINTE de procesare POST (va cauza erori!)</p>";
                    $tests['contact_order'] = false;
                }
            }
            
            echo "<p><a href='/pages/contact.php' target='_blank' class='btn btn-primary btn-sm'>Testează Formularul →</a></p>";
        } else {
            echo "<p class='error'>❌ Fișierul <code>contact.php</code> NU există!</p>";
            $tests['contact_order'] = false;
        }
        
        echo "</div></div>";
        
        // Test 6: Test de trimitere email (opțional)
        if (isset($_GET['send_test_email'])) {
            echo "<div class='card test-card'>";
            echo "<div class='card-header'><strong>Test 6:</strong> Trimitere Email Test</div>";
            echo "<div class='card-body'>";
            
            require_once __DIR__ . '/includes/forms/process_contact.php';
            
            $testResult = sendContactEmail(
                'Test User',
                'test@brodero.online',
                'Test Email din Script',
                'Acesta este un email de test automat.\n\nDacă primești acest email, configurarea funcționează!',
                []
            );
            
            if ($testResult) {
                echo "<p class='success'>✅ Email trimis cu succes! Verifică inbox-ul la <code>" . SITE_EMAIL . "</code></p>";
            } else {
                echo "<p class='error'>❌ Email NU a putut fi trimis. Verifică:</p>";
                echo "<ul>";
                echo "<li>Configurația mail server în cPanel</li>";
                echo "<li>SPF/DKIM records în DNS</li>";
                echo "<li>Log-urile: <code>/var/log/mail.log</code></li>";
                echo "</ul>";
            }
            
            echo "</div></div>";
        } else {
            echo "<div class='card test-card'>";
            echo "<div class='card-body text-center'>";
            echo "<a href='?debug_key=brodero2025&send_test_email=1' class='btn btn-warning'>📧 Trimite Email Test</a>";
            echo "<p class='text-muted mt-2 mb-0'>Click pentru a testa trimiterea efectivă a emailului</p>";
            echo "</div></div>";
        }
        
        // Sumar Final
        $totalTests = count($tests);
        $passedTests = count(array_filter($tests));
        
        echo "<div class='card'>";
        echo "<div class='card-header bg-" . ($passedTests === $totalTests ? 'success' : 'warning') . " text-white'>";
        echo "<strong>Rezultat Final:</strong> $passedTests / $totalTests teste reușite";
        echo "</div>";
        echo "<div class='card-body'>";
        
        if ($passedTests === $totalTests) {
            echo "<h4 class='success'>🎉 Toate testele au trecut cu succes!</h4>";
            echo "<p>Formularul de contact este configurat corect și gata de utilizare.</p>";
            echo "<p><a href='/pages/contact.php' class='btn btn-success'>Accesează Formularul →</a></p>";
        } else {
            echo "<h4 class='warning'>⚠️ Unele teste au eșuat</h4>";
            echo "<p>Verifică erorile de mai sus și rezolvă problemele identificate.</p>";
        }
        
        echo "<hr>";
        echo "<p class='text-danger'><strong>⚠️ IMPORTANT:</strong> Șterge acest fișier după testare:</p>";
        echo "<pre>rm " . __FILE__ . "</pre>";
        
        echo "</div></div>";
        ?>
        
        <div class="mt-4 text-center text-muted">
            <p>Data Test: <?php echo date('d.m.Y H:i:s'); ?></p>
            <p><a href="?" class="btn btn-sm btn-secondary">↻ Reîncarcă Testele</a></p>
        </div>
    </div>
</body>
</html>

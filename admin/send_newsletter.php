<?php
/**
 * Send Newsletter
 * Formular pentru trimitere newsletter către abonați
 */

$pageTitle = "Trimite Newsletter";

require_once __DIR__ . '/../includes/header.php';

// Verificare acces admin
if (!isAdmin()) {
    setMessage("Nu ai acces la această pagină.", "danger");
    redirect('/');
}

$db = getDB();

// Statistici destinatari
$activeSubscribers = $db->query("SELECT COUNT(*) as total FROM newsletter WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC)[0]['total'];
$allSubscribers = $db->query("SELECT COUNT(*) as total FROM newsletter")->fetch_all(MYSQLI_ASSOC)[0]['total'];

// Procesare formular
$success = false;
$sentCount = 0;
$failedCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_newsletter'])) {
    $subject = trim($_POST['subject']);
    $content = $_POST['content']; // HTML content
    $recipients = $_POST['recipients']; // 'all', 'active', 'inactive'
    
    $errors = [];
    
    // Validări
    if (empty($subject)) {
        $errors[] = "Subiectul este obligatoriu.";
    }
    
    if (empty($content)) {
        $errors[] = "Conținutul este obligatoriu.";
    }
    
    if (empty($errors)) {
        // Selectare destinatari
        $query = "SELECT email FROM newsletter WHERE 1=1";
        
        if ($recipients === 'active') {
            $query .= " AND is_active = 1";
        } elseif ($recipients === 'inactive') {
            $query .= " AND is_active = 0";
        }
        
        $subscribersResult = $db->query($query);
        
        if ($subscribersResult->num_rows > 0) {
            // Template HTML pentru email
            $emailTemplate = '
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            margin: 20px;
            padding: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px 20px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e9ecef;
        }
        .footer a {
            color: #6366f1;
            text-decoration: none;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #6366f1;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Brodero</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Design-uri de broderie premium</p>
        </div>
        <div class="content">
            ' . $content . '
        </div>
        <div class="footer">
            <p><strong>Brodero</strong> - Magazin de design-uri pentru broderie</p>
            <p>
                Ai primit acest email pentru că ești abonat la newsletter-ul nostru.<br>
                <a href="' . SITE_URL . '/pages/unsubscribe.php?email={{EMAIL}}">Dezabonează-te aici</a>
            </p>
            <p style="margin-top: 15px;">
                <a href="' . SITE_URL . '">Vizitează magazinul</a> | 
                <a href="' . SITE_URL . '/pages/contact.php">Contact</a>
            </p>
        </div>
    </div>
</body>
</html>';
            
            // Trimitere emailuri
            while ($subscriber = $subscribersResult->fetch_assoc()) {
                $toEmail = $subscriber['email'];
                
                // Înlocuire placeholder email în template
                $personalizedContent = str_replace('{{EMAIL}}', urlencode($toEmail), $emailTemplate);
                
                // Headers pentru HTML email
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: Brodero <noreply@brodero.online>\r\n";
                $headers .= "Reply-To: contact@brodero.online\r\n";
                
                // Trimitere email
                if (mail($toEmail, $subject, $personalizedContent, $headers)) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }
            
            $success = true;
            setMessage("Newsletter trimis cu succes! Trimis: $sentCount, Eșuat: $failedCount", "success");
        } else {
            setMessage("Nu există destinatari pentru această selecție.", "warning");
        }
    }
    
    // Afișare erori
    if (!empty($errors)) {
        foreach ($errors as $error) {
            setMessage($error, "danger");
        }
    }
}
?>

<!-- Page Header -->
<section class="bg-dark text-white py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h2 fw-bold mb-0">
                    <i class="bi bi-send-fill me-2"></i>Trimite Newsletter
                </h1>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="<?php echo SITE_URL; ?>/admin/admin_newsletter.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-2"></i>Înapoi la Listă
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Form -->
<section class="py-5">
    <div class="container">
        <?php if ($success): ?>
        <!-- Success Message -->
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-3 me-3"></i>
                <div>
                    <h5 class="mb-1">Newsletter Trimis cu Succes!</h5>
                    <p class="mb-0">
                        <strong><?php echo $sentCount; ?></strong> emailuri trimise
                        <?php if ($failedCount > 0): ?>
                            | <strong class="text-danger"><?php echo $failedCount; ?></strong> eșuate
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 p-4">
                        <h5 class="fw-bold mb-0">Compune Newsletter</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" id="newsletterForm">
                            <!-- Subiect -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    Subiect Email <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="subject" class="form-control" required 
                                       placeholder="Ex: Oferte speciale de iarnă la Brodero">
                                <small class="text-muted">Acest text va apărea în subiectul emailului</small>
                            </div>
                            
                            <!-- Destinatari -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    Destinatari <span class="text-danger">*</span>
                                </label>
                                <select name="recipients" class="form-select" required id="recipientsSelect">
                                    <option value="active">Doar abonați activi (<?php echo $activeSubscribers; ?> persoane)</option>
                                    <option value="all">Toți abonații (<?php echo $allSubscribers; ?> persoane)</option>
                                    <option value="inactive">Doar abonați dezactivați (<?php echo $allSubscribers - $activeSubscribers; ?> persoane)</option>
                                </select>
                                <small class="text-muted">Selectează cui vrei să trimiți newsletter-ul</small>
                            </div>
                            
                            <!-- Conținut -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    Conținut Newsletter <span class="text-danger">*</span>
                                </label>
                                <textarea name="content" class="form-control" rows="15" required 
                                          placeholder="Scrie conținutul newsletter-ului aici... (HTML acceptat)"></textarea>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Poți folosi HTML pentru formatare: &lt;h2&gt;, &lt;p&gt;, &lt;strong&gt;, &lt;a href=""&gt;, &lt;img src=""&gt;, etc.
                                </small>
                            </div>
                            
                            <!-- Exemple HTML -->
                            <div class="mb-4">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertTemplate('greeting')">
                                    <i class="bi bi-plus-circle me-1"></i>Template Salut
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertTemplate('offer')">
                                    <i class="bi bi-plus-circle me-1"></i>Template Ofertă
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertTemplate('product')">
                                    <i class="bi bi-plus-circle me-1"></i>Template Produs
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertTemplate('button')">
                                    <i class="bi bi-plus-circle me-1"></i>Buton Link
                                </button>
                            </div>
                            
                            <!-- Buttons -->
                            <div class="d-flex gap-2 pt-3 border-top">
                                <button type="button" class="btn btn-outline-primary" onclick="previewNewsletter()">
                                    <i class="bi bi-eye me-2"></i>Preview
                                </button>
                                <button type="submit" name="send_newsletter" class="btn btn-success">
                                    <i class="bi bi-send-fill me-2"></i>Trimite Newsletter
                                </button>
                                <a href="<?php echo SITE_URL; ?>/admin/admin_newsletter.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Anulează
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar Info -->
            <div class="col-lg-4">
                <!-- Tips Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 p-4">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-lightbulb me-2"></i>Sfaturi
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <small>Folosește un subiect captivant și scurt</small>
                            </li>
                            <li class="mb-3">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <small>Adaugă link-uri către produse și oferte</small>
                            </li>
                            <li class="mb-3">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <small>Include imagini pentru a face emailul mai atractiv</small>
                            </li>
                            <li class="mb-3">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <small>Testează preview-ul înainte de trimitere</small>
                            </li>
                            <li class="mb-0">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <small>Adaugă call-to-action clar (butoane)</small>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Recipients Info -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 p-4">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-people me-2"></i>Statistici Destinatari
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="text-muted small">Abonați activi</label>
                            <div class="h4 fw-bold mb-0 text-success"><?php echo $activeSubscribers; ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-muted small">Total abonați</label>
                            <div class="h4 fw-bold mb-0"><?php echo $allSubscribers; ?></div>
                        </div>
                        
                        <div class="mb-0">
                            <label class="text-muted small">Dezabonați</label>
                            <div class="h5 fw-bold mb-0 text-warning"><?php echo $allSubscribers - $activeSubscribers; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-eye me-2"></i>Preview Newsletter
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="previewContent" style="max-height: 600px; overflow-y: auto;">
                    <!-- Preview va fi generat aici -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
            </div>
        </div>
    </div>
</div>

<script>
// Template-uri predefinite
const templates = {
    greeting: '<h2>Bună ziua!</h2>\n<p>Suntem bucuroși să vă aducem cele mai noi oferte de la Brodero.</p>',
    offer: '<div style="background-color: #f0f9ff; padding: 20px; border-left: 4px solid #6366f1; margin: 20px 0;">\n    <h3 style="margin-top: 0; color: #6366f1;">🎉 Ofertă Specială</h3>\n    <p style="margin-bottom: 0;">Reducere de <strong>30%</strong> la toate produsele din categoria Broderie Florală!</p>\n</div>',
    product: '<div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 20px 0;">\n    <h3>Trandafir Roșu Elegant</h3>\n    <p>Design de broderie cu un trandafir roșu detaliat, perfect pentru decorațiuni elegante.</p>\n    <p style="font-size: 24px; color: #6366f1; font-weight: bold;">35.00 LEI <del style="color: #999; font-size: 18px;">45.00 LEI</del></p>\n</div>',
    button: '<a href="' + '<?php echo SITE_URL; ?>' + '" class="button">Vezi toate ofertele</a>'
};

// Inserare template în textarea
function insertTemplate(type) {
    const textarea = document.querySelector('textarea[name="content"]');
    const template = templates[type];
    
    if (textarea && template) {
        const cursorPos = textarea.selectionStart;
        const textBefore = textarea.value.substring(0, cursorPos);
        const textAfter = textarea.value.substring(cursorPos);
        
        textarea.value = textBefore + template + textAfter;
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = cursorPos + template.length;
    }
}

// Preview newsletter
function previewNewsletter() {
    const subject = document.querySelector('input[name="subject"]').value;
    const content = document.querySelector('textarea[name="content"]').value;
    
    if (!content) {
        alert('Adaugă conținut pentru a vedea preview-ul!');
        return;
    }
    
    const emailTemplate = `
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            margin: 20px;
            padding: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px 20px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e9ecef;
        }
        .footer a {
            color: #6366f1;
            text-decoration: none;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #6366f1;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Brodero</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Design-uri de broderie premium</p>
        </div>
        <div class="content">
            ${content}
        </div>
        <div class="footer">
            <p><strong>Brodero</strong> - Magazin de design-uri pentru broderie</p>
            <p>
                Ai primit acest email pentru că ești abonat la newsletter-ul nostru.<br>
                <a href="#">Dezabonează-te aici</a>
            </p>
            <p style="margin-top: 15px;">
                <a href="<?php echo SITE_URL; ?>">Vizitează magazinul</a> | 
                <a href="<?php echo SITE_URL; ?>/pages/contact.php">Contact</a>
            </p>
        </div>
    </div>
</body>
</html>`;
    
    document.getElementById('previewContent').innerHTML = emailTemplate;
    
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    previewModal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

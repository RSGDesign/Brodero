<?php
/**
 * Pagina Contact
 * Formular de contact cu upload fișiere și informații de contact
 */

// IMPORTANT: Include config și database PRIMUL (pentru a putea face redirect fără erori)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/seo.php';

$db = getDB();
$errors = [];

// Încarcă SEO din baza de date
$dbPDO = getPDO();
$seo = getSeoForPage('contact', $dbPDO);

if ($seo) {
    $pageTitle = $seo['title'];
    $pageDescription = $seo['description'];
    $pageKeywords = $seo['keywords'];
} else {
    $pageTitle = "Contact";
    $pageDescription = "Contactează-ne pentru orice întrebări sau suport.";
}

// ═══════════════════════════════════════════════════════════════════════════
// PROCESARE FORMULAR ÎNAINTE DE ORICE OUTPUT HTML
// Aceasta este singura modalitate corectă de a preveni eroarea "headers already sent"
// ═══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // CSRF Token Verification
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Token de securitate invalid. Te rugăm să reîncerci.";
    }
    
    // Honeypot anti-spam
    if (!empty($_POST['website'])) {
        // Bot detected - fake success
        setMessage("Mesajul tău a fost trimis cu succes!", "success");
        redirect('/pages/contact.php');
        exit;
    }
    
    $name = cleanInput($_POST['name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $subject = cleanInput($_POST['subject'] ?? '');
    $message = cleanInput($_POST['message'] ?? '');
    
    // Validare câmpuri
    if (empty($name)) {
        $errors[] = "Numele este obligatoriu.";
    } elseif (strlen($name) < 2) {
        $errors[] = "Numele trebuie să aibă cel puțin 2 caractere.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email-ul este invalid.";
    }
    
    if (empty($subject)) {
        $errors[] = "Subiectul este obligatoriu.";
    } elseif (strlen($subject) < 3) {
        $errors[] = "Subiectul trebuie să aibă cel puțin 3 caractere.";
    }
    
    if (empty($message)) {
        $errors[] = "Mesajul este obligatoriu.";
    } elseif (strlen($message) < 10) {
        $errors[] = "Mesajul trebuie să aibă cel puțin 10 caractere.";
    }
    
    $attachments = [];
    $attachmentErrors = [];
    
    // ═══════════════════════════════════════════════════════════════════════════
    // PROCESARE FIȘIERE UPLOADATE (ATAȘAMENTE)
    // ═══════════════════════════════════════════════════════════════════════════
    if (isset($_FILES['attachments']) && $_FILES['attachments']['error'][0] !== UPLOAD_ERR_NO_FILE) {
        $uploadDir = UPLOAD_PATH . 'contact/';
        
        // Creare director pentru fișiere de contact dacă nu există
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $errors[] = "Eroare: Nu s-a putut crea directorul pentru atașamente.";
            }
        }
        
        // Verifică permisiunile directorului
        if (!is_writable($uploadDir)) {
            $errors[] = "Eroare: Directorul pentru atașamente nu este accesibil pentru scriere.";
        }
        
        // Procesare fiecare fișier uploadat
        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
            // Verifică dacă fișierul a fost uploadat cu succes
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['attachments']['name'][$key];
                $fileSize = $_FILES['attachments']['size'][$key];
                $fileType = $_FILES['attachments']['type'][$key];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // VALIDARE 1: Verifică dimensiunea fișierului
                if ($fileSize > MAX_FILE_SIZE) {
                    $fileSizeMB = round(MAX_FILE_SIZE / 1024 / 1024, 1);
                    $attachmentErrors[] = "Fișierul <strong>" . htmlspecialchars($fileName) . "</strong> este prea mare (" . round($fileSize/1024/1024, 2) . " MB). Maxim permis: {$fileSizeMB} MB.";
                    continue;
                }
                
                // VALIDARE 2: Verifică extensia fișierului
                if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
                    $allowedList = implode(', ', ALLOWED_EXTENSIONS);
                    $attachmentErrors[] = "Fișierul <strong>" . htmlspecialchars($fileName) . "</strong> are o extensie nepermisă (<strong>.{$fileExt}</strong>). Extensii permise: {$allowedList}.";
                    continue;
                }
                
                // VALIDARE 3: Verifică fișierul temporar
                if (!is_uploaded_file($tmpName)) {
                    $attachmentErrors[] = "Eroare de securitate: Fișierul <strong>" . htmlspecialchars($fileName) . "</strong> nu este valid.";
                    continue;
                }
                
                // VALIDARE 4: Verifică tipul MIME real (nu doar extensia)
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $realMimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                
                $allowedMimeTypes = [
                    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                    'application/pdf',
                    'application/zip', 'application/x-zip-compressed'
                ];
                
                if (!in_array($realMimeType, $allowedMimeTypes)) {
                    $attachmentErrors[] = "Fișierul <strong>" . htmlspecialchars($fileName) . "</strong> are un tip MIME invalid ({$realMimeType}).";
                    continue;
                }
                
                // Generare nume unic și sigur pentru fișier
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', pathinfo($fileName, PATHINFO_FILENAME));
                $newFileName = uniqid('contact_') . '_' . time() . '_' . substr($safeName, 0, 50) . '.' . $fileExt;
                $destination = $uploadDir . $newFileName;
                
                // Mutare fișier din locația temporară în destinație
                if (move_uploaded_file($tmpName, $destination)) {
                    $attachments[] = $newFileName;
                } else {
                    $attachmentErrors[] = "Eroare la încărcarea fișierului <strong>" . htmlspecialchars($fileName) . "</strong>. Te rugăm să încerci din nou.";
                }
            } elseif ($_FILES['attachments']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                // Tratare alte erori de upload
                $errorMsg = "Eroare la uploadul fișierului <strong>" . htmlspecialchars($_FILES['attachments']['name'][$key]) . "</strong>: ";
                switch ($_FILES['attachments']['error'][$key]) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMsg .= "Fișierul este prea mare.";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMsg .= "Fișierul a fost uploadat parțial.";
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $errorMsg .= "Lipsește directorul temporar.";
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $errorMsg .= "Eroare la scrierea pe disc.";
                        break;
                    default:
                        $errorMsg .= "Eroare necunoscută.";
                }
                $attachmentErrors[] = $errorMsg;
            }
        }
    }
    
    // Adaugă erorile de atașamente la lista principală de erori
    if (!empty($attachmentErrors)) {
        $errors = array_merge($errors, $attachmentErrors);
    }
    
    if (empty($errors)) {
        // ═══════════════════════════════════════════════════════════════════════════
        // TRIMITERE EMAIL CU ATAȘAMENTE - MIME MULTIPART
        // Această metodă permite trimiterea de fișiere atașate prin funcția mail()
        // ═══════════════════════════════════════════════════════════════════════════
        
        $toEmail = 'contact@brodero.online';
        $emailSubject = "Mesaj nou din formular: " . $subject;
        
        // Generare boundary unic pentru MIME multipart
        $boundary = md5(uniqid(time()));
        
        // Construire listă atașamente pentru afișare în email
        $attachmentsList = '';
        if (!empty($attachments)) {
            $attachmentsList = '<p><strong>Fișiere atașate (' . count($attachments) . '):</strong></p><ul>';
            foreach ($attachments as $file) {
                $filePath = UPLOAD_PATH . 'contact/' . $file;
                $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
                $fileSizeKB = round($fileSize / 1024, 2);
                $attachmentsList .= '<li>' . htmlspecialchars($file) . ' (' . $fileSizeKB . ' KB)</li>';
            }
            $attachmentsList .= '</ul>';
        }
        
        // Template HTML - IDENTIC CU NEWSLETTER-UL
        $emailContent = '
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
        .info-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #6366f1;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Brodero</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Mesaj nou din formular de contact</p>
        </div>
        <div class="content">
            <h2>Detalii mesaj:</h2>
            
            <div class="info-box">
                <p><strong>Nume:</strong> ' . htmlspecialchars($name) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>
                <p><strong>Subiect:</strong> ' . htmlspecialchars($subject) . '</p>
                <p><strong>Data:</strong> ' . date('d.m.Y H:i') . '</p>
            </div>
            
            <h3>Mesaj:</h3>
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
                ' . nl2br(htmlspecialchars($message)) . '
            </div>
            
            ' . $attachmentsList . '
            
            <p style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e9ecef; color: #666; font-size: 14px;">
                <strong>IP:</strong> ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . '<br>
                <strong>User Agent:</strong> ' . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . '
            </p>
        </div>
        <div class="footer">
            <p><strong>Brodero</strong> - Magazin de design-uri pentru broderie</p>
            <p>Acest email a fost trimis automat din formularul de contact de pe site.</p>
        </div>
    </div>
</body>
</html>';
        
        // ═══════════════════════════════════════════════════════════════════════════
        // HEADERS PENTRU EMAIL CU ATAȘAMENTE (MIME Multipart)
        // ═══════════════════════════════════════════════════════════════════════════
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: Brodero <noreply@brodero.online>\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        
        // ═══════════════════════════════════════════════════════════════════════════
        // CONSTRUIRE BODY EMAIL - MIME MULTIPART FORMAT
        // Partea 1: Conținut HTML
        // Partea 2+: Fișiere atașate (dacă există)
        // ═══════════════════════════════════════════════════════════════════════════
        $emailBody = "--{$boundary}\r\n";
        $emailBody .= "Content-Type: text/html; charset=UTF-8\r\n";
        $emailBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $emailBody .= $emailContent . "\r\n\r\n";
        
        // ATAȘARE FIȘIERE (dacă există)
        if (!empty($attachments)) {
            foreach ($attachments as $attachmentFile) {
                $filePath = UPLOAD_PATH . 'contact/' . $attachmentFile;
                
                // Verifică dacă fișierul există
                if (!file_exists($filePath)) {
                    continue; // Skip dacă fișierul nu există
                }
                
                // Citește conținutul fișierului și encodează în base64
                $fileContent = file_get_contents($filePath);
                $fileContentEncoded = chunk_split(base64_encode($fileContent));
                
                // Detectare tip MIME
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $filePath);
                finfo_close($finfo);
                
                // Adaugă fișierul atașat în email
                $emailBody .= "--{$boundary}\r\n";
                $emailBody .= "Content-Type: {$mimeType}; name=\"{$attachmentFile}\"\r\n";
                $emailBody .= "Content-Transfer-Encoding: base64\r\n";
                $emailBody .= "Content-Disposition: attachment; filename=\"{$attachmentFile}\"\r\n\r\n";
                $emailBody .= $fileContentEncoded . "\r\n";
            }
        }
        
        // Închide boundary-ul multipart
        $emailBody .= "--{$boundary}--";
        
        // ═══════════════════════════════════════════════════════════════════════════
        // TRIMITERE EMAIL CU FIȘIERE ATAȘATE
        // ═══════════════════════════════════════════════════════════════════════════
        if (mail($toEmail, $emailSubject, $emailBody, $headers)) {
            // ✅ EMAIL TRIMIS CU SUCCES
            
            // Salvează în database pentru backup
            $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message, attachments, status, created_at) VALUES (?, ?, ?, ?, ?, 'new', NOW())");
            $attachmentsJson = json_encode($attachments);
            $stmt->bind_param("sssss", $name, $email, $subject, $message, $attachmentsJson);
            $stmt->execute();
            $stmt->close();
            
            // Șterge fișierele temporare după trimitere (opțional - pentru a economisi spațiu)
            // COMENTEAZĂ această secțiune dacă vrei să păstrezi fișierele pe server
            /*
            foreach ($attachments as $attachmentFile) {
                $filePath = UPLOAD_PATH . 'contact/' . $attachmentFile;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            */
            
            // Mesaj de succes cu detalii
            $successMsg = "Mesajul tău a fost trimis cu succes!";
            if (!empty($attachments)) {
                $successMsg .= " (" . count($attachments) . " fișier(e) atașat(e))";
            }
            $successMsg .= " Îți vom răspunde în cel mai scurt timp.";
            
            setMessage($successMsg, "success");
            redirect('/pages/contact.php');
            exit; // IMPORTANT: exit după redirect
        } else {
            // ❌ EROARE LA TRIMITERE
            $errors[] = "Eroare la trimiterea emailului. Te rugăm să încerci din nou sau contactează-ne direct la contact@brodero.online";
            
            // Șterge fișierele uploadate dacă emailul nu a putut fi trimis
            foreach ($attachments as $attachmentFile) {
                $filePath = UPLOAD_PATH . 'contact/' . $attachmentFile;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }
    }
    
    // Setează mesaje de eroare în sesiune pentru afișare după redirect
    if (!empty($errors)) {
        foreach ($errors as $error) {
            setMessage($error, "danger");
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ACUM INCLUDEM HEADER.PHP (DUPĂ procesarea POST)
// Acesta este momentul corect pentru a începe output-ul HTML
// ═══════════════════════════════════════════════════════════════════════════
$pageTitle = "Contact";
$pageDescription = "Contactează echipa Brodero pentru orice întrebări sau sugestii.";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<section class="bg-light py-4 border-bottom">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="h2 fw-bold mb-0 text-dark">Contact</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Acasă</a></li>
                        <li class="breadcrumb-item active">Contact</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="py-5">
    <div class="container">
        <div class="row g-5">
            <!-- Contact Form -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="fw-bold mb-4">Trimite-ne un mesaj</h3>
                        
                        <form method="POST" enctype="multipart/form-data" class="contact-form">
                            <div class="row g-3">
                                <!-- Nume -->
                                <div class="col-md-6">
                                    <label for="name" class="form-label">
                                        Nume complet <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <!-- Email -->
                                <div class="col-md-6">
                                    <label for="email" class="form-label">
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <!-- Subiect -->
                                <div class="col-12">
                                    <label for="subject" class="form-label">
                                        Subiect <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="subject" name="subject" required>
                                </div>
                                
                                <!-- Mesaj -->
                                <div class="col-12">
                                    <label for="message" class="form-label">
                                        Mesaj <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                                </div>
                                
                                <!-- Upload Fișiere -->
                                <div class="col-12">
                                    <label for="attachments" class="form-label">
                                        Atașează fișiere (opțional)
                                    </label>
                                    <input type="file" class="form-control" id="attachments" name="attachments[]" 
                                           multiple accept=".jpg,.jpeg,.png,.pdf,.zip">
                                    <div class="form-text">
                                        Poți atașa imagini, PDF sau arhive ZIP (max 5MB per fișier).
                                    </div>
                                </div>
                                
                                <!-- Honeypot anti-spam (hidden field) -->
                                <input type="text" name="website" value="" style="display:none !important" tabindex="-1" autocomplete="off">
                                
                                <!-- CSRF Token -->
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <!-- Submit -->
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-send me-2"></i>Trimite Mesajul
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="col-lg-4">
                <!-- Informații de Contact -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Informații de Contact</h5>
                        
                        <div class="d-flex gap-3 mb-3">
                            <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Email</h6>
                                <a href="mailto:<?php echo SITE_EMAIL; ?>" class="text-decoration-none">
                                    <?php echo SITE_EMAIL; ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-3 mb-3">
                            <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                <i class="bi bi-telephone-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Telefon</h6>
                                <a href="tel:<?php echo SITE_PHONE; ?>" class="text-decoration-none">
                                    <?php echo SITE_PHONE; ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-3">
                            <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                <i class="bi bi-clock-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Program</h6>
                                <p class="text-muted mb-0">
                                    Luni - Vineri: 09:00 - 18:00<br>
                                    Sâmbătă - Duminică: Închis
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Social Media -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Urmărește-ne</h5>
                        
                        <div class="d-flex gap-3">
                            <a href="<?php echo FACEBOOK_URL; ?>" target="_blank" 
                               class="btn btn-outline-primary flex-fill">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="<?php echo INSTAGRAM_URL; ?>" target="_blank" 
                               class="btn btn-outline-primary flex-fill">
                                <i class="bi bi-instagram"></i>
                            </a>
                            
                        </div>
                        
                        <p class="text-muted mt-3 mb-0 small">
                            Conectează-te cu noi pe rețelele sociale pentru inspirație și noutăți!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h3 class="fw-bold">Întrebări Frecvente</h3>
            <p class="text-muted">Poate găsești răspunsul mai rapid aici</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Cât durează să primesc fișierele după achiziție?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Fișierele sunt disponibile pentru download imediat după finalizarea plății. Vei primi și un email cu link-ul de descărcare.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                În ce formate sunt disponibile design-urile?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Design-urile noastre sunt disponibile în formatele DST, PES, JEF, VP3 și EXP, compatibile cu majoritatea mașinilor de brodat.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Oferiți suport tehnic?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Da! Echipa noastră este disponibilă prin email și telefon pentru a te ajuta cu orice întrebări despre design-uri sau procesul de broderie.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="<?php echo SITE_URL; ?>/pages/faq.php" class="btn btn-outline-primary">
                        Vezi toate întrebările <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- JavaScript pentru Preview Fișiere Atașate -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const attachmentsInput = document.getElementById('attachments');
    
    if (attachmentsInput) {
        // Creare div pentru preview
        const previewDiv = document.createElement('div');
        previewDiv.id = 'attachments-preview';
        previewDiv.style.marginTop = '15px';
        attachmentsInput.parentNode.appendChild(previewDiv);
        
        attachmentsInput.addEventListener('change', function(e) {
            const files = e.target.files;
            previewDiv.innerHTML = '';
            
            if (files.length === 0) {
                return;
            }
            
            // Afișare informații despre fișierele selectate
            const title = document.createElement('div');
            title.style.fontWeight = 'bold';
            title.style.marginBottom = '10px';
            title.style.color = '#667eea';
            title.innerHTML = `<i class="bi bi-paperclip"></i> Fișiere selectate (${files.length}):`;
            previewDiv.appendChild(title);
            
            const fileList = document.createElement('div');
            fileList.style.display = 'flex';
            fileList.style.flexDirection = 'column';
            fileList.style.gap = '8px';
            
            Array.from(files).forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.style.padding = '10px';
                fileItem.style.background = '#f8f9fa';
                fileItem.style.borderRadius = '5px';
                fileItem.style.display = 'flex';
                fileItem.style.justifyContent = 'space-between';
                fileItem.style.alignItems = 'center';
                fileItem.style.border = '1px solid #dee2e6';
                
                // Verificare validitate fișier
                const fileSize = file.size;
                const fileExt = file.name.split('.').pop().toLowerCase();
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'zip'];
                
                let status = '';
                let statusColor = '#28a745';
                
                if (fileSize > maxSize) {
                    status = '❌ Prea mare (' + (fileSize / 1024 / 1024).toFixed(2) + ' MB)';
                    statusColor = '#dc3545';
                } else if (!allowedExts.includes(fileExt)) {
                    status = '❌ Extensie nepermisă (.' + fileExt + ')';
                    statusColor = '#dc3545';
                } else {
                    status = '✅ Valid (' + (fileSize / 1024).toFixed(1) + ' KB)';
                    statusColor = '#28a745';
                }
                
                // Icon pe baza extensiei
                let icon = 'bi-file-earmark';
                if (['jpg', 'jpeg', 'png'].includes(fileExt)) {
                    icon = 'bi-file-earmark-image';
                } else if (fileExt === 'pdf') {
                    icon = 'bi-file-earmark-pdf';
                } else if (fileExt === 'zip') {
                    icon = 'bi-file-earmark-zip';
                }
                
                fileItem.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="bi ${icon}" style="font-size: 24px; color: #667eea;"></i>
                        <div>
                            <div style="font-weight: 500;">${file.name}</div>
                            <div style="font-size: 12px; color: #6c757d;">
                                ${(fileSize / 1024).toFixed(1)} KB · .${fileExt.toUpperCase()}
                            </div>
                        </div>
                    </div>
                    <div style="font-size: 13px; font-weight: 500; color: ${statusColor};">
                        ${status}
                    </div>
                `;
                
                fileList.appendChild(fileItem);
            });
            
            previewDiv.appendChild(fileList);
            
            // Mesaj informativ
            const info = document.createElement('div');
            info.style.marginTop = '10px';
            info.style.padding = '10px';
            info.style.background = '#e7f3ff';
            info.style.borderRadius = '5px';
            info.style.fontSize = '13px';
            info.style.color = '#004085';
            info.innerHTML = '<i class="bi bi-info-circle"></i> Doar fișierele valide vor fi atașate la email.';
            previewDiv.appendChild(info);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

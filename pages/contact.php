<?php
/**
 * Pagina Contact
 * Formular de contact cu upload fișiere și informații de contact
 */

$pageTitle = "Contact";
$pageDescription = "Contactează echipa Brodero pentru orice întrebări sau sugestii.";

require_once __DIR__ . '/../includes/header.php';

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = cleanInput($_POST['name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $subject = cleanInput($_POST['subject'] ?? '');
    $message = cleanInput($_POST['message'] ?? '');
    
    $errors = [];
    
    // Validare
    if (empty($name)) {
        $errors[] = "Numele este obligatoriu.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email-ul este invalid.";
    }
    
    if (empty($subject)) {
        $errors[] = "Subiectul este obligatoriu.";
    }
    
    if (empty($message)) {
        $errors[] = "Mesajul este obligatoriu.";
    }
    
    $attachments = [];
    
    // Procesare fișiere uploadate
    if (isset($_FILES['attachments']) && $_FILES['attachments']['error'][0] !== UPLOAD_ERR_NO_FILE) {
        $uploadDir = UPLOAD_PATH . 'contact/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['attachments']['name'][$key];
                $fileSize = $_FILES['attachments']['size'][$key];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Validare fișier
                if ($fileSize > MAX_FILE_SIZE) {
                    $errors[] = "Fișierul $fileName este prea mare (max 5MB).";
                    continue;
                }
                
                if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
                    $errors[] = "Tipul fișierului $fileName nu este permis.";
                    continue;
                }
                
                // Salvare fișier
                $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
                $destination = $uploadDir . $newFileName;
                
                if (move_uploaded_file($tmpName, $destination)) {
                    $attachments[] = $newFileName;
                }
            }
        }
    }
    
    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message, attachments) VALUES (?, ?, ?, ?, ?)");
        $attachmentsJson = !empty($attachments) ? json_encode($attachments) : null;
        $stmt->bind_param("sssss", $name, $email, $subject, $message, $attachmentsJson);
        
        if ($stmt->execute()) {
            setMessage("Mesajul tău a fost trimis cu succes! Îți vom răspunde în cel mai scurt timp.", "success");
            redirect('/pages/contact.php');
        } else {
            $errors[] = "Eroare la trimiterea mesajului. Te rugăm să încerci din nou.";
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        foreach ($errors as $error) {
            setMessage($error, "danger");
        }
    }
}
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
                            <a href="<?php echo TWITTER_URL; ?>" target="_blank" 
                               class="btn btn-outline-primary flex-fill">
                                <i class="bi bi-twitter"></i>
                            </a>
                            <a href="<?php echo PINTEREST_URL; ?>" target="_blank" 
                               class="btn btn-outline-primary flex-fill">
                                <i class="bi bi-pinterest"></i>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * Modele la Comandă - Custom Orders
 * Formular pentru comenzi personalizate cu upload fișiere
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/seo.php';

// Încarcă SEO din baza de date
$db = getPDO();
$seo = getSeoForPage('modele-la-comanda', $db);

if ($seo) {
    $pageTitle = $seo['title'];
    $pageDescription = $seo['description'];
    $pageKeywords = $seo['keywords'];
} else {
    $pageTitle = "Modele la Comandă";
    $pageDescription = "Comandă modele personalizate adaptate nevoilor tale. Trimite-ne cererea ta cu detalii și fișiere atașate.";
    $pageKeywords = "modele la comandă, design personalizat, comenzi custom, broderie personalizată";
}

require_once __DIR__ . '/../includes/header.php';

// Mesaj de succes după submit
$successMessage = isset($_SESSION['custom_order_success']) ? $_SESSION['custom_order_success'] : null;
if ($successMessage) {
    unset($_SESSION['custom_order_success']);
}
?>

<!-- Page Header -->
<section class="bg-gradient-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="bi bi-palette me-3"></i>Modele la Comandă
                </h1>
                <p class="lead mb-0">
                    Ai o idee unică pentru un design de broderie? Noi o transformăm în realitate!
                    Trimite-ne cererea ta și vom crea un model personalizat special pentru tine.
                </p>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
                <i class="bi bi-brush-fill display-1 opacity-75"></i>
            </div>
        </div>
    </div>
</section>

<!-- Success Message -->
<?php if ($successMessage): ?>
    <div class="container mt-4">
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Succes!</strong> <?php echo htmlspecialchars($successMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Info Section -->
            <div class="col-lg-5 mb-4">
                <div class="sticky-lg-top" style="top: 2rem;">
                    <h2 class="h3 fw-bold mb-4">De ce să alegi modele personalizate?</h2>
                    
                    <div class="feature-list">
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <div class="icon-box bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="bi bi-star-fill"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="h5 fw-bold">Design Unic</h3>
                                <p class="text-muted mb-0">
                                    Creat special pentru tine, perfect adaptat nevoilor și stilului tău.
                                </p>
                            </div>
                        </div>

                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <div class="icon-box bg-success text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="bi bi-lightning-fill"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="h5 fw-bold">Proces Rapid</h3>
                                <p class="text-muted mb-0">
                                    Primești o primă variantă în maxim 3-5 zile lucrătoare.
                                </p>
                            </div>
                        </div>

                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <div class="icon-box bg-info text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="bi bi-arrow-repeat"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="h5 fw-bold">Revizii Incluse</h3>
                                <p class="text-muted mb-0">
                                    Până la 2 revizii gratuite pentru a te asigura că designul este perfect.
                                </p>
                            </div>
                        </div>

                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <div class="icon-box bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="h5 fw-bold">Calitate Garantată</h3>
                                <p class="text-muted mb-0">
                                    Fișiere în format profesional, gata de utilizat.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border-0 mt-4">
                        <div class="card-body">
                            <h4 class="h6 fw-bold mb-3">
                                <i class="bi bi-info-circle me-2"></i>Cum funcționează?
                            </h4>
                            <ol class="mb-0 ps-3">
                                <li class="mb-2">Completezi formularul cu detalii despre cererea ta</li>
                                <li class="mb-2">Atașezi fișiere relevante (imagini, schiță, referințe)</li>
                                <li class="mb-2">Primești un răspuns în maxim 24h cu detalii și ofertă</li>
                                <li class="mb-2">Aprobăm detaliile și începem lucrul</li>
                                <li>Primești designul finalizat și fișierele sursă</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Section -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-primary text-white py-3">
                        <h3 class="h4 mb-0 fw-bold">
                            <i class="bi bi-pencil-square me-2"></i>Trimite Cererea Ta
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        <form id="customOrderForm" method="POST" action="<?php echo SITE_URL; ?>/ajax/submit_custom_order.php" 
                              enctype="multipart/form-data">
                            
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <!-- Nume -->
                            <div class="mb-4">
                                <label for="name" class="form-label fw-bold">
                                    Nume complet <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control form-control-lg" id="name" name="name" 
                                       required maxlength="255"
                                       placeholder="Ex: Ion Popescu">
                                <div class="invalid-feedback">Vă rugăm introduceți numele complet.</div>
                            </div>

                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-bold">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                       required maxlength="255"
                                       placeholder="exemplu@email.com">
                                <div class="invalid-feedback">Vă rugăm introduceți o adresă de email validă.</div>
                            </div>

                            <!-- Telefon (opțional) -->
                            <div class="mb-4">
                                <label for="phone" class="form-label fw-bold">
                                    Telefon <small class="text-muted">(opțional)</small>
                                </label>
                                <input type="tel" class="form-control form-control-lg" id="phone" name="phone" 
                                       maxlength="50"
                                       placeholder="Ex: 0712 345 678">
                                <small class="form-text text-muted">
                                    Pentru contact rapid în caz de întrebări.
                                </small>
                            </div>

                            <!-- Descriere comandă -->
                            <div class="mb-4">
                                <label for="description" class="form-label fw-bold">
                                    Descrie cererea ta <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="6" required maxlength="5000"
                                          placeholder="Descrie în detaliu ce tip de design dorești: stilul, culorile, dimensiunile, aplicația finală, etc. Cu cât mai multe detalii, cu atât mai bine!"></textarea>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">Minim 50 caractere recomandat</small>
                                    <small class="text-muted" id="charCount">0 / 5000</small>
                                </div>
                                <div class="invalid-feedback">Vă rugăm descrieți cererea în detaliu.</div>
                            </div>

                            <!-- Buget -->
                            <div class="mb-4">
                                <label for="budget" class="form-label fw-bold">
                                    Buget estimativ (RON) <small class="text-muted">(opțional)</small>
                                </label>
                                <input type="number" class="form-control form-control-lg" id="budget" name="budget" 
                                       min="0" max="999999" step="0.01"
                                       placeholder="Ex: 250">
                                <small class="form-text text-muted">
                                    Ne ajută să înțelegem mai bine cerințele tale.
                                </small>
                            </div>

                            <!-- Upload Fișier -->
                            <div class="mb-4">
                                <label for="file" class="form-label fw-bold">
                                    Atașează fișiere <small class="text-muted">(opțional)</small>
                                </label>
                                <input type="file" class="form-control form-control-lg" id="file" name="file" 
                                       accept=".jpg,.jpeg,.png,.pdf,.zip,.rar">
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Tipuri acceptate: JPG, PNG, PDF, ZIP, RAR (max. 10MB)
                                    <br>
                                    Exemplu: schiță, imagine de referință, logo existent, etc.
                                </small>
                                <div id="fileError" class="text-danger mt-2" style="display: none;"></div>
                            </div>

                            <!-- Recaptcha / Honeypot (anti-spam simplu) -->
                            <input type="text" name="website" style="display: none;" tabindex="-1" autocomplete="off">

                            <!-- Submit Button -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="bi bi-send-fill me-2"></i>Trimite Cererea
                                </button>
                            </div>

                            <div class="mt-3 text-center">
                                <small class="text-muted">
                                    <i class="bi bi-shield-lock me-1"></i>
                                    Datele tale sunt în siguranță. Primești răspuns în maxim 24h.
                                </small>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h4 class="h5 mb-0 fw-bold">
                            <i class="bi bi-question-circle me-2"></i>Întrebări Frecvente
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="accordion accordion-flush" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#faq1">
                                        Cât timp durează realizarea unui design personalizat?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        În general, primești prima variantă în 3-5 zile lucrătoare. 
                                        Timpul poate varia în funcție de complexitatea cererii.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#faq2">
                                        Ce formate primesc la final?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Primești fișiere în formatele standard pentru broderie: DST, PES, JEF, EXP, 
                                        plus un preview PNG/JPG pentru vizualizare.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#faq3">
                                        Pot solicita modificări după livrare?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Da! Includem până la 2 revizii gratuite în primele 14 zile de la livrare.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Character counter pentru descriere
const descTextarea = document.getElementById('description');
const charCount = document.getElementById('charCount');

descTextarea?.addEventListener('input', function() {
    const count = this.value.length;
    charCount.textContent = count + ' / 5000';
    
    if (count > 5000) {
        charCount.classList.add('text-danger');
    } else {
        charCount.classList.remove('text-danger');
    }
});

// File validation
const fileInput = document.getElementById('file');
const fileError = document.getElementById('fileError');
const maxSize = 10 * 1024 * 1024; // 10MB
const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/zip', 'application/x-rar-compressed', 'application/x-zip-compressed'];

fileInput?.addEventListener('change', function() {
    fileError.style.display = 'none';
    
    if (this.files.length > 0) {
        const file = this.files[0];
        
        // Check size
        if (file.size > maxSize) {
            fileError.textContent = 'Fișierul este prea mare. Maxim 10MB permis.';
            fileError.style.display = 'block';
            this.value = '';
            return;
        }
        
        // Check type
        if (!allowedTypes.includes(file.type) && !file.name.match(/\.(jpg|jpeg|png|pdf|zip|rar)$/i)) {
            fileError.textContent = 'Tip fișier invalid. Doar JPG, PNG, PDF, ZIP, RAR sunt permise.';
            fileError.style.display = 'block';
            this.value = '';
            return;
        }
    }
});

// Form validation
const form = document.getElementById('customOrderForm');
const submitBtn = document.getElementById('submitBtn');

form?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validare client-side
    if (!form.checkValidity()) {
        e.stopPropagation();
        form.classList.add('was-validated');
        return;
    }
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Se trimite...';
    
    // Submit form
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to success or show message
            window.location.href = '<?php echo SITE_URL; ?>/pages/modele-la-comanda.php?success=1';
        } else {
            alert('Eroare: ' + (data.message || 'Nu s-a putut trimite cererea. Vă rugăm încercați din nou.'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Trimite Cererea';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la trimiterea formularului. Vă rugăm încercați din nou.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Trimite Cererea';
    });
});

// Success message from URL
if (window.location.search.includes('success=1')) {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.icon-box {
    transition: transform 0.3s ease;
}

.feature-list .d-flex:hover .icon-box {
    transform: scale(1.1);
}

.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #667eea;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

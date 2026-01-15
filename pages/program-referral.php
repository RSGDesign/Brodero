<?php
/**
 * Pagină Publică - Program Referral
 * Prezentare program de afiliere pentru utilizatori noi
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions_referral.php';
require_once __DIR__ . '/../includes/seo.php';

// Obține procent comision din setări
$commissionPercentage = getCommissionPercentage();

// Încarcă SEO din baza de date
$db = getPDO();
$seo = getSeoForPage('program-referral', $db);

if ($seo) {
    $pageTitle = $seo['title'];
    $pageDescription = $seo['description'];
    $pageKeywords = $seo['keywords'];
} else {
    // Fallback
    $pageTitle = "Program Referral – Câștigă " . number_format($commissionPercentage, 0) . "% comision";
    $pageDescription = "Invită utilizatori și câștigă " . number_format($commissionPercentage, 0) . "% comision din fiecare comandă. Program de referral simplu, transparent și fără limită.";
    $pageKeywords = "program referral, afiliere, comision, câștig pasiv, venit online";
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero Section -->
<section class="bg-gradient-primary text-white py-5">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-4 mb-lg-0">
                <h1 class="display-4 fw-bold mb-4">
                    Câștigă <?php echo number_format($commissionPercentage, 0); ?>% comision din fiecare comandă
                </h1>
                <p class="lead mb-4">
                    Invită alți utilizatori și câștigă automat din fiecare achiziție pe care o fac. 
                    Simplu, transparent și fără limită de câștig.
                </p>
                
                <div class="d-flex flex-wrap gap-3">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/pages/referral.php" class="btn btn-light btn-lg px-5">
                            <i class="bi bi-link-45deg me-2"></i>Vezi linkul tău de referral
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/pages/login.php" class="btn btn-light btn-lg px-5">
                            <i class="bi bi-person-plus me-2"></i>Creează cont și începe să câștigi
                        </a>
                        <a href="<?php echo SITE_URL; ?>/pages/login.php" class="btn btn-outline-light btn-lg px-5">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Autentifică-te
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-5 text-center">
                <div class="p-5 bg-white bg-opacity-10 rounded-3">
                    <div class="display-1 fw-bold mb-3">
                        <?php echo number_format($commissionPercentage, 0); ?>%
                    </div>
                    <h3 class="h5 mb-0">Comision pe fiecare comandă</h3>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cum funcționează -->
<section class="py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3">Cum funcționează?</h2>
            <p class="text-muted">3 pași simpli pentru a începe să câștigi</p>
        </div>
        
        <div class="row g-4">
            <!-- Pasul 1 -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <div class="mb-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="bi bi-person-plus text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <div class="display-6 fw-bold text-primary mb-3">1</div>
                    <h3 class="h5 fw-bold mb-3">Îți creezi cont</h3>
                    <p class="text-muted mb-0">
                        Înregistrează-te gratuit și primești automat un link unic de referral în contul tău.
                    </p>
                </div>
            </div>
            
            <!-- Pasul 2 -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <div class="mb-4">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="bi bi-share text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <div class="display-6 fw-bold text-success mb-3">2</div>
                    <h3 class="h5 fw-bold mb-3">Distribui linkul</h3>
                    <p class="text-muted mb-0">
                        Partajează linkul tău pe social media, email sau oriunde dorești. Oricine accesează linkul este asociat automat cu tine.
                    </p>
                </div>
            </div>
            
            <!-- Pasul 3 -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <div class="mb-4">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="bi bi-cash-coin text-warning" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <div class="display-6 fw-bold text-warning mb-3">3</div>
                    <h3 class="h5 fw-bold mb-3">Câștigi <?php echo number_format($commissionPercentage, 0); ?>%</h3>
                    <p class="text-muted mb-0">
                        Primești <?php echo number_format($commissionPercentage, 0); ?>% comision din fiecare comandă plătită, 
                        fără limită. Totul se urmărește automat.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Beneficii -->
<section class="bg-light py-5">
    <div class="container py-4">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="fw-bold mb-4">De ce să te alături programului?</h2>
                <ul class="list-unstyled">
                    <li class="mb-3 d-flex align-items-start">
                        <i class="bi bi-check-circle-fill text-success me-3 mt-1" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong><?php echo number_format($commissionPercentage, 0); ?>% comision din fiecare comandă</strong>
                            <p class="text-muted mb-0">Câștigi un procent consistent din fiecare achiziție realizată de persoanele pe care le inviți.</p>
                        </div>
                    </li>
                    <li class="mb-3 d-flex align-items-start">
                        <i class="bi bi-infinity text-primary me-3 mt-1" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong>Fără limită de câștig</strong>
                            <p class="text-muted mb-0">Cu cât inviți mai mulți utilizatori activi, cu atât câștigi mai mult. Nu există plafon.</p>
                        </div>
                    </li>
                    <li class="mb-3 d-flex align-items-start">
                        <i class="bi bi-wallet2 text-warning me-3 mt-1" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong>Credit utilizabil sau retragere</strong>
                            <p class="text-muted mb-0">Folosește creditul pentru comenzi pe site sau solicită retragere bancară când depășești suma minimă.</p>
                        </div>
                    </li>
                    <li class="mb-3 d-flex align-items-start">
                        <i class="bi bi-graph-up text-info me-3 mt-1" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong>Totul se urmărește automat</strong>
                            <p class="text-muted mb-0">Vezi în timp real câștigurile tale, numărul de comenzi și statusul referral-urilor din contul tău.</p>
                        </div>
                    </li>
                </ul>
            </div>
            
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg p-5 bg-white">
                    <div class="text-center mb-4">
                        <i class="bi bi-gift text-primary" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="h4 fw-bold text-center mb-4">Exemplu de câștig</h3>
                    <div class="bg-light p-4 rounded mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Comandă referral:</span>
                            <strong>100 RON</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Comision (<?php echo number_format($commissionPercentage, 0); ?>%):</span>
                            <strong class="text-success">+<?php echo number_format(100 * ($commissionPercentage / 100), 0); ?> RON</strong>
                        </div>
                    </div>
                    <div class="bg-light p-4 rounded mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Comandă referral:</span>
                            <strong>500 RON</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Comision (<?php echo number_format($commissionPercentage, 0); ?>%):</span>
                            <strong class="text-success">+<?php echo number_format(500 * ($commissionPercentage / 100), 0); ?> RON</strong>
                        </div>
                    </div>
                    <p class="text-muted small mb-0 text-center">
                        <i class="bi bi-info-circle me-1"></i>
                        Comisionul este calculat automat la fiecare comandă plătită
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Final -->
<section class="py-5">
    <div class="container py-4">
        <div class="card border-0 shadow-lg bg-gradient-primary text-white">
            <div class="card-body p-5 text-center">
                <h2 class="fw-bold mb-3">Gata să începi?</h2>
                <p class="lead mb-4">
                    Alătură-te programului de referral și transformă recomandările în venit pasiv.
                </p>
                
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/pages/referral.php" class="btn btn-light btn-lg px-5">
                            <i class="bi bi-link-45deg me-2"></i>Accesează dashboard-ul de referral
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/pages/register.php" class="btn btn-light btn-lg px-5">
                            <i class="bi bi-person-plus me-2"></i>Creează cont gratuit
                        </a>
                        <a href="<?php echo SITE_URL; ?>/pages/login.php" class="btn btn-outline-light btn-lg px-5">
                            Autentifică-te
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Disclaimer -->
<section class="py-4 bg-light border-top">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <p class="text-muted small mb-0">
                    <i class="bi bi-shield-check me-2"></i>
                    Comisionul este acordat doar pentru comenzile plătite. 
                    Programul de referral este destinat utilizării corecte și poate fi suspendat în caz de abuz.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Custom Gradient Style -->
<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

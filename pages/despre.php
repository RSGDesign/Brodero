<?php
/**
 * Pagina Despre Noi
 * Prezintă povestea, misiunea și valorile Brodero
 */

$pageTitle = "Despre Noi";
$pageDescription = "Descoperă povestea Brodero și pasiunea noastră pentru design-uri de broderie unice și creative.";

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<section class="bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="display-4 fw-bold mb-3">Despre Brodero</h1>
                <p class="lead text-muted">Pasiunea noastră pentru broderie și creativitate</p>
            </div>
        </div>
    </div>
</section>

<!-- Povestea Noastră -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="position-relative about-image-wrapper">
                    <img src="<?php echo SITE_URL; ?>/assets/images/about/poza1.jpg" 
                         alt="Broderie manuală - Lucru artistic de înaltă calitate" 
                         class="img-fluid rounded-custom shadow-custom about-image">
                    <div class="position-absolute bottom-0 end-0 bg-primary text-white p-4 rounded-custom m-3 about-badge">
                        <h3 class="fw-bold mb-0">3+</h3>
                        <p class="mb-0">Ani de Experiență</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h2 class="section-title mb-4">Povestea Noastră</h2>
                <p class="text-muted mb-3">
                    Brodero s-a născut din pasiunea pentru artă și dorința de a aduce frumusețea broderiei în casele și 
                    proiectele creative ale oamenilor din întreaga lume. Totul a început în 2022, când am realizat că 
                    există o nevoie reală de design-uri de broderie de calitate, accesibile și diverse.
                </p>
                <p class="text-muted mb-3">
                    De atunci, am creat sute de modele unice, colaborând cu artiști talentați și ascultând cu atenție 
                    nevoile comunității noastre. Fiecare design este creat cu grijă, testat pe diverse materiale și 
                    optimizat pentru rezultate perfecte.
                </p>
                <p class="text-muted mb-4">
                    Astăzi, Brodero este mai mult decât un magazin online - este o comunitate de pasionați de broderie 
                    care împărtășesc aceeași dragoste pentru creativitate și artă.
                </p>
                <a href="<?php echo SITE_URL; ?>/pages/magazin.php" class="btn btn-primary">
                    Descoperă Colecția Noastră
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Misiunea Noastră -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-lg-2">
                <div class="about-image-wrapper">
                    <img src="<?php echo SITE_URL; ?>/assets/images/about/poza2.jpg" 
                         alt="Broderie în cerc - Design creativ și colorat" 
                         class="img-fluid rounded-custom shadow-custom about-image">
                </div>
            </div>
            <div class="col-lg-6 order-lg-1">
                <h2 class="section-title mb-4">Misiunea Noastră</h2>
                <p class="text-muted mb-3">
                    Misiunea noastră este să inspirăm creativitatea și să facem broderia accesibilă tuturor, indiferent 
                    de nivelul de experiență. Credem că fiecare persoană are potențialul de a crea lucruri minunate, și 
                    noi suntem aici pentru a le oferi instrumentele necesare.
                </p>
                
                <div class="d-flex gap-3 mb-3">
                    <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-check2"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-2">Calitate Premium</h5>
                        <p class="text-muted mb-0">
                            Oferim doar design-uri testate și optimizate pentru cele mai bune rezultate.
                        </p>
                    </div>
                </div>
                
                <div class="d-flex gap-3 mb-3">
                    <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-check2"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-2">Diversitate</h5>
                        <p class="text-muted mb-0">
                            De la motive tradiționale la design-uri moderne, avem ceva pentru fiecare gust.
                        </p>
                    </div>
                </div>
                
                <div class="d-flex gap-3">
                    <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-check2"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-2">Comunitate</h5>
                        <p class="text-muted mb-0">
                            Construim o comunitate de pasionați care se susțin și se inspiră reciproc.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Valorile Noastre -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Valorile Noastre</h2>
            <p class="section-subtitle">Principiile care ne ghidează în tot ce facem</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm text-center">
                    <div class="card-body p-4">
                        <div class="feature-icon mx-auto mb-3">
                            <i class="bi bi-heart-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Pasiune</h5>
                        <p class="text-muted mb-0">
                            Facem ceea ce iubim și punem suflet în fiecare design creat. Pasiunea noastră se 
                            reflectă în calitatea lucrărilor noastre.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm text-center">
                    <div class="card-body p-4">
                        <div class="feature-icon mx-auto mb-3">
                            <i class="bi bi-lightbulb-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Creativitate</h5>
                        <p class="text-muted mb-0">
                            Inovăm constant și explorăm noi stiluri și tehnici pentru a oferi design-uri unice 
                            și inspiraționale.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm text-center">
                    <div class="card-body p-4">
                        <div class="feature-icon mx-auto mb-3">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Integritate</h5>
                        <p class="text-muted mb-0">
                            Suntem transparenți, onești și ne respectăm promisiunile. Clienții noștri pot avea 
                            încredere deplină în noi.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm text-center">
                    <div class="card-body p-4">
                        <div class="feature-icon mx-auto mb-3">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Comunitate</h5>
                        <p class="text-muted mb-0">
                            Ascultăm feedback-ul clienților și construim relații pe termen lung bazate pe 
                            respect și sprijin reciproc.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h3 class="fw-bold mb-3">Abonează-te la newsletter pentru oferte exclusive!</h3>
                <p class="text-muted mb-lg-0">
                    Fii printre primii care află despre noile design-uri și reducerile speciale.
                </p>
            </div>
            <div class="col-lg-4">
                <form action="<?php echo SITE_URL; ?>/pages/newsletter.php" method="POST" class="newsletter-form needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="input-group">
                        <input type="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="Email-ul tău" 
                               required
                               pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                               title="Introducă o adresă de email validă">
                        <button type="submit" class="btn btn-primary">
                            Abonează-te
                        </button>
                        <div class="invalid-feedback">
                            Te rugăm să introduci un email valid.
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
.about-image-wrapper {
    overflow: hidden;
    border-radius: 0.5rem;
}

.about-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    max-height: 500px;
    transition: transform 0.3s ease;
}

.about-image:hover {
    transform: scale(1.03);
}

.about-badge {
    backdrop-filter: blur(10px);
    background-color: rgba(45, 55, 72, 0.9) !important;
}

@media (max-width: 768px) {
    .about-image {
        max-height: 350px;
    }
    
    .about-badge {
        padding: 0.75rem !important;
    }
    
    .about-badge h3 {
        font-size: 1.5rem;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

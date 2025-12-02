<?php
/**
 * Pagina principală - Acasă
 * Include banner hero și ultimele modele de design
 */

$pageTitle = "Acasă";
$pageDescription = "Brodero - Descoperă cele mai noi și creative design-uri de broderie. Transformăm ideile în artă brodată.";

require_once __DIR__ . '/includes/header.php';

// Obține produse featured
$db = getDB();
$query = "SELECT * FROM products WHERE is_featured = 1 AND is_active = 1 ORDER BY created_at DESC LIMIT 6";
$result = $db->query($query);
$featuredProducts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $featuredProducts[] = $row;
    }
}
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">
                    Design-uri de Broderie <span class="d-block">Unice și Creative</span>
                </h1>
                <p class="lead mb-4">
                    Descoperă o colecție exclusivă de modele de broderie create cu pasiune și atenție la detalii. 
                    De la motive tradiționale la design-uri moderne, avem tot ce ai nevoie pentru proiectele tale.
                </p>
                <div class="d-flex gap-3">
                    <a href="<?php echo SITE_URL; ?>/pages/magazin.php" class="btn btn-white btn-lg">
                        <i class="bi bi-shop me-2"></i>Explorează Magazinul
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/despre.php" class="btn btn-outline-light btn-lg">
                        Despre Noi
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center d-none d-lg-block">
                <i class="bi bi-flower3" style="font-size: 15rem; opacity: 0.2;"></i>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="feature-icon mx-auto">
                    <i class="bi bi-star-fill"></i>
                </div>
                <h4 class="fw-bold mb-3">Calitate Premium</h4>
                <p class="text-muted">
                    Toate design-urile noastre sunt create cu atenție la detalii și testate pentru rezultate perfecte.
                </p>
            </div>
            <div class="col-md-4 text-center">
                <div class="feature-icon mx-auto">
                    <i class="bi bi-download"></i>
                </div>
                <h4 class="fw-bold mb-3">Download Instant</h4>
                <p class="text-muted">
                    Descarcă fișierele imediat după achiziție, în format compatibil cu mașina ta de brodat.
                </p>
            </div>
            <div class="col-md-4 text-center">
                <div class="feature-icon mx-auto">
                    <i class="bi bi-headset"></i>
                </div>
                <h4 class="fw-bold mb-3">Suport Dedicat</h4>
                <p class="text-muted">
                    Echipa noastră este mereu disponibilă pentru a te ajuta cu orice întrebări sau probleme.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Latest Products Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Ultimele Modele</h2>
            <p class="section-subtitle">Descoperă cele mai noi design-uri adăugate în colecția noastră</p>
        </div>
        
        <?php if (!empty($featuredProducts)): ?>
        <div class="row g-4">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card product-card h-100 shadow-sm">
                    <?php if ($product['sale_price']): ?>
                        <span class="product-badge">REDUCERE</span>
                    <?php endif; ?>
                    
                    <img src="<?php echo $product['image'] ? SITE_URL . '/uploads/' . $product['image'] : 'https://via.placeholder.com/400x300?text=' . urlencode($product['name']); ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                    
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text text-muted flex-grow-1">
                            <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <?php if ($product['sale_price']): ?>
                                    <span class="product-price"><?php echo number_format($product['sale_price'], 2); ?> LEI</span>
                                    <span class="product-price-old"><?php echo number_format($product['price'], 2); ?> LEI</span>
                                <?php else: ?>
                                    <span class="product-price"><?php echo number_format($product['price'], 2); ?> LEI</span>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo SITE_URL; ?>/pages/produs.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-primary">
                                <i class="bi bi-eye me-1"></i>Detalii
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="<?php echo SITE_URL; ?>/pages/magazin.php" class="btn btn-primary btn-lg">
                Vezi Toate Produsele <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
            <p class="text-muted mt-3">În curând vom adăuga produse noi!</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
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
                <form action="<?php echo SITE_URL; ?>/pages/newsletter.php" method="POST" class="newsletter-form">
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" placeholder="Email-ul tău" required>
                        <button type="submit" class="btn btn-primary">
                            Abonează-te
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

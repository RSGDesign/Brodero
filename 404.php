<?php
/**
 * Pagina 404 - Not Found
 * Afișată când o pagină nu este găsită
 * 
 * IMPORTANT: Returnează HTTP 404 status (fără redirect)
 */

// Setează HTTP status code 404 ÎNAINTE de orice output
http_response_code(404);

$pageTitle = "Pagină Negăsită - 404";

// SEO: Previne indexarea paginii 404
$seoNoIndex = true;

require_once __DIR__ . '/includes/header.php';
?>

<!-- 404 Error Section -->
<section class="py-5 my-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center">
                <div class="error-content">
                    <!-- Error Icon -->
                    <div class="mb-4">
                        <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 8rem;"></i>
                    </div>
                    
                    <!-- Error Code -->
                    <h1 class="display-1 fw-bold text-primary mb-3">404</h1>
                    
                    <!-- Error Message -->
                    <h2 class="h3 mb-4">Oops! Pagina nu a fost găsită</h2>
                    
                    <p class="text-muted mb-4">
                        Ne pare rău, dar pagina pe care o cauți nu există sau a fost mutată.
                        <br>
                        Te rugăm să folosești meniul de navigare sau butonul de mai jos pentru a continua.
                    </p>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-house-door me-2"></i>Mergi la Pagina Principală
                        </a>
                        <a href="<?php echo SITE_URL; ?>/pages/magazin.php" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-shop me-2"></i>Explorează Magazinul
                        </a>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="mt-5">
                        <p class="text-muted mb-3">Sau explorează:</p>
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <a href="<?php echo SITE_URL; ?>/pages/magazin.php" class="text-decoration-none">
                                <i class="bi bi-shop me-1"></i>Magazin
                            </a>
                            <a href="<?php echo SITE_URL; ?>/pages/despre.php" class="text-decoration-none">
                                <i class="bi bi-info-circle me-1"></i>Despre Noi
                            </a>
                            <a href="<?php echo SITE_URL; ?>/pages/contact.php" class="text-decoration-none">
                                <i class="bi bi-envelope me-1"></i>Contact
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recommended Products -->
<section class="py-5 bg-light">
    <div class="container">
        <h3 class="text-center mb-4">Produse Recomandate</h3>
        <div class="row g-4">
            <?php
            // Obține 4 produse featured
            $db = getDB();
            $featuredProducts = $db->query("SELECT * FROM products WHERE is_featured = 1 AND is_active = 1 ORDER BY RAND() LIMIT 4")->fetch_all(MYSQLI_ASSOC);
            
            foreach ($featuredProducts as $product):
                $displayPrice = $product['sale_price'] ?: $product['price'];
                $hasDiscount = $product['sale_price'] > 0;
            ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm product-card">
                        <a href="<?php echo SITE_URL; ?>/pages/produs.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                            <img src="<?php echo $product['image'] ? SITE_URL . '/uploads/' . $product['image'] : SITE_URL . '/assets/images/placeholder.svg'; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="height: 200px; object-fit: cover;">
                            
                            <?php if ($hasDiscount): ?>
                                <span class="badge bg-danger position-absolute top-0 end-0 m-2">
                                    -<?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%
                                </span>
                            <?php endif; ?>
                        </a>
                        
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="<?php echo SITE_URL; ?>/pages/produs.php?id=<?php echo $product['id']; ?>" 
                                   class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h5>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <?php if ($hasDiscount): ?>
                                        <span class="text-muted text-decoration-line-through small">
                                            <?php echo number_format($product['price'], 2); ?> LEI
                                        </span>
                                        <br>
                                        <span class="h5 text-danger mb-0">
                                            <?php echo number_format($displayPrice, 2); ?> LEI
                                        </span>
                                    <?php else: ?>
                                        <span class="h5 mb-0"><?php echo number_format($displayPrice, 2); ?> LEI</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- JavaScript eliminat - NU mai facem redirect automat -->
<script>
// Tracking opțional pentru pagini 404 (Google Analytics)
if (typeof gtag !== 'undefined') {
    gtag('event', 'page_view', {
        'page_title': '404 Not Found',
        'page_location': window.location.href,
        'send_to': 'G-79KM6LDM6X'
    });
}
</script>

<style>
.error-content {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.product-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

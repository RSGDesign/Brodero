<?php
/**
 * Pagina Produs - Detalii complete produs
 * Afișare informații produs, galerie imagini, opțiuni achiziție
 */

$pageTitle = "Produs";

require_once __DIR__ . '/../includes/header.php';

// Verificare ID produs
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMessage("Produsul nu a fost găsit.", "danger");
    redirect('/pages/magazin.php');
}

$productId = (int)$_GET['id'];

// Obține detalii produs
$db = getDB();
$stmt = $db->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug 
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE p.id = ? AND p.is_active = 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setMessage("Produsul nu a fost găsit.", "danger");
    redirect('/pages/magazin.php');
}

$product = $result->fetch_assoc();
$stmt->close();

// Incrementare vizualizări
$db->query("UPDATE products SET views = views + 1 WHERE id = $productId");

// Obține produse similare
$similarProducts = [];
if ($product['category_id']) {
    $stmt = $db->prepare("SELECT * FROM products 
                          WHERE category_id = ? AND id != ? AND is_active = 1 
                          ORDER BY RAND() LIMIT 4");
    $stmt->bind_param("ii", $product['category_id'], $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $similarProducts[] = $row;
    }
    $stmt->close();
}

// Procesare galerie imagini
$gallery = [];
$allImages = []; // Array cu toate imaginile (principală + galerie)

// Adaugă imaginea principală
if (!empty($product['image'])) {
    $allImages[] = $product['image'];
}

// Adaugă imaginile din galerie
if (!empty($product['gallery'])) {
    $galleryImages = json_decode($product['gallery'], true);
    if (is_array($galleryImages)) {
        foreach ($galleryImages as $img) {
            $allImages[] = $img;
        }
    }
}

// Calculare preț final
$finalPrice = $product['sale_price'] ? $product['sale_price'] : $product['price'];
$discount = 0;
if ($product['sale_price']) {
    $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
}

$pageTitle = $product['name'];
$pageDescription = substr(strip_tags($product['description']), 0, 160);
?>

<!-- Breadcrumb -->
<section class="bg-light py-3">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Acasă</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/pages/magazin.php">Magazin</a></li>
                <?php if ($product['category_name']): ?>
                    <li class="breadcrumb-item">
                        <a href="<?php echo SITE_URL; ?>/pages/magazin.php?category=<?php echo $product['category_id']; ?>">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>
    </div>
</section>

<!-- Product Details -->
<section class="py-5">
    <div class="container">
        <div class="row g-5">
            <!-- Product Images -->
            <div class="col-lg-6">
                <div class="sticky-top" style="top: 100px;">
                    <!-- Main Image -->
                    <div class="card border-0 shadow-sm mb-3">
                        <img src="<?php echo !empty($allImages) ? SITE_URL . '/uploads/' . $allImages[0] : 'https://via.placeholder.com/600x450?text=' . urlencode($product['name']); ?>" 
                             class="card-img-top rounded" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             id="mainProductImage"
                             style="height: 450px; object-fit: cover;">
                        
                        <?php if ($discount > 0): ?>
                            <span class="position-absolute top-0 end-0 m-3 badge bg-danger" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                -<?php echo $discount; ?>%
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Gallery Thumbnails -->
                    <?php if (count($allImages) > 1): ?>
                        <div class="row g-2">
                            <?php foreach ($allImages as $index => $img): ?>
                                <div class="col-3">
                                    <img src="<?php echo SITE_URL . '/uploads/' . $img; ?>" 
                                         class="img-fluid rounded shadow-sm thumbnail-image <?php echo $index === 0 ? 'active-thumbnail' : ''; ?>" 
                                         style="cursor: pointer; height: 100px; object-fit: cover; width: 100%; border: 3px solid transparent; transition: border 0.3s;"
                                         onclick="changeMainImage('<?php echo SITE_URL . '/uploads/' . $img; ?>', this)"
                                         alt="Thumbnail <?php echo $index + 1; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Product Info -->
            <div class="col-lg-6">
                <!-- Category Badge -->
                <?php if ($product['category_name']): ?>
                    <a href="<?php echo SITE_URL; ?>/pages/magazin.php?category=<?php echo $product['category_id']; ?>" 
                       class="badge bg-primary text-decoration-none mb-2">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                <?php endif; ?>
                
                <!-- Product Title -->
                <h1 class="h2 fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <!-- Rating & Views (placeholder) -->
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="text-warning">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-half"></i>
                        <span class="text-dark ms-2">4.5</span>
                    </div>
                    <span class="text-muted">|</span>
                    <span class="text-muted">
                        <i class="bi bi-eye me-1"></i><?php echo number_format($product['views']); ?> vizualizări
                    </span>
                </div>
                
                <!-- Price -->
                <div class="mb-4">
                    <?php if ($product['sale_price']): ?>
                        <h3 class="text-primary fw-bold mb-0">
                            <?php echo number_format($finalPrice, 2); ?> LEI
                            <span class="h5 text-muted text-decoration-line-through ms-2">
                                <?php echo number_format($product['price'], 2); ?> LEI
                            </span>
                        </h3>
                        <p class="text-success mb-0">
                            <i class="bi bi-tag-fill me-1"></i>Economisești <?php echo number_format($product['price'] - $product['sale_price'], 2); ?> LEI
                        </p>
                    <?php else: ?>
                        <h3 class="text-primary fw-bold mb-0">
                            <?php echo number_format($finalPrice, 2); ?> LEI
                        </h3>
                    <?php endif; ?>
                </div>
                
                <!-- Stock Status -->
                <div class="mb-4">
                    <?php if ($product['stock_status'] === 'in_stock'): ?>
                        <span class="badge bg-success">
                            <i class="bi bi-check-circle me-1"></i>În Stoc - Disponibil
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger">
                            <i class="bi bi-x-circle me-1"></i>Stoc Epuizat
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Description -->
                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Descriere</h5>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                
                <!-- Features -->
                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Caracteristici</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Design digital de calitate premium
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Formate multiple: DST, PES, JEF, VP3, EXP
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Download instant după achiziție
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Licență de utilizare comercială
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Suport tehnic inclus
                        </li>
                    </ul>
                </div>
                
                <!-- Actions -->
                <div class="d-grid gap-2">
                    <?php if ($product['stock_status'] === 'in_stock'): ?>
                        <button type="button" class="btn btn-primary btn-lg" onclick="addToCart(<?php echo $product['id']; ?>)">
                            <i class="bi bi-cart-plus me-2"></i>Adaugă în Coș
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-heart me-2"></i>Adaugă la Favorite
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-lg" disabled>
                            <i class="bi bi-x-circle me-2"></i>Indisponibil
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Additional Info -->
                <div class="mt-4 p-3 bg-light rounded">
                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <i class="bi bi-shield-check text-primary fs-3 mb-2 d-block"></i>
                            <small class="text-muted">Plată Securizată</small>
                        </div>
                        <div class="col-4">
                            <i class="bi bi-download text-primary fs-3 mb-2 d-block"></i>
                            <small class="text-muted">Download Instant</small>
                        </div>
                        <div class="col-4">
                            <i class="bi bi-headset text-primary fs-3 mb-2 d-block"></i>
                            <small class="text-muted">Suport 24/7</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Similar Products -->
<?php if (!empty($similarProducts)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <h3 class="fw-bold mb-4">Produse Similare</h3>
        
        <div class="row g-4">
            <?php foreach ($similarProducts as $similar): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="card product-card h-100 shadow-sm">
                        <?php if ($similar['sale_price']): ?>
                            <span class="product-badge">
                                -<?php echo round((($similar['price'] - $similar['sale_price']) / $similar['price']) * 100); ?>%
                            </span>
                        <?php endif; ?>
                        
                        <img src="<?php echo $similar['image'] ? SITE_URL . '/uploads/' . $similar['image'] : 'https://via.placeholder.com/400x300?text=' . urlencode($similar['name']); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($similar['name']); ?>">
                        
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title"><?php echo htmlspecialchars($similar['name']); ?></h6>
                            <p class="card-text text-muted small flex-grow-1">
                                <?php echo htmlspecialchars(substr($similar['description'], 0, 80)) . '...'; ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div>
                                    <?php if ($similar['sale_price']): ?>
                                        <span class="fw-bold text-primary"><?php echo number_format($similar['sale_price'], 2); ?> LEI</span>
                                    <?php else: ?>
                                        <span class="fw-bold text-primary"><?php echo number_format($similar['price'], 2); ?> LEI</span>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo SITE_URL; ?>/pages/produs.php?id=<?php echo $similar['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    Vezi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
// Schimbare imagine principală
function changeMainImage(imageUrl, clickedThumbnail) {
    // Actualizează imaginea principală
    document.getElementById('mainProductImage').src = imageUrl;
    
    // Elimină border de la toate thumbnail-urile
    document.querySelectorAll('.thumbnail-image').forEach(img => {
        img.style.border = '3px solid transparent';
        img.classList.remove('active-thumbnail');
    });
    
    // Adaugă border la thumbnail-ul selectat
    if (clickedThumbnail) {
        clickedThumbnail.style.border = '3px solid #6366f1';
        clickedThumbnail.classList.add('active-thumbnail');
    }
}

// Setează border inițial pentru primul thumbnail
document.addEventListener('DOMContentLoaded', function() {
    const firstThumbnail = document.querySelector('.thumbnail-image.active-thumbnail');
    if (firstThumbnail) {
        firstThumbnail.style.border = '3px solid #6366f1';
    }
});

// Adăugare în coș
function addToCart(productId) {
    // Implementare funcționalitate coș
    showNotification('Produs adăugat în coș!', 'success');
}
</script>

<style>
.thumbnail-image:hover {
    opacity: 0.8;
    transform: scale(1.05);
}

.thumbnail-image {
    transition: all 0.3s ease;
}

.active-thumbnail {
    border: 3px solid #6366f1 !important;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

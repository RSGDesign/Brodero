<?php
/**
 * Pagina Produs - Detalii complete produs
 * Afișare informații produs, galerie imagini, opțiuni achiziție
 */

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_downloads.php';
require_once __DIR__ . '/../includes/functions_seo.php';

// Verificare slug produs
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    // Fallback pentru compatibilitate cu ID
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $productId = (int)$_GET['id'];
        $db = getDB();
        $stmt = $db->prepare("SELECT slug FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            redirect('/pages/produs.php?slug=' . $row['slug']);
        }
    }
    setMessage("Produsul nu a fost găsit.", "danger");
    redirect('/pages/magazin.php');
}

$productSlug = cleanInput($_GET['slug']);

// Obține detalii produs prin slug
$db = getDB();
$stmt = $db->prepare("SELECT p.* FROM products p WHERE p.slug = ? AND p.is_active = 1");
$stmt->bind_param("s", $productSlug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setMessage("Produsul nu a fost găsit.", "danger");
    redirect('/pages/magazin.php');
}

$product = $result->fetch_assoc();
$stmt->close();

// Obține categoriile produsului
$productCategories = getProductCategories($productId);
$product['categories'] = $productCategories;
$product['category_names'] = array_map(function($cat) {
    return $cat['name'];
}, $productCategories);

// Incrementare vizualizări (prepared statement for security)
$viewStmt = $db->prepare("UPDATE products SET views = views + 1 WHERE id = ?");
$viewStmt->bind_param("i", $productId);
$viewStmt->execute();
$viewStmt->close();

// Obține produse similare (din aceleași categorii)
$similarProducts = [];
if (!empty($productCategories)) {
    $categoryIds = array_map(function($cat) { return $cat['id']; }, $productCategories);
    $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
    
    $stmt = $db->prepare("SELECT DISTINCT p.* FROM products p
                          INNER JOIN product_categories pc ON p.id = pc.product_id
                          WHERE pc.category_id IN ($placeholders) 
                          AND p.id != ? 
                          AND p.is_active = 1 
                          ORDER BY RAND() LIMIT 4");
    
    $params = array_merge($categoryIds, [$productId]);
    $types = str_repeat('i', count($params));
    $stmt->bind_param($types, ...$params);
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
$pageDescription = sanitizeMetaDescription($product['description'], 160);
$pageKeywords = $product['name'] . ', broderie, design broderie, pattern';
$pageImage = !empty($product['image']) ? SITE_URL . '/uploads/' . $product['image'] : '';

// Generează Product Schema pentru SEO
echo generateProductSchema($product);
?>

<!-- Breadcrumb -->
<section class="bg-light py-3 border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Acasă</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/pages/magazin.php">Magazin</a></li>
                <?php if (!empty($productCategories)): ?>
                    <li class="breadcrumb-item">
                        <a href="<?php echo SITE_URL; ?>/pages/magazin.php?category=<?php echo $productCategories[0]['id']; ?>">
                            <?php echo htmlspecialchars($productCategories[0]['name']); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>
    </div>
</section>

<!-- Digital product badge and files list -->


<!-- Product Details -->
<section class="py-5">
    <div class="container">
        <div class="row g-5">
            <!-- Product Images -->
            <div class="col-lg-6">
                <div class="sticky-top" style="top: 100px;">
                    <!-- Main Image -->
                    <div class="card border-0 shadow-sm mb-3 position-relative">
                        <img src="<?php echo !empty($allImages) ? SITE_URL . '/uploads/' . $allImages[0] : 'https://via.placeholder.com/600x450?text=' . urlencode($product['name']); ?>" 
                             class="card-img-top rounded" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             id="mainProductImage"
                             style="height: 450px; object-fit: contain; cursor: zoom-in; background-color: #f8f9fa;"
                             onclick="openLightbox(0)"
                             title="">
                        
                        <?php if ($discount > 0): ?>
                            <span class="position-absolute top-0 end-0 m-3 badge bg-danger" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                -<?php echo $discount; ?>%
                            </span>
                        <?php endif; ?>
                        
                        <!-- Zoom Icon -->
                        <div class="position-absolute bottom-0 end-0 m-3">
                            <span class="badge bg-dark bg-opacity-75">
                                <i class="bi bi-zoom-in"></i> Click pentru mărire
                            </span>
                        </div>
                    </div>
                    
                    <!-- Gallery Thumbnails -->
                    <?php if (count($allImages) > 1): ?>
                        <div class="d-flex gap-2 overflow-auto pb-2" style="max-width: 100%;">
                            <?php foreach ($allImages as $index => $img): ?>
                                <div class="flex-shrink-0">
                                    <img src="<?php echo SITE_URL . '/uploads/' . $img; ?>" 
                                         class="rounded shadow-sm thumbnail-image <?php echo $index === 0 ? 'active-thumbnail' : ''; ?>" 
                                         style="cursor: pointer; height: 100px; width: 100px; object-fit: cover; border: 3px solid transparent;"
                                         onclick="changeMainImage('<?php echo SITE_URL . '/uploads/' . $img; ?>', this, <?php echo $index; ?>)"
                                         data-index="<?php echo $index; ?>"
                                         alt=""
                                         title="">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Product Info -->
            <div class="col-lg-6">
                <!-- Category Badges -->
                <?php if (!empty($product['category_names'])): ?>
                    <div class="mb-2">
                        <?php foreach ($productCategories as $cat): ?>
                            <a href="<?php echo SITE_URL; ?>/pages/magazin.php?category=<?php echo $cat['id']; ?>" 
                               class="badge bg-primary text-decoration-none me-1">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Product Title -->
                <h1 class="h2 fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                
                <!-- Price -->
                <div class="mb-4">
                    <?php if ($product['sale_price']): ?>
                        <h3 class="text-primary fw-bold mb-0">
                            <?php echo number_format($finalPrice, 2); ?> RON
                            <span class="h5 text-muted text-decoration-line-through ms-2">
                                <?php echo number_format($product['price'], 2); ?> RON
                            </span>
                        </h3>
                        <p class="text-success mb-0">
                            <i class="bi bi-tag-fill me-1"></i>Economisești <?php echo number_format($product['price'] - $product['sale_price'], 2); ?> RON
                        </p>
                    <?php else: ?>
                        <h3 class="text-primary fw-bold mb-0">
                            <?php echo number_format($finalPrice, 2); ?> RON
                        </h3>
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
                            Format: EMB
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
                    <button type="button" class="btn btn-primary btn-lg" onclick="addToCart(<?php echo $product['id']; ?>)">
                        <i class="bi bi-cart-plus me-2"></i>Adaugă în Coș
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-lg" onclick="addToFavorites(<?php echo $product['id']; ?>)">
                        <i class="bi bi-heart me-2"></i>Adaugă la Favorite
                    </button>
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
                                        <span class="fw-bold text-primary"><?php echo number_format($similar['sale_price'], 2); ?> RON</span>
                                    <?php else: ?>
                                        <span class="fw-bold text-primary"><?php echo number_format($similar['price'], 2); ?> RON</span>
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

<!-- Lightbox Modal pentru vizualizare imagini la rezoluție completă -->
<div class="modal fade" id="imageLightbox" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white">
                    <i class="bi bi-image me-2"></i><?php echo htmlspecialchars($product['name']); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center position-relative" style="min-height: 500px;">
                <!-- Imagine mare -->
                <img id="lightboxImage" src="" alt="" title="" class="img-fluid" style="max-height: 70vh; object-fit: contain;">
                
                <!-- Navigare stânga/dreapta -->
                <button class="btn btn-light position-absolute top-50 start-0 translate-middle-y ms-3" 
                        onclick="navigateLightbox(-1)" 
                        id="prevBtn"
                        style="opacity: 0.8; z-index: 10;"
                        title="Imagine anterioară">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="btn btn-light position-absolute top-50 end-0 translate-middle-y me-3" 
                        onclick="navigateLightbox(1)" 
                        id="nextBtn"
                        style="opacity: 0.8; z-index: 10;"
                        title="Imagine următoare">
                    <i class="bi bi-chevron-right"></i>
                </button>
                
                <!-- Counter -->
                <div class="position-absolute bottom-0 start-50 translate-middle-x mb-3">
                    <span class="badge bg-dark bg-opacity-75 text-white" id="imageCounter">1 / 1</span>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <!-- Thumbnail preview în modal -->
                <div class="d-flex gap-2 overflow-auto" id="lightboxThumbnails" style="max-width: 100%;"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Array cu toate imaginile pentru lightbox
const allProductImages = <?php echo json_encode(array_map(function($img) { return SITE_URL . '/uploads/' . $img; }, $allImages)); ?>;
let currentLightboxIndex = 0;

// Schimbare imagine principală
function changeMainImage(imageUrl, clickedThumbnail, index) {
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
    
    // Update current index pentru lightbox
    currentLightboxIndex = index;
}

// Deschide lightbox
function openLightbox(index) {
    currentLightboxIndex = index;
    updateLightboxImage();
    
    // Generează thumbnails pentru modal
    const thumbnailsContainer = document.getElementById('lightboxThumbnails');
    thumbnailsContainer.innerHTML = '';
    
    allProductImages.forEach((img, idx) => {
        const thumb = document.createElement('img');
        thumb.src = img;
        thumb.className = 'rounded shadow-sm';
        thumb.style.cssText = 'width: 60px; height: 60px; object-fit: cover; cursor: pointer; border: 2px solid transparent;';
        thumb.onclick = () => {
            currentLightboxIndex = idx;
            updateLightboxImage();
        };
        if (idx === index) {
            thumb.style.border = '2px solid #6366f1';
        }
        thumbnailsContainer.appendChild(thumb);
    });
    
    // Deschide modal
    const modal = new bootstrap.Modal(document.getElementById('imageLightbox'));
    modal.show();
}

// Update imagine în lightbox
function updateLightboxImage() {
    document.getElementById('lightboxImage').src = allProductImages[currentLightboxIndex];
    document.getElementById('imageCounter').textContent = `${currentLightboxIndex + 1} / ${allProductImages.length}`;
    
    // Update active thumbnail în modal
    const modalThumbs = document.querySelectorAll('#lightboxThumbnails img');
    modalThumbs.forEach((thumb, idx) => {
        thumb.style.border = idx === currentLightboxIndex ? '2px solid #6366f1' : '2px solid transparent';
    });
    
    // Ascunde/arată butoane navigare
    document.getElementById('prevBtn').style.display = currentLightboxIndex === 0 ? 'none' : 'block';
    document.getElementById('nextBtn').style.display = currentLightboxIndex === allProductImages.length - 1 ? 'none' : 'block';
}

// Navigare în lightbox
function navigateLightbox(direction) {
    currentLightboxIndex += direction;
    if (currentLightboxIndex < 0) currentLightboxIndex = 0;
    if (currentLightboxIndex >= allProductImages.length) currentLightboxIndex = allProductImages.length - 1;
    updateLightboxImage();
}

// Navigare cu taste (stânga/dreapta)
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('imageLightbox');
    if (modal.classList.contains('show')) {
        if (e.key === 'ArrowLeft') navigateLightbox(-1);
        if (e.key === 'ArrowRight') navigateLightbox(1);
        if (e.key === 'Escape') bootstrap.Modal.getInstance(modal).hide();
    }
});

// Setează border inițial pentru primul thumbnail
document.addEventListener('DOMContentLoaded', function() {
    const firstThumbnail = document.querySelector('.thumbnail-image.active-thumbnail');
    if (firstThumbnail) {
        firstThumbnail.style.border = '3px solid #6366f1';
    }
});

// Adăugare în coș
function addToCart(productId) {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    
    // Disable button
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Se adaugă...';
    
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count în header
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
                cartCount.style.display = 'inline-block';
                cartCount.classList.add('animate__animated', 'animate__pulse');
                setTimeout(() => {
                    cartCount.classList.remove('animate__animated', 'animate__pulse');
                }, 1000);
            }
            
            // Success feedback
            btn.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Adăugat!';
            btn.classList.replace('btn-primary', 'btn-success');
            
            showNotification('Produs adăugat în coș!', 'success');
            
            // Reset button after 2 seconds
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.replace('btn-success', 'btn-primary');
                btn.disabled = false;
            }, 2000);
        } else {
            btn.innerHTML = originalText;
            btn.disabled = false;
            showNotification(data.message || 'Eroare la adăugare în coș', 'danger');
        }
    })
    .catch(error => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        showNotification('Eroare la adăugare în coș', 'danger');
        console.error('Error:', error);
    });
}

// Adăugare la favorite
function addToFavorites(productId) {
    const btn = event.target.closest('button');
    const icon = btn.querySelector('i');
    
    // Check if user is logged in
    <?php if (!isLoggedIn()): ?>
    showNotification('Trebuie să fii autentificat pentru a adăuga la favorite', 'warning');
    setTimeout(() => {
        window.location.href = '<?php echo SITE_URL; ?>/pages/login.php';
    }, 1500);
    return;
    <?php endif; ?>
    
    showNotification('Funcționalitatea de favorite va fi disponibilă în curând!', 'info');
    
    // Temporary visual feedback
    icon.classList.toggle('bi-heart');
    icon.classList.toggle('bi-heart-fill');
    btn.classList.toggle('btn-outline-primary');
    btn.classList.toggle('btn-primary');
}
</script>

<style>
.thumbnail-image {
    transition: all 0.3s ease;
}

.thumbnail-image:hover {
    opacity: 0.8;
    transform: scale(1.05);
}

.active-thumbnail {
    border: 3px solid #6366f1 !important;
}

#mainProductImage:hover {
    opacity: 0.95;
}

/* Lightbox styles */
#imageLightbox .modal-content {
    background-color: rgba(0, 0, 0, 0.95) !important;
}

#imageLightbox .btn-light:hover {
    opacity: 1 !important;
}

/* Scrollbar pentru thumbnails */
.overflow-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.overflow-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

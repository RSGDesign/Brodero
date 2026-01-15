<?php
/**
 * Pagina Magazin
 * Afișare produse cu filtrare, sortare și pagination
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/seo.php';

// Încarcă SEO din baza de date
$db = getPDO();
$seo = getSeoForPage('magazin', $db);

if ($seo) {
    $pageTitle = $seo['title'];
    $pageDescription = $seo['description'];
    $pageKeywords = $seo['keywords'];
} else {
    $pageTitle = "Magazin";
    $pageDescription = "Explorează colecția noastră completă de design-uri de broderie premium.";
}

require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Parametri pentru filtrare și sortare
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000;
$sortBy = isset($_GET['sort']) ? cleanInput($_GET['sort']) : 'newest';
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : PRODUCTS_PER_PAGE;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Construire filtre pentru funcția getProductsWithFilters
$filters = [
    'is_active' => 1,
    'min_price' => $minPrice,
    'max_price' => $maxPrice
];

if ($category > 0) {
    $filters['category_ids'] = [$category];
}

if (!empty($search)) {
    $filters['search'] = $search;
}

// Sortare
$orderByMap = [
    'price_asc' => 'price_asc',
    'price_desc' => 'price_desc',
    'name_asc' => 'name_asc',
    'popular' => 'popular',
    'newest' => 'newest'
];
$filters['order_by'] = isset($orderByMap[$sortBy]) ? $orderByMap[$sortBy] : 'newest';

// Obține total produse cu filtrele aplicate
$totalProducts = countProductsWithFilters($filters);
$totalPages = ceil($totalProducts / $perPage);

// Obține produse folosind funcția cu many-to-many
$products = getProductsWithFilters($filters, $perPage, $offset);

// Adaugă categoriile pentru fiecare produs (pentru afișare)
foreach ($products as &$product) {
    $product['categories'] = getProductCategories($product['id']);
    $product['category_names'] = array_map(function($cat) {
        return $cat['name'];
    }, $product['categories']);
    
    // MVP: Verificare dacă produsul a fost cumpărat
    $product['is_purchased'] = hasUserPurchasedProduct($product['id']);
}
// CRITICAL FIX: Unset reference to avoid issues in subsequent foreach loops
unset($product);

// Obține categorii pentru filtre
$categoriesQuery = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order, name";
$categoriesResult = $db->query($categoriesQuery);
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}
?>

<!-- Page Header -->
<section class="bg-light py-4 border-bottom">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="h2 fw-bold mb-0 text-dark">Magazin</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Acasă</a></li>
                        <li class="breadcrumb-item active">Magazin</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>

<!-- Products Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3 mb-4">
                <div class="filter-section sticky-top" style="top: 100px;">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-funnel me-2"></i>Filtrare
                        <span id="filter-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status">
                            <span class="visually-hidden">Se încarcă...</span>
                        </span>
                    </h5>
                    
                    <form id="filter-form" method="GET" action="">
                        <!-- Căutare -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Căutare</label>
                            <input type="text" 
                                   id="filter-search" 
                                   name="search" 
                                   class="form-control auto-filter" 
                                   placeholder="Caută produse..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <!-- Categorii -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Categorii</label>
                            <select id="filter-category" 
                                    name="category" 
                                    class="form-select auto-filter">
                                <option value="0">Toate categoriile</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Preț -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Preț (RON)</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" 
                                           id="filter-min-price" 
                                           name="min_price" 
                                           class="form-control auto-filter-debounce" 
                                           placeholder="Min" 
                                           value="<?php echo $minPrice; ?>" 
                                           min="0">
                                </div>
                                <div class="col-6">
                                    <input type="number" 
                                           id="filter-max-price" 
                                           name="max_price" 
                                           class="form-control auto-filter-debounce" 
                                           placeholder="Max" 
                                           value="<?php echo $maxPrice; ?>" 
                                           min="0">
                                </div>
                            </div>
                        </div>
                        
                        <a href="<?php echo SITE_URL; ?>/pages/magazin.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Resetează Filtre
                        </a>
                    </form>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-lg-9">
                <!-- Toolbar -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <div>
                        <p class="mb-0 text-muted">
                            Afișare <strong><?php echo min($offset + 1, $totalProducts); ?>-<?php echo min($offset + $perPage, $totalProducts); ?></strong> 
                            din <strong><?php echo $totalProducts; ?></strong> produse
                        </p>
                    </div>
                    
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <!-- Sortare -->
                        <div class="d-flex gap-2">
                            <select id="filter-sort" 
                                    name="sort" 
                                    class="form-select form-select-sm auto-filter">
                                <option value="newest" <?php echo $sortBy == 'newest' ? 'selected' : ''; ?>>Cele mai noi</option>
                                <option value="popular" <?php echo $sortBy == 'popular' ? 'selected' : ''; ?>>Populare</option>
                                <option value="price_asc" <?php echo $sortBy == 'price_asc' ? 'selected' : ''; ?>>Preț crescător</option>
                                <option value="price_desc" <?php echo $sortBy == 'price_desc' ? 'selected' : ''; ?>>Preț descrescător</option>
                                <option value="name_asc" <?php echo $sortBy == 'name_asc' ? 'selected' : ''; ?>>Nume A-Z</option>
                            </select>
                            
                            <select id="filter-per-page" 
                                    name="per_page" 
                                    class="form-select form-select-sm auto-filter">
                                <option value="12" <?php echo $perPage == 12 ? 'selected' : ''; ?>>12 produse</option>
                                <option value="24" <?php echo $perPage == 24 ? 'selected' : ''; ?>>24 produse</option>
                                <option value="48" <?php echo $perPage == 48 ? 'selected' : ''; ?>>48 produse</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Products -->
                <div id="products-container" class="position-relative">
                    <!-- Overlay loader -->
                    <div id="products-loader" class="position-absolute top-0 start-0 w-100 h-100 d-none" style="background: rgba(255,255,255,0.8); z-index: 10; min-height: 400px;">
                        <div class="d-flex justify-content-center align-items-center h-100">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Se încarcă...</span>
                                </div>
                                <p class="mt-3 text-muted fw-bold">Se actualizează produsele...</p>
                            </div>
                        </div>
                    </div>
                    
                <?php if (!empty($products)): ?>
                    <div class="row g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="card product-card h-100 shadow-sm">
                                    <?php if ($product['sale_price']): ?>
                                        <span class="product-badge">
                                            -<?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%
                                        </span>
                                    <?php endif; ?>
                                    
                                    <!-- MVP: Badge "Deja cumpărat" -->
                                    <?php if ($product['is_purchased']): ?>
                                        <span class="position-absolute top-0 start-0 m-2 badge bg-success" style="z-index: 10;">
                                            <i class="bi bi-check-circle-fill me-1"></i>Deja cumpărat
                                        </span>
                                    <?php endif; ?>
                                    
                                    <img src="<?php echo $product['image'] ? SITE_URL . '/uploads/' . $product['image'] : 'https://via.placeholder.com/400x300?text=' . urlencode($product['name']); ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         loading="lazy"
                                         width="400"
                                         height="300">
                                    
                                    <div class="card-body d-flex flex-column">
                                        <?php if (!empty($product['category_names'])): ?>
                                            <div class="mb-2">
                                                <?php foreach ($product['category_names'] as $catName): ?>
                                                    <span class="badge bg-light text-primary me-1"><?php echo htmlspecialchars($catName); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="card-text text-muted flex-grow-1">
                                            <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                                        </p>
                                        
                                        <div class="product-card-footer">
                                            <div class="product-price-container">
                                                <?php if ($product['sale_price']): ?>
                                                    <span class="product-price"><?php echo number_format($product['sale_price'], 2); ?> RON</span>
                                                    <span class="product-price-old"><?php echo number_format($product['price'], 2); ?> RON</span>
                                                <?php else: ?>
                                                    <span class="product-price"><?php echo number_format($product['price'], 2); ?> RON</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-actions">
                                                <a href="<?php echo SITE_URL; ?>/pages/produs.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm product-details-btn">
                                                    <i class="bi bi-eye me-1"></i>Detalii
                                                </a>
                                                <?php if ($product['is_purchased']): ?>
                                                    <!-- Produs deja cumpărat - buton dezactivat -->
                                                    <button type="button" class="btn btn-success btn-sm" disabled>
                                                        <i class="bi bi-check-circle me-1"></i>Deținut
                                                    </button>
                                                <?php else: ?>
                                                    <!-- Produs necumpărat - buton normal -->
                                                    <button type="button" class="btn btn-primary btn-sm add-to-cart-btn" 
                                                            data-product-id="<?php echo $product['id']; ?>">
                                                        <i class="bi bi-cart-plus me-1"></i>Coș
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-5">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3">Nu au fost găsite produse</h4>
                        <p class="text-muted">Încearcă să modifici filtrele sau caută altceva.</p>
                        <a href="<?php echo SITE_URL; ?>/pages/magazin.php" class="btn btn-primary mt-3">
                            Resetează Filtrele
                        </a>
                    </div>
                <?php endif; ?>
                </div><!-- #products-container -->
            </div>
        </div>
    </div>
</section>

<script>
/**
 * ===================================
 * FILTRARE AUTOMATĂ INSTANT
 * ===================================
 * Aplică filtrele automat la schimbarea oricărui element
 * fără necesitatea apăsării unui buton.
 */

(function() {
    'use strict';
    
    // Debounce pentru input-uri (300ms)
    let debounceTimer;
    function debounce(callback, delay) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(callback, delay);
    }
    
    /**
     * Construiește URL-ul cu parametrii actuali din filtre
     * Elimină parametrii goali sau cu valori default
     */
    function buildFilterURL() {
        const params = new URLSearchParams();
        
        // Căutare
        const search = document.getElementById('filter-search')?.value.trim();
        if (search) {
            params.set('search', search);
        }
        
        // Categorie
        const category = document.getElementById('filter-category')?.value;
        if (category && category !== '0') {
            params.set('category', category);
        }
        
        // Preț minim
        const minPrice = document.getElementById('filter-min-price')?.value;
        if (minPrice && minPrice !== '0') {
            params.set('min_price', minPrice);
        }
        
        // Preț maxim
        const maxPrice = document.getElementById('filter-max-price')?.value;
        if (maxPrice && maxPrice !== '1000') {
            params.set('max_price', maxPrice);
        }
        
        // Sortare
        const sort = document.getElementById('filter-sort')?.value;
        if (sort && sort !== 'newest') {
            params.set('sort', sort);
        }
        
        // Produse per pagină
        const perPage = document.getElementById('filter-per-page')?.value;
        if (perPage && perPage !== '12') {
            params.set('per_page', perPage);
        }
        
        // Resetează pagina la 1 când se schimbă filtrele
        // (păstrează pagina curentă doar dacă nu s-a schimbat nimic)
        const currentParams = new URLSearchParams(window.location.search);
        const currentPage = currentParams.get('page');
        if (currentPage && currentPage !== '1') {
            // Verifică dacă s-a schimbat vreun filtru
            const hasFilterChange = 
                params.toString() !== currentParams.toString().replace(/&?page=\d+/, '');
            
            if (!hasFilterChange) {
                params.set('page', currentPage);
            }
        }
        
        return params.toString() ? '?' + params.toString() : window.location.pathname;
    }
    
    /**
     * Aplică filtrele și reîncarcă pagina
     */
    function applyFilters() {
        const url = buildFilterURL();
        
        // Afișează loader
        const productsLoader = document.getElementById('products-loader');
        const filterLoader = document.getElementById('filter-loader');
        
        if (productsLoader) {
            productsLoader.classList.remove('d-none');
        }
        if (filterLoader) {
            filterLoader.classList.remove('d-none');
        }
        
        // Redirecționează la URL-ul nou
        window.location.href = url;
    }
    
    /**
     * Inițializare evenimente pentru filtrare automată
     */
    function initAutoFilters() {
        // Filtre instant (select, checkbox, radio)
        document.querySelectorAll('.auto-filter').forEach(element => {
            element.addEventListener('change', function() {
                console.log('Filter changed:', this.name, '=', this.value);
                applyFilters();
            });
        });
        
        // Filtre cu debounce (input text, number, range)
        document.querySelectorAll('.auto-filter-debounce').forEach(element => {
            // Detectează Enter pentru aplicare instant
            element.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    applyFilters();
                }
            });
            
            // Aplicare cu debounce la scriere
            element.addEventListener('input', function() {
                console.log('Debounced filter changed:', this.name, '=', this.value);
                debounce(() => {
                    applyFilters();
                }, 300);
            });
        });
        
        console.log('✓ Filtrare automată inițializată cu succes');
    }
    
    // Inițializare când DOM-ul este gata
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAutoFilters);
    } else {
        initAutoFilters();
    }
    
})();

// Add to cart functionality pentru magazin
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const originalHTML = this.innerHTML;
        
        // Disable button
        this.disabled = true;
        this.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        
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
                // Update cart count
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
                this.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
                this.classList.add('btn-success');
                this.classList.remove('btn-primary');
                
                // Show toast notification
                if (typeof showNotification === 'function') {
                    showNotification('Produs adăugat în coș!', 'success');
                }
                
                // Reset after 2 seconds
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-primary');
                    this.disabled = false;
                }, 2000);
            } else {
                this.innerHTML = originalHTML;
                this.disabled = false;
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Eroare la adăugare', 'danger');
                } else {
                    alert(data.message || 'Eroare la adăugare');
                }
            }
        })
        .catch(error => {
            this.innerHTML = originalHTML;
            this.disabled = false;
            console.error('Error:', error);
            if (typeof showNotification === 'function') {
                showNotification('Eroare la adăugare în coș', 'danger');
            } else {
                alert('Eroare la adăugare în coș');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

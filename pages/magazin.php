<?php
/**
 * Pagina Magazin
 * Afișare produse cu filtrare, sortare și pagination
 */

$pageTitle = "Magazin";
$pageDescription = "Explorează colecția noastră completă de design-uri de broderie premium.";

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

// Construire query
$whereConditions = ["p.is_active = 1"];
$params = [];
$types = "";

if ($category > 0) {
    $whereConditions[] = "p.category_id = ?";
    $params[] = $category;
    $types .= "i";
}

if (!empty($search)) {
    $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

$whereConditions[] = "COALESCE(p.sale_price, p.price) >= ?";
$whereConditions[] = "COALESCE(p.sale_price, p.price) <= ?";
$params[] = $minPrice;
$params[] = $maxPrice;
$types .= "dd";

$whereClause = implode(" AND ", $whereConditions);

// Sortare
switch ($sortBy) {
    case 'price_asc':
        $orderBy = "COALESCE(p.sale_price, p.price) ASC";
        break;
    case 'price_desc':
        $orderBy = "COALESCE(p.sale_price, p.price) DESC";
        break;
    case 'name_asc':
        $orderBy = "p.name ASC";
        break;
    case 'popular':
        $orderBy = "p.views DESC";
        break;
    default:
        $orderBy = "p.created_at DESC";
}

// Count total produse
$countQuery = "SELECT COUNT(*) as total FROM products p WHERE $whereClause";
$stmt = $db->prepare($countQuery);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalProducts = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalProducts / $perPage);

// Obține produse
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE $whereClause 
          ORDER BY $orderBy 
          LIMIT ? OFFSET ?";

$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

// Obține categorii pentru filtre
$categoriesQuery = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order, name";
$categoriesResult = $db->query($categoriesQuery);
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}
?>

<!-- Page Header -->
<section class="bg-light py-4">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="h2 fw-bold mb-0">Magazin</h1>
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
                    </h5>
                    
                    <form method="GET" action="">
                        <!-- Căutare -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Căutare</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Caută produse..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <!-- Categorii -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Categorii</label>
                            <select name="category" class="form-select">
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
                            <label class="form-label fw-bold">Preț (LEI)</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" name="min_price" class="form-control" 
                                           placeholder="Min" value="<?php echo $minPrice; ?>" min="0">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="max_price" class="form-control" 
                                           placeholder="Max" value="<?php echo $maxPrice; ?>" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-2"></i>Aplică Filtre
                        </button>
                        <a href="<?php echo SITE_URL; ?>/pages/magazin.php" class="btn btn-outline-secondary w-100 mt-2">
                            Resetează
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
                        <form method="GET" class="d-flex gap-2">
                            <?php if ($category > 0): ?>
                                <input type="hidden" name="category" value="<?php echo $category; ?>">
                            <?php endif; ?>
                            <?php if (!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                            
                            <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="newest" <?php echo $sortBy == 'newest' ? 'selected' : ''; ?>>Cele mai noi</option>
                                <option value="popular" <?php echo $sortBy == 'popular' ? 'selected' : ''; ?>>Populare</option>
                                <option value="price_asc" <?php echo $sortBy == 'price_asc' ? 'selected' : ''; ?>>Preț crescător</option>
                                <option value="price_desc" <?php echo $sortBy == 'price_desc' ? 'selected' : ''; ?>>Preț descrescător</option>
                                <option value="name_asc" <?php echo $sortBy == 'name_asc' ? 'selected' : ''; ?>>Nume A-Z</option>
                            </select>
                            
                            <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="12" <?php echo $perPage == 12 ? 'selected' : ''; ?>>12 produse</option>
                                <option value="24" <?php echo $perPage == 24 ? 'selected' : ''; ?>>24 produse</option>
                                <option value="48" <?php echo $perPage == 48 ? 'selected' : ''; ?>>48 produse</option>
                            </select>
                        </form>
                    </div>
                </div>
                
                <!-- Products -->
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
                                    
                                    <img src="<?php echo $product['image'] ? SITE_URL . '/uploads/' . $product['image'] : 'https://via.placeholder.com/400x300?text=' . urlencode($product['name']); ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    
                                    <div class="card-body d-flex flex-column">
                                        <?php if ($product['category_name']): ?>
                                            <span class="badge bg-light text-primary mb-2 align-self-start">
                                                <?php echo htmlspecialchars($product['category_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                        
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
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-primary btn-sm add-to-cart-btn" 
                                                        data-product-id="<?php echo $product['id']; ?>">
                                                    <i class="bi bi-cart-plus"></i>
                                                </button>
                                                <a href="<?php echo SITE_URL; ?>/pages/produs.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    Detalii
                                                </a>
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
            </div>
        </div>
    </div>
</section>

<script>
// Add to cart functionality pentru magazin
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const originalHTML = this.innerHTML;
        
        // Disable button
        this.disabled = true;
        this.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        
        fetch('/pages/add_to_cart.php', {
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
                    cartCount.classList.add('animate__animated', 'animate__pulse');
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

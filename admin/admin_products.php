<?php
/**
 * Gestionare Produse Admin
 * Listare, adăugare, editare, ștergere produse
 */

// Include config ÎNAINTE de orice output
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificare acces admin ÎNAINTE de header
if (!isLoggedIn() || !isAdmin()) {
    setMessage("Nu ai acces la această pagină.", "danger");
    redirect('/');
}

$db = getDB();

// Procesare ștergere produs ÎNAINTE de includerea header.php
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    
    // Obține informații produs pentru ștergere imagini
    $stmt = $db->prepare("SELECT image, gallery FROM products WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Șterge imaginea principală
        if (!empty($product['image']) && file_exists(UPLOAD_PATH . $product['image'])) {
            unlink(UPLOAD_PATH . $product['image']);
        }
        
        // Șterge galeria
        if (!empty($product['gallery'])) {
            $gallery = json_decode($product['gallery'], true);
            if (is_array($gallery)) {
                foreach ($gallery as $img) {
                    if (file_exists(UPLOAD_PATH . $img)) {
                        unlink(UPLOAD_PATH . $img);
                    }
                }
            }
        }
        
        // Șterge din baza de date
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        
        if ($stmt->execute()) {
            setMessage("Produsul a fost șters cu succes!", "success");
        } else {
            setMessage("Eroare la ștergerea produsului.", "danger");
        }
        $stmt->close();
    }
    
    redirect('/admin/admin_products.php');
    exit; // IMPORTANT: oprește execuția aici
}

// Acum includem header.php DUPĂ procesarea ștergerii
$pageTitle = "Gestionare Produse";
require_once __DIR__ . '/../includes/header.php';

// Obține toate produsele
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Count total produse
$totalProducts = $db->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$totalPages = ceil($totalProducts / $perPage);

// Obține produse (fără JOIN pe categories - folosim funcția getProductCategories)
$query = "SELECT p.* 
          FROM products p 
          ORDER BY p.created_at DESC 
          LIMIT $perPage OFFSET $offset";
$products = $db->query($query)->fetch_all(MYSQLI_ASSOC);

// Adaugă categoriile pentru fiecare produs
foreach ($products as &$product) {
    $categories = getProductCategories($product['id']);
    $product['categories'] = $categories;
    $product['category_names'] = array_map(function($cat) {
        return $cat['name'];
    }, $categories);
}
// CRITICAL FIX: Unset reference to avoid issues in subsequent foreach loops
unset($product);

// Obține categorii pentru filtru
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<!-- Page Header -->
<section class="bg-dark text-white py-4">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-box-seam me-2"></i>Gestionare Produse
                </h1>
                <p class="mb-0 text-white-50">Total: <?php echo $totalProducts; ?> produse</p>
            </div>
            <div>
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline-light me-2">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/add_product.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Adaugă Produs Nou
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Products Management -->
<section class="py-4">
    <div class="container-fluid">
        <!-- Filtru rapid -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Căutare produs..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">Toate categoriile</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">Toate statusurile</option>
                            <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-1"></i>Caută
                        </button>
                        <a href="<?php echo SITE_URL; ?>/admin/admin_products.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">ID</th>
                                <th style="width: 100px;">Imagine</th>
                                <th>Denumire</th>
                                <th>Categorie</th>
                                <th>Preț</th>
                                <th>Status</th>
                                <th>Vizualizări</th>
                                <th>Data Adăugare</th>
                                <th style="width: 200px;" class="text-center">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td class="fw-bold">#<?php echo $product['id']; ?></td>
                                        <td>
                                            <img src="<?php echo $product['image'] ? SITE_URL . '/uploads/' . $product['image'] : SITE_URL . '/assets/images/placeholder.svg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                 class="img-thumbnail"
                                                 style="width: 60px; height: 60px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <?php if ($product['is_featured']): ?>
                                                <span class="badge bg-warning text-dark ms-2">Featured</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($product['category_names'])): ?>
                                                <?php foreach ($product['category_names'] as $catName): ?>
                                                    <span class="badge bg-info me-1 mb-1"><?php echo htmlspecialchars($catName); ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($product['sale_price']): ?>
                                                <span class="text-danger fw-bold"><?php echo number_format($product['sale_price'], 2); ?> LEI</span>
                                                <br>
                                                <small class="text-muted text-decoration-line-through"><?php echo number_format($product['price'], 2); ?> LEI</small>
                                            <?php else: ?>
                                                <span class="fw-bold"><?php echo number_format($product['price'], 2); ?> LEI</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($product['is_active']): ?>
                                                <span class="badge bg-success">Activ</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactiv</span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="badge bg-<?php echo $product['stock_status'] === 'in_stock' ? 'success' : 'danger'; ?> mt-1">
                                                <?php echo $product['stock_status'] === 'in_stock' ? 'În stoc' : 'Epuizat'; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <i class="bi bi-eye text-muted me-1"></i>
                                            <?php echo number_format($product['views']); ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d.m.Y', strtotime($product['created_at'])); ?>
                                                <br>
                                                <?php echo date('H:i', strtotime($product['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?php echo SITE_URL; ?>/pages/produs.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-outline-info" 
                                                   target="_blank"
                                                   title="Vizualizare">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo SITE_URL; ?>/admin/edit_product.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-outline-primary"
                                                   title="Editare">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?delete=<?php echo $product['id']; ?>" 
                                                   class="btn btn-outline-danger btn-delete"
                                                   title="Ștergere"
                                                   onclick="return confirm('Ești sigur că vrei să ștergi acest produs? Această acțiune nu poate fi anulată!');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3">Nu există produse în baza de date.</p>
                                        <a href="<?php echo SITE_URL; ?>/admin/add_product.php" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-2"></i>Adaugă Primul Produs
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

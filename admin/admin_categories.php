<?php
/**
 * Gestionare Categorii Admin
 * Listare, adăugare, editare, ștergere categorii
 */

$pageTitle = "Gestionare Categorii";

require_once __DIR__ . '/../includes/header.php';

// Verificare acces admin
if (!isAdmin()) {
    setMessage("Nu ai acces la această pagină.", "danger");
    redirect('/');
}

$db = getDB();

// Procesare ștergere categorie
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    
    // Verifică dacă există produse în această categorie
    $checkProducts = $db->query("SELECT COUNT(*) as count FROM products WHERE category_id = $deleteId");
    $productCount = $checkProducts->fetch_assoc()['count'];
    
    if ($productCount > 0) {
        setMessage("Nu poți șterge această categorie deoarece conține $productCount produse. Mută sau șterge produsele mai întâi.", "danger");
    } else {
        // Șterge categoria
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        
        if ($stmt->execute()) {
            setMessage("Categoria a fost ștearsă cu succes!", "success");
        } else {
            setMessage("Eroare la ștergerea categoriei.", "danger");
        }
        $stmt->close();
    }
    
    redirect('/admin/admin_categories.php');
}

// Obține toate categoriile
$categories = $db->query("SELECT c.*, 
                          (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count 
                          FROM categories c 
                          ORDER BY c.display_order ASC, c.name ASC")->fetch_all(MYSQLI_ASSOC);

// Statistici
$totalCategories = count($categories);
$activeCategories = count(array_filter($categories, function($cat) { return $cat['is_active']; }));
$totalProducts = $db->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
?>

<!-- Page Header -->
<section class="bg-dark text-white py-4">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-tags me-2"></i>Gestionare Categorii
                </h1>
                <p class="mb-0 text-white-50">Total: <?php echo $totalCategories; ?> categorii (<?php echo $activeCategories; ?> active)</p>
            </div>
            <div>
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline-light me-2">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/add_category.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Adaugă Categorie Nouă
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Categories Management -->
<section class="py-4">
    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Categorii</p>
                                <h3 class="fw-bold mb-0"><?php echo $totalCategories; ?></h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <i class="bi bi-tags text-primary" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Categorii Active</p>
                                <h3 class="fw-bold mb-0"><?php echo $activeCategories; ?></h3>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Produse</p>
                                <h3 class="fw-bold mb-0"><?php echo $totalProducts; ?></h3>
                            </div>
                            <div class="bg-info bg-opacity-10 rounded p-3">
                                <i class="bi bi-box-seam text-info" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th style="width: 100px;">Imagine</th>
                                <th>Nume Categorie</th>
                                <th>Slug</th>
                                <th>Descriere</th>
                                <th class="text-center">Produse</th>
                                <th class="text-center">Ordine</th>
                                <th class="text-center">Status</th>
                                <th style="width: 200px;" class="text-center">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td class="fw-bold">#<?php echo $category['id']; ?></td>
                                        <td>
                                            <?php if ($category['image']): ?>
                                                <img src="<?php echo SITE_URL . '/uploads/' . $category['image']; ?>" 
                                                     alt=""
                                                     title=""
                                                     class="img-thumbnail"
                                                     style="width: 60px; height: 60px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                     style="width: 60px; height: 60px;">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                        </td>
                                        <td>
                                            <code class="text-muted"><?php echo htmlspecialchars($category['slug']); ?></code>
                                        </td>
                                        <td>
                                            <?php if ($category['description']): ?>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars(substr($category['description'], 0, 80)) . (strlen($category['description']) > 80 ? '...' : ''); ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($category['product_count'] > 0): ?>
                                                <a href="<?php echo SITE_URL; ?>/admin/admin_products.php?category=<?php echo $category['id']; ?>" 
                                                   class="badge bg-primary text-decoration-none">
                                                    <?php echo $category['product_count']; ?> produse
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">0 produse</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?php echo $category['display_order']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($category['is_active']): ?>
                                                <span class="badge bg-success">Activ</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactiv</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?php echo SITE_URL; ?>/pages/magazin.php?category=<?php echo $category['id']; ?>" 
                                                   class="btn btn-outline-info" 
                                                   target="_blank"
                                                   title="Vizualizare">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo SITE_URL; ?>/admin/edit_category.php?id=<?php echo $category['id']; ?>" 
                                                   class="btn btn-outline-primary"
                                                   title="Editare">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?delete=<?php echo $category['id']; ?>" 
                                                   class="btn btn-outline-danger"
                                                   title="Ștergere"
                                                   onclick="return confirm('Ești sigur că vrei să ștergi această categorie?\n\n<?php echo $category['product_count'] > 0 ? "ATENȚIE: Această categorie conține {$category['product_count']} produse!" : "Această acțiune nu poate fi anulată!"; ?>');">
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
                                        <p class="text-muted mt-3">Nu există categorii în baza de date.</p>
                                        <a href="<?php echo SITE_URL; ?>/admin/add_category.php" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-2"></i>Adaugă Prima Categorie
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="alert alert-info mt-4">
            <h5 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Informații Utile</h5>
            <ul class="mb-0">
                <li><strong>Ordine afișare:</strong> Numărul mic = poziție înaintea listei. Modifică în pagina de editare.</li>
                <li><strong>Slug:</strong> URL-friendly identifier generat automat din nume (ex: "broderii-florale" → /magazin?category=broderii-florale)</li>
                <li><strong>Ștergere:</strong> Nu poți șterge categorii care conțin produse. Mută sau șterge produsele mai întâi.</li>
                <li><strong>Status Inactiv:</strong> Categoriile inactive nu vor fi vizibile pe site, dar produsele rămân în baza de date.</li>
            </ul>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * Adăugare Produs Nou
 * Formular și procesare pentru adăugarea unui produs nou
 */

$pageTitle = "Adăugare Produs Nou";

require_once __DIR__ . '/../includes/header.php';

// Verificare acces admin
if (!isAdmin()) {
    setMessage("Nu ai acces la această pagină.", "danger");
    redirect('/');
}

$db = getDB();
$errors = [];
$success = false;

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare câmpuri
    $name = cleanInput($_POST['name']);
    $price = floatval($_POST['price']);
    $sale_price = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
    $description = cleanInput($_POST['description']);
    $category_ids = isset($_POST['category_ids']) ? $_POST['category_ids'] : [];
    $stock_status = $_POST['stock_status'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Validări
    if (empty($name)) {
        $errors[] = "Denumirea produsului este obligatorie.";
    }
    if ($price <= 0) {
        $errors[] = "Prețul trebuie să fie mai mare de 0.";
    }
    if ($sale_price && $sale_price >= $price) {
        $errors[] = "Prețul redus trebuie să fie mai mic decât prețul normal.";
    }
    if (empty($description)) {
        $errors[] = "Descrierea este obligatorie.";
    }
    if (empty($category_ids)) {
        $errors[] = "Selectează cel puțin o categorie.";
    }
    
    // Upload imagine principală
    $mainImage = '';
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['main_image'], 'products');
        if ($uploadResult['success']) {
            $mainImage = $uploadResult['filename'];
        } else {
            $errors[] = $uploadResult['error'];
        }
    }
    
    // Upload galerie imagini
    $galleryImages = [];
    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
        foreach ($_FILES['gallery_images']['name'] as $key => $name) {
            if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['gallery_images']['name'][$key],
                    'type' => $_FILES['gallery_images']['type'][$key],
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$key],
                    'error' => $_FILES['gallery_images']['error'][$key],
                    'size' => $_FILES['gallery_images']['size'][$key]
                ];
                
                $uploadResult = uploadImage($file, 'products/gallery');
                if ($uploadResult['success']) {
                    $galleryImages[] = $uploadResult['filename'];
                } else {
                    $errors[] = "Imagine galerie: " . $uploadResult['error'];
                }
            }
        }
    }
    
    $galleryJson = !empty($galleryImages) ? json_encode($galleryImages) : null;
    
    // Salvare în baza de date
    if (empty($errors)) {
        // Inserare produs (fără category_id - folosim tabelul product_categories)
        $stmt = $db->prepare("INSERT INTO products (name, description, price, sale_price, image, gallery, stock_status, is_active, is_featured, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $stmt->bind_param("ssddssiii", 
            $name, 
            $description, 
            $price, 
            $sale_price, 
            $mainImage, 
            $galleryJson, 
            $stock_status, 
            $is_active, 
            $is_featured
        );
        
        if ($stmt->execute()) {
            $product_id = $db->insert_id;
            
            // Atribuie categoriile la produs
            if (assignCategoriesToProduct($product_id, $category_ids)) {
                $success = true;
                setMessage("Produsul a fost adăugat cu succes!", "success");
                redirect('/admin/admin_products.php');
            } else {
                $errors[] = "Produsul a fost creat dar categoriile nu au putut fi atribuite.";
            }
        } else {
            $errors[] = "Eroare la salvarea în baza de date: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Obține categorii
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

/**
 * Funcție pentru upload imagine
 */
function uploadImage($file, $subfolder = '') {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Verificare tip
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Tipul fișierului nu este permis. Folosește: JPG, PNG, GIF, WEBP'];
    }
    
    // Verificare dimensiune
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Fișierul este prea mare. Maxim 5MB'];
    }
    
    // Generare nume unic
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('product_') . '_' . time() . '.' . $extension;
    
    // Creare director dacă nu există
    $uploadDir = UPLOAD_PATH;
    if (!empty($subfolder)) {
        $uploadDir .= $subfolder . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = $subfolder . '/' . $filename;
    }
    
    $targetPath = UPLOAD_PATH . $filename;
    
    // Upload fișier
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Eroare la încărcarea fișierului'];
    }
}
?>

<!-- Page Header -->
<section class="bg-dark text-white py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-plus-circle me-2"></i>Adăugare Produs Nou
                </h1>
                <p class="mb-0 text-white-50">Completează formularul pentru a adăuga un produs nou</p>
            </div>
            <a href="<?php echo SITE_URL; ?>/admin/admin_products.php" class="btn btn-outline-light">
                <i class="bi bi-arrow-left me-2"></i>Înapoi la Lista Produse
            </a>
        </div>
    </div>
</section>

<!-- Add Product Form -->
<section class="py-5">
    <div class="container">
        <!-- Erori -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Erori la validare:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <!-- Informații Principale -->
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informații Principale</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="name" class="form-label">Denumire Produs <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Descriere Completă <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="8" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <small class="text-muted">Descriere detaliată care va apărea pe pagina produsului</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Preț Normal (LEI) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           step="0.01" min="0" 
                                           value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>" 
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="sale_price" class="form-label">Preț Redus (LEI)</label>
                                    <input type="number" class="form-control" id="sale_price" name="sale_price" 
                                           step="0.01" min="0" 
                                           value="<?php echo isset($_POST['sale_price']) ? $_POST['sale_price'] : ''; ?>">
                                    <small class="text-muted">Lasă gol dacă produsul nu este la reducere</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Imagini -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-image me-2"></i>Imagini Produs</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="main_image" class="form-label">Imagine Principală <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="main_image" name="main_image" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                <small class="text-muted">Format: JPG, PNG, GIF, WEBP. Maxim 5MB</small>
                                <div id="main_image_preview" class="mt-3"></div>
                            </div>

                            <div class="mb-3">
                                <label for="gallery_images" class="form-label">Galerie Imagini</label>
                                <input type="file" class="form-control" id="gallery_images" name="gallery_images[]" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" multiple>
                                <small class="text-muted">Poți încărca mai multe imagini simultan (max 5MB fiecare)</small>
                                <div id="gallery_images_preview" class="mt-3 d-flex flex-wrap gap-2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Setări -->
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Setări Produs</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Categorii <span class="text-danger">*</span></label>
                                <small class="text-muted d-block mb-2">Selectează una sau mai multe categorii</small>
                                <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                    <?php foreach ($categories as $cat): ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="category_<?php echo $cat['id']; ?>" 
                                                   name="category_ids[]" 
                                                   value="<?php echo $cat['id']; ?>"
                                                   <?php echo (isset($_POST['category_ids']) && in_array($cat['id'], $_POST['category_ids'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="category_<?php echo $cat['id']; ?>">
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="stock_status" class="form-label">Status Stoc</label>
                                <select class="form-select" id="stock_status" name="stock_status">
                                    <option value="in_stock" <?php echo (isset($_POST['stock_status']) && $_POST['stock_status'] === 'in_stock') ? 'selected' : 'selected'; ?>>În Stoc</option>
                                    <option value="out_of_stock" <?php echo (isset($_POST['stock_status']) && $_POST['stock_status'] === 'out_of_stock') ? 'selected' : ''; ?>>Epuizat</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo (!isset($_POST['is_active']) || $_POST['is_active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Produs Activ
                                    </label>
                                </div>
                                <small class="text-muted">Produsul va fi vizibil pe site</small>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured"
                                           <?php echo (isset($_POST['is_featured']) && $_POST['is_featured']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_featured">
                                        Produs Recomandat
                                    </label>
                                </div>
                                <small class="text-muted">Produsul va apărea în secțiunea "Featured"</small>
                            </div>
                        </div>
                    </div>

                    <!-- Butoane Acțiuni -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="bi bi-check-circle me-2"></i>Adaugă Produs
                            </button>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_products.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-x-circle me-2"></i>Anulează
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- JavaScript pentru Preview Imagini -->
<script>
// Preview imagine principală
document.getElementById('main_image').addEventListener('change', function(e) {
    const preview = document.getElementById('main_image_preview');
    preview.innerHTML = '';
    
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width: 300px;">`;
        };
        reader.readAsDataURL(file);
    }
});

// Preview galerie imagini
document.getElementById('gallery_images').addEventListener('change', function(e) {
    const preview = document.getElementById('gallery_images_preview');
    preview.innerHTML = '';
    
    const files = e.target.files;
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">`;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

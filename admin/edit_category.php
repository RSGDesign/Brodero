<?php
/**
 * Editare Categorie Existentă
 * Formular și procesare pentru editarea unei categorii
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificare acces admin
if (!isAdmin()) {
    setMessage("Nu ai acces la această pagină.", "danger");
    redirect('/');
}

$db = getDB();
$errors = [];
$success = false;

// Verificare ID categorie
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMessage("ID categorie invalid.", "danger");
    redirect('/admin/admin_categories.php');
}

$categoryId = (int)$_GET['id'];

// Obține informații categorie
$stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setMessage("Categoria nu a fost găsită.", "danger");
    redirect('/admin/admin_categories.php');
}

$category = $result->fetch_assoc();
$stmt->close();

// Număr produse în categorie
$productCount = $db->query("SELECT COUNT(*) as count FROM products WHERE category_id = $categoryId")->fetch_assoc()['count'];

// Funcție pentru generare slug
function generateSlug($text) {
    $text = strtolower($text);
    $text = str_replace(['ă', 'â', 'î', 'ș', 'ț'], ['a', 'a', 'i', 's', 't'], $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare câmpuri
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $display_order = (int)$_POST['display_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Generare slug
    $slug = generateSlug($name);
    
    // Validări
    if (empty($name)) {
        $errors[] = "Numele categoriei este obligatoriu.";
    }
    
    if (empty($slug)) {
        $errors[] = "Nu s-a putut genera slug-ul din numele categoriei.";
    }
    
    // Verificare slug duplicat (exclude categoria curentă)
    $checkSlug = $db->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
    $checkSlug->bind_param("si", $slug, $categoryId);
    $checkSlug->execute();
    if ($checkSlug->get_result()->num_rows > 0) {
        $errors[] = "Există deja o categorie cu acest nume. Alege un nume diferit.";
    }
    $checkSlug->close();
    
    // Upload imagine nouă (dacă există)
    $image = $category['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Șterge imaginea veche
        if (!empty($category['image']) && file_exists(UPLOAD_PATH . $category['image'])) {
            unlink(UPLOAD_PATH . $category['image']);
        }
        
        $uploadResult = uploadCategoryImage($_FILES['image']);
        if ($uploadResult['success']) {
            $image = $uploadResult['filename'];
        } else {
            $errors[] = $uploadResult['error'];
        }
    }
    
    // Ștergere imagine (dacă este bifat)
    if (isset($_POST['delete_image']) && !empty($category['image'])) {
        if (file_exists(UPLOAD_PATH . $category['image'])) {
            unlink(UPLOAD_PATH . $category['image']);
        }
        $image = '';
    }
    
    // Actualizare în baza de date
    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, image = ?, display_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
        
        $stmt->bind_param("ssssiii", $name, $slug, $description, $image, $display_order, $is_active, $categoryId);
        
        if ($stmt->execute()) {
            $success = true;
            setMessage("Categoria a fost actualizată cu succes!", "success");
            redirect('/admin/admin_categories.php');
        } else {
            $errors[] = "Eroare la actualizarea în baza de date: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Re-obține datele categoriei după update
    if ($success) {
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $category = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

// Include header DUPĂ procesarea POST
$pageTitle = "Editare Categorie";
require_once __DIR__ . '/../includes/header.php';

/**
 * Funcție pentru upload imagine categorie
 */
function uploadCategoryImage($file) {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    // Verificare tip
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Tipul fișierului nu este permis. Folosește: JPG, PNG, GIF, WEBP'];
    }
    
    // Verificare dimensiune
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Fișierul este prea mare. Maxim 2MB'];
    }
    
    // Generare nume unic
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'categories/category_' . uniqid() . '_' . time() . '.' . $extension;
    
    // Creare director dacă nu există
    $uploadDir = UPLOAD_PATH . 'categories/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
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
                    <i class="bi bi-pencil-square me-2"></i>Editare Categorie #<?php echo $category['id']; ?>
                </h1>
                <p class="mb-0 text-white-50"><?php echo htmlspecialchars($category['name']); ?></p>
            </div>
            <div>
                <a href="<?php echo SITE_URL; ?>/pages/magazin.php?category=<?php echo $category['id']; ?>" 
                   class="btn btn-outline-light me-2" target="_blank">
                    <i class="bi bi-eye me-2"></i>Previzualizare
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/admin_categories.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-2"></i>Înapoi
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Edit Category Form -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Statistici categorie -->
                <div class="alert alert-info mb-4">
                    <h5 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Informații Categorie</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Produse în categorie:</strong> <?php echo $productCount; ?></p>
                            <p class="mb-1"><strong>Slug:</strong> <code><?php echo htmlspecialchars($category['slug']); ?></code></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Creat:</strong> <?php echo date('d.m.Y H:i', strtotime($category['created_at'])); ?></p>
                            <?php if ($category['updated_at']): ?>
                                <p class="mb-1"><strong>Actualizat:</strong> <?php echo date('d.m.Y H:i', strtotime($category['updated_at'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

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
                    <!-- Informații Principale -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informații Categorie</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nume Categorie <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($category['name']); ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Descriere</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="4"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                <small class="text-muted">Descrierea va apărea pe pagina categoriei (opțional)</small>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Imagine Categorie</label>
                                
                                <?php if (!empty($category['image'])): ?>
                                    <div class="mb-3">
                                        <p class="text-muted mb-2">Imagine actuală:</p>
                                        <img src="<?php echo SITE_URL . '/uploads/' . $category['image']; ?>" 
                                             alt=""
                                             title=""
                                             class="img-thumbnail"
                                             style="max-width: 300px;">
                                        
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="delete_image" name="delete_image">
                                            <label class="form-check-label text-danger" for="delete_image">
                                                Șterge imaginea actuală
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <input type="file" class="form-control" id="image" name="image" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                <small class="text-muted">Format: JPG, PNG, GIF, WEBP. Maxim 2MB. Lasă gol pentru a păstra imaginea actuală.</small>
                                <div id="image_preview" class="mt-3"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Setări -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Setări Categorie</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="display_order" class="form-label">Ordine Afișare</label>
                                <input type="number" class="form-control" id="display_order" name="display_order" 
                                       value="<?php echo $category['display_order']; ?>" 
                                       min="0">
                                <small class="text-muted">Numărul mic = poziție înaintea listei (0 = primul)</small>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo $category['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Categorie Activă
                                    </label>
                                </div>
                                <small class="text-muted">Categoria va fi vizibilă pe site</small>
                            </div>
                        </div>
                    </div>

                    <!-- Butoane Acțiuni -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-2">
                                <i class="bi bi-check-circle me-2"></i>Salvează Modificările
                            </button>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_categories.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-x-circle me-2"></i>Anulează
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- JavaScript pentru Preview Imagine -->
<script>
// Preview imagine
document.getElementById('image').addEventListener('change', function(e) {
    const preview = document.getElementById('image_preview');
    preview.innerHTML = '';
    
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <p class="text-muted mb-2">Previzualizare imagine nouă:</p>
                <img src="${e.target.result}" class="img-thumbnail" style="max-width: 300px;">
            `;
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

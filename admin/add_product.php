<?php
/**
 * Adăugare Produs Nou
 * Formular și procesare pentru adăugarea unui produs nou
 */

// Include config și database PRIMUL (pentru redirect fără erori)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificare acces admin ÎNAINTE de orice output
if (!isAdmin()) {
    setMessage("Nu ai acces la această pagină.", "danger");
    redirect('/');
}

$db = getDB();
$errors = [];
$success = false;

/**
 * Funcție pentru generare slug URL-friendly din text
 */
function generateSlug($text) {
    // Convertește la lowercase
    $text = strtolower($text);
    // Înlocuiește caractere speciale românești
    $text = str_replace(['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'], ['a', 'a', 'i', 's', 't', 'a', 'a', 'i', 's', 't'], $text);
    // Înlocuiește orice caracter non-alfanumeric cu dash
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    // Elimină dash-uri de la început și final
    $text = trim($text, '-');
    return $text;
}

/**
 * Funcție pentru generare slug unic în baza de date
 */
function generateUniqueSlug($db, $text, $table = 'products', $exclude_id = null) {
    $slug = generateSlug($text);
    $originalSlug = $slug;
    $counter = 1;
    
    // Verifică dacă slug-ul există deja
    while (true) {
        if ($exclude_id) {
            $stmt = $db->prepare("SELECT id FROM {$table} WHERE slug = ? AND id != ?");
            $stmt->bind_param("si", $slug, $exclude_id);
        } else {
            $stmt = $db->prepare("SELECT id FROM {$table} WHERE slug = ?");
            $stmt->bind_param("s", $slug);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            break; // Slug-ul este unic
        }
        
        $stmt->close();
        // Slug-ul există, adaugă counter
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

/**
 * Funcții pentru gestionarea fișierelor descărcabile
 */
function sanitizeFilename($name) {
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
    return trim($name, '_');
}

function allowedFileExtension($ext) {
    $allowed = ['zip','rar','7z','pdf','png','jpg','jpeg','gif','svg','txt','doc','docx','xls','xlsx','ppt','pptx','mp3','wav','mp4','avi','mkv'];
    return in_array(strtolower($ext), $allowed, true);
}

function ensureProductDownloadFolder($productId) {
    $base = __DIR__ . '/../uploads/downloads/' . intval($productId);
    if (!is_dir($base)) {
        mkdir($base, 0775, true);
    }
    return $base;
}

// Procesare formular ÎNAINTE de header.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CHECK: Verify POST data exists (upload size limits can empty $_POST)
    if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $maxUpload = ini_get('upload_max_filesize');
        $maxPost = ini_get('post_max_size');
        $errors[] = "Fișierele încărcate depășesc limita serverului (upload_max_filesize: $maxUpload, post_max_size: $maxPost). Încercați cu fișiere mai mici.";
    } elseif (empty($_POST['name']) && empty($_POST['price'])) {
        $errors[] = "Datele formularului sunt incomplete. Verificați toate câmpurile obligatorii.";
    } else {
        // PROTECTION: Prevent double submission with session token
        if (!isset($_POST['form_token']) || !isset($_SESSION['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
            // Token invalid, poate fi double submit
            if (isset($_SESSION['last_product_added']) && (time() - $_SESSION['last_product_added']) < 3) {
                // Submitted în ultimele 3 secunde - ignore duplicate
                setMessage("Produsul a fost deja adăugat.", "info");
                redirect('/admin/admin_products.php');
            }
        }
        
        // Validare câmpuri cu verificare isset
        $name = isset($_POST['name']) ? cleanInput($_POST['name']) : '';
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $sale_price = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
        $description = isset($_POST['description']) ? cleanInput($_POST['description']) : '';
        $category_ids = isset($_POST['category_ids']) ? $_POST['category_ids'] : [];
        $stock_status = isset($_POST['stock_status']) ? $_POST['stock_status'] : 'in_stock';
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
            // CRITICAL FIX: Use $galleryFileName instead of $name (which is product name!)
            foreach ($_FILES['gallery_images']['name'] as $key => $galleryFileName) {
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
            // Generare slug unic din numele produsului
            $slug = generateUniqueSlug($db, $name, 'products');
            
            // Inserare produs (cu slug unic)
            $stmt = $db->prepare("INSERT INTO products (name, slug, description, price, sale_price, image, gallery, stock_status, is_active, is_featured, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            // Tipuri: s=string, d=double, i=integer
            // name(s), slug(s), description(s), price(d), sale_price(d), image(s), gallery(s), stock_status(s), is_active(i), is_featured(i)
            $stmt->bind_param("sssddsssii", 
                $name,
                $slug,
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
                
                // DEBUG: Log product creation
                error_log("PRODUCT CREATED: ID=$product_id, Name=$name, Time=" . date('Y-m-d H:i:s'));
                $_SESSION['last_product_added'] = time();
                
                // Atribuie categoriile la produs
                if (assignCategoriesToProduct($product_id, $category_ids)) {
                    $success = true;
                    
                    // PROCESARE FIȘIERE DESCĂRCABILE
                    $fileErrors = [];
                    $fileSuccessCount = 0;
                    
                    if (isset($_FILES['downloadable_files']) && !empty($_FILES['downloadable_files']['name'][0])) {
                        $totalFiles = count($_FILES['downloadable_files']['name']);
                        
                        for ($i = 0; $i < $totalFiles; $i++) {
                            // Verifică dacă fișierul a fost încărcat corect
                            if ($_FILES['downloadable_files']['error'][$i] !== UPLOAD_ERR_OK) {
                                if ($_FILES['downloadable_files']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                                    $fileErrors[] = "Fișier {$_FILES['downloadable_files']['name'][$i]}: Eroare la încărcare.";
                                }
                                continue;
                            }
                            
                            $fileName = $_FILES['downloadable_files']['name'][$i];
                            $fileTmpName = $_FILES['downloadable_files']['tmp_name'][$i];
                            $fileSize = $_FILES['downloadable_files']['size'][$i];
                            
                            // Validare dimensiune (max 200MB)
                            if ($fileSize <= 0) {
                                $fileErrors[] = "Fișier {$fileName}: Fișier gol.";
                                continue;
                            }
                            if ($fileSize > 200 * 1024 * 1024) {
                                $fileErrors[] = "Fișier {$fileName}: Prea mare (max 200MB).";
                                continue;
                            }
                            
                            // Validare extensie
                            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                            if (!allowedFileExtension($fileExt)) {
                                $fileErrors[] = "Fișier {$fileName}: Extensie nepermisă.";
                                continue;
                            }
                            
                            // Sanitizare nume fișier
                            $safeFileName = sanitizeFilename(pathinfo($fileName, PATHINFO_FILENAME)) . '.' . strtolower($fileExt);
                            
                            // Creare director pentru produs
                            $downloadFolder = ensureProductDownloadFolder($product_id);
                            $destinationPath = $downloadFolder . '/' . $safeFileName;
                            
                            // Mutare fișier încărcat
                            if (move_uploaded_file($fileTmpName, $destinationPath)) {
                                // Salvare în baza de date
                                $relativePath = 'uploads/downloads/' . $product_id . '/' . $safeFileName;
                                $actualFileSize = filesize($destinationPath);
                                
                                // Obține limitele și status pentru fiecare fișier
                                $downloadLimit = isset($_POST['download_limits'][$i]) ? intval($_POST['download_limits'][$i]) : 0;
                                $fileStatus = isset($_POST['file_statuses'][$i]) && $_POST['file_statuses'][$i] === 'active' ? 'active' : 'inactive';
                                
                                $fileStmt = $db->prepare("INSERT INTO product_files (product_id, file_name, file_path, file_size, status, download_limit, download_count, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
                                $fileStmt->bind_param('issisi', $product_id, $safeFileName, $relativePath, $actualFileSize, $fileStatus, $downloadLimit);
                                
                                if ($fileStmt->execute()) {
                                    $fileSuccessCount++;
                                } else {
                                    $fileErrors[] = "Fișier {$fileName}: Eroare la salvare în DB.";
                                    // Șterge fișierul fizic dacă nu s-a salvat în DB
                                    @unlink($destinationPath);
                                }
                                $fileStmt->close();
                            } else {
                                $fileErrors[] = "Fișier {$fileName}: Nu s-a putut salva pe server.";
                            }
                        }
                    }
                    
                    // Mesaj final de succes
                    $successMessage = "Produsul a fost adăugat cu succes!";
                    if ($fileSuccessCount > 0) {
                        $successMessage .= " Au fost încărcate {$fileSuccessCount} fișier(e) descărcabil(e).";
                    }
                    if (!empty($fileErrors)) {
                        $successMessage .= " Erori fișiere: " . implode("; ", $fileErrors);
                    }
                    
                    setMessage($successMessage, "success");
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

// ACUM include header.php (DUPĂ procesarea POST)
$pageTitle = "Adăugare Produs Nou";
require_once __DIR__ . '/../includes/header.php';
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
            <?php
            // Generate and store form token to prevent double submission
            $_SESSION['form_token'] = bin2hex(random_bytes(32));
            ?>
            <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
            
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

                    <!-- Fișiere Descărcabile -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="bi bi-download me-2"></i>Fișiere Descărcabile</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Informații:</strong> Poți încărca multiple fișiere simultan (PDF, ZIP, etc.). 
                                Maximum 200MB per fișier. Extensii permise: zip, rar, 7z, pdf, doc, docx, xls, xlsx, ppt, pptx, imagini, video, audio.
                            </div>

                            <div class="mb-3">
                                <label for="downloadable_files" class="form-label">Selectează Fișiere</label>
                                <input type="file" class="form-control" id="downloadable_files" 
                                       name="downloadable_files[]" multiple>
                                <small class="text-muted">
                                    <i class="bi bi-check-circle text-success"></i> 
                                    Poți selecta mai multe fișiere simultan (Ctrl+Click sau Shift+Click)
                                </small>
                            </div>

                            <div id="downloadable_files_preview" class="mt-3">
                                <!-- Preview-ul fișierelor va apărea aici -->
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
                            <button type="submit" class="btn btn-primary w-100 mb-2" id="submitBtn">
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

<!-- JavaScript pentru Preview Imagini și Fișiere -->
<script>
// PREVENT DOUBLE SUBMISSION
let formSubmitted = false;
const form = document.querySelector('form');
const submitBtn = document.getElementById('submitBtn');

form.addEventListener('submit', function(e) {
    if (formSubmitted) {
        e.preventDefault();
        console.log('Form already submitted, preventing duplicate submission');
        return false;
    }
    
    formSubmitted = true;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Se procesează...';
    
    // Re-enable after 5 seconds as a safety measure
    setTimeout(function() {
        formSubmitted = false;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Adaugă Produs';
    }, 5000);
});

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

// Preview și configurare fișiere descărcabile
document.getElementById('downloadable_files').addEventListener('change', function(e) {
    const preview = document.getElementById('downloadable_files_preview');
    preview.innerHTML = '';
    
    const files = e.target.files;
    
    if (files.length === 0) {
        return;
    }
    
    // Header pentru lista de fișiere
    preview.innerHTML = `
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-2"></i>
            <strong>${files.length} fișier(e) selectat(e)</strong>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40%;">Nume Fișier</th>
                        <th style="width: 15%;">Dimensiune</th>
                        <th style="width: 15%;">Tip</th>
                        <th style="width: 15%;">Limită Descărcări</th>
                        <th style="width: 15%;">Status</th>
                    </tr>
                </thead>
                <tbody id="files_table_body">
                </tbody>
            </table>
        </div>
    `;
    
    const tbody = document.getElementById('files_table_body');
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileName = file.name;
        const fileSize = (file.size / (1024 * 1024)).toFixed(2); // MB
        const fileExt = fileName.split('.').pop().toLowerCase();
        
        // Verificare extensie
        const allowedExt = ['zip','rar','7z','pdf','png','jpg','jpeg','gif','svg','txt','doc','docx','xls','xlsx','ppt','pptx','mp3','wav','mp4','avi','mkv'];
        const isValidExt = allowedExt.includes(fileExt);
        
        // Verificare dimensiune (max 200MB)
        const isValidSize = file.size <= (200 * 1024 * 1024);
        
        let statusBadge = '';
        if (!isValidExt) {
            statusBadge = '<span class="badge bg-danger">Extensie nepermisă</span>';
        } else if (!isValidSize) {
            statusBadge = '<span class="badge bg-danger">Prea mare (max 200MB)</span>';
        } else {
            statusBadge = '<span class="badge bg-success">Valid</span>';
        }
        
        const row = document.createElement('tr');
        row.className = (!isValidExt || !isValidSize) ? 'table-danger' : '';
        row.innerHTML = `
            <td>
                <i class="bi bi-file-earmark-${getFileIcon(fileExt)} me-2"></i>
                <strong>${escapeHtml(fileName)}</strong>
            </td>
            <td>${fileSize} MB</td>
            <td><span class="badge bg-secondary">${fileExt.toUpperCase()}</span></td>
            <td>
                <input type="number" name="download_limits[]" class="form-control form-control-sm" 
                       value="0" min="0" placeholder="0 = nelimitat" style="width: 100px;"
                       ${(!isValidExt || !isValidSize) ? 'disabled' : ''}>
            </td>
            <td>
                <select name="file_statuses[]" class="form-select form-select-sm"
                        ${(!isValidExt || !isValidSize) ? 'disabled' : ''}>
                    <option value="active">Activ</option>
                    <option value="inactive">Inactiv</option>
                </select>
            </td>
        `;
        tbody.appendChild(row);
    }
});

// Helper pentru icoane fișiere
function getFileIcon(ext) {
    const icons = {
        'pdf': 'pdf',
        'zip': 'zip', 'rar': 'zip', '7z': 'zip',
        'doc': 'word', 'docx': 'word',
        'xls': 'excel', 'xlsx': 'excel',
        'ppt': 'ppt', 'pptx': 'ppt',
        'jpg': 'image', 'jpeg': 'image', 'png': 'image', 'gif': 'image', 'svg': 'image',
        'mp3': 'music', 'wav': 'music',
        'mp4': 'play', 'avi': 'play', 'mkv': 'play',
        'txt': 'text'
    };
    return icons[ext] || 'file';
}

// Helper pentru escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_downloads.php';

// Simple admin guard
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
    }
}
if (!isAdmin()) {
    // CRITICAL: Save session before redirect
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$db = getDB();

// Helpers
function sanitizeFilename($name) {
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
    return trim($name, '_');
}

function allowedExtension($ext) {
    $allowed = ['zip','rar','7z','pdf','png','jpg','jpeg','gif','svg','txt','doc','docx','xls','xlsx','ppt','pptx','mp3','wav','mp4','avi','mkv'];
    return in_array(strtolower($ext), $allowed, true);
}

function ensureProductFolder($productId) {
    $base = __DIR__ . '/../uploads/downloads/' . intval($productId);
    if (!is_dir($base)) {
        mkdir($base, 0775, true);
    }
    return $base;
}

// Actions: upload, delete, toggle, rename
$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if ($action === 'upload') {
        if ($productId <= 0) {
            $errors[] = 'Produs invalid.';
        } else if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Fișierul nu a fost încărcat corect.';
        } else {
            $file = $_FILES['file'];
            $size = $file['size'];
            if ($size <= 0) {
                $errors[] = 'Fișier gol.';
            } else if ($size > 200 * 1024 * 1024) {
                $errors[] = 'Fișier prea mare (max 200MB).';
            } else {
                $orig = $file['name'];
                $ext = pathinfo($orig, PATHINFO_EXTENSION);
                if (!allowedExtension($ext)) {
                    $errors[] = 'Extensie nepermisă.';
                } else {
                    $safeName = sanitizeFilename(pathinfo($orig, PATHINFO_FILENAME)) . '.' . strtolower($ext);
                    $folder = ensureProductFolder($productId);
                    $dest = $folder . '/' . $safeName;
                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $errors[] = 'Nu s-a putut salva fișierul.';
                    } else {
                        $relPath = 'uploads/downloads/' . $productId . '/' . $safeName;
                        $sizeBytes = filesize($dest);
                        $limit = isset($_POST['download_limit']) ? intval($_POST['download_limit']) : 0;
                        $status = isset($_POST['status']) && $_POST['status'] === 'active' ? 'active' : 'inactive';
                        $stmt = $db->prepare("INSERT INTO product_files (product_id, file_name, file_path, file_size, status, download_limit, download_count, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
                        $stmt->bind_param('issisi', $productId, $safeName, $relPath, $sizeBytes, $status, $limit);
                        if ($stmt->execute()) {
                            $messages[] = 'Fișier încărcat cu succes.';
                        } else {
                            $errors[] = 'Eroare DB la inserare.';
                        }
                    }
                }
            }
        }
    } else if ($action === 'delete') {
        $fileId = intval($_POST['file_id'] ?? 0);
        if ($fileId <= 0) {
            $errors[] = 'Fișier invalid.';
        } else {
            $stmt = $db->prepare('SELECT file_path FROM product_files WHERE id = ?');
            $stmt->bind_param('i', $fileId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                $full = __DIR__ . '/../' . $row['file_path'];
                if (file_exists($full)) {
                    @unlink($full);
                }
                $del = $db->prepare('DELETE FROM product_files WHERE id = ?');
                $del->bind_param('i', $fileId);
                if ($del->execute()) {
                    $messages[] = 'Fișier șters.';
                } else {
                    $errors[] = 'Nu s-a putut șterge din DB.';
                }
            } else {
                $errors[] = 'Fișier inexistent.';
            }
        }
    } else if ($action === 'toggle') {
        $fileId = intval($_POST['file_id'] ?? 0);
        $newStatus = ($_POST['new_status'] ?? 'inactive') === 'active' ? 'active' : 'inactive';
        $stmt = $db->prepare('UPDATE product_files SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $newStatus, $fileId);
        if ($stmt->execute()) {
            $messages[] = 'Stare actualizată.';
        } else {
            $errors[] = 'Eroare la actualizarea stării.';
        }
    } else if ($action === 'rename') {
        $fileId = intval($_POST['file_id'] ?? 0);
        $newName = sanitizeFilename($_POST['new_name'] ?? '');
        if ($fileId <= 0 || $newName === '') {
            $errors[] = 'Date invalide pentru redenumire.';
        } else {
            $stmt = $db->prepare('SELECT product_id, file_name, file_path FROM product_files WHERE id = ?');
            $stmt->bind_param('i', $fileId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) {
                $errors[] = 'Fișier inexistent.';
            } else {
                $productId = intval($row['product_id']);
                $oldName = $row['file_name'];
                $oldPath = $row['file_path'];
                $ext = pathinfo($oldName, PATHINFO_EXTENSION);
                $newSafe = sanitizeFilename($newName) . ($ext ? '.' . strtolower($ext) : '');
                $folder = ensureProductFolder($productId);
                $oldFull = __DIR__ . '/../' . $oldPath;
                $newFull = $folder . '/' . $newSafe;
                if (!@rename($oldFull, $newFull)) {
                    $errors[] = 'Nu s-a putut redenumi fișierul fizic.';
                } else {
                    $newRel = 'uploads/downloads/' . $productId . '/' . $newSafe;
                    $upd = $db->prepare('UPDATE product_files SET file_name = ?, file_path = ? WHERE id = ?');
                    $upd->bind_param('ssi', $newSafe, $newRel, $fileId);
                    if ($upd->execute()) {
                        $messages[] = 'Fișier redenumit.';
                    } else {
                        $errors[] = 'Nu s-a putut actualiza DB.';
                    }
                }
            }
        }
    }
}

// Fetch products for selector
$products = [];
$pq = $db->query('SELECT id, name FROM products ORDER BY name');
while ($r = $pq->fetch_assoc()) { $products[] = $r; }

// If a product is selected, list its files
$currentProductId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$currentFiles = [];
if ($currentProductId > 0) {
    $stmt = $db->prepare('SELECT * FROM product_files WHERE product_id = ? ORDER BY uploaded_at DESC');
    $stmt->bind_param('i', $currentProductId);
    $stmt->execute();
    $currentFiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>
<div class="container py-4">
    <h1 class="h3 mb-3">Fișiere Descărcabile</h1>
    <?php if (!empty($errors)) : ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($messages)) : ?>
        <div class="alert alert-success">
            <?php foreach ($messages as $m) echo '<div>' . htmlspecialchars($m) . '</div>'; ?>
        </div>
    <?php endif; ?>

    <form class="row g-2 mb-4" method="get" action="">
        <div class="col-auto">
            <label class="form-label">Produs</label>
            <select name="product_id" class="form-select" onchange="this.form.submit()">
                <option value="0">-- selectează --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= $currentProductId===(int)$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if ($currentProductId > 0): ?>
    <div class="card mb-4">
        <div class="card-header">Încarcă fișier</div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <input type="hidden" name="product_id" value="<?= (int)$currentProductId ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Fișier</label>
                        <input type="file" name="file" class="form-control" required>
                        <div class="form-text">Max 200MB. Extensii permise: zip, pdf, imagini, doc, etc.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Limită descărcări</label>
                        <input type="number" name="download_limit" class="form-control" min="0" value="0">
                        <div class="form-text">0 = nelimitat</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Stare</label>
                        <select name="status" class="form-select">
                            <option value="active">Activ</option>
                            <option value="inactive">Inactiv</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Încarcă</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Fișiere pentru produs</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Nume</th>
                            <th>Mărime</th>
                            <th>Stare</th>
                            <th>Limită</th>
                            <th>Descărcări</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($currentFiles)): ?>
                        <tr><td colspan="6" class="text-center">Nu există fișiere.</td></tr>
                        <?php else: foreach ($currentFiles as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['file_name']) ?></td>
                            <td><?= number_format(((int)$f['file_size'])/1024/1024, 2) ?> MB</td>
                            <td><span class="badge bg-<?= $f['status']==='active'?'success':'secondary' ?>"><?= htmlspecialchars($f['status']) ?></span></td>
                            <td><?= (int)$f['download_limit'] ?></td>
                            <td><?= (int)$f['download_count'] ?></td>
                            <td class="d-flex gap-2">
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="file_id" value="<?= (int)$f['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $f['status']==='active'?'inactive':'active' ?>">
                                    <button class="btn btn-sm btn-outline-<?= $f['status']==='active'?'secondary':'success' ?>" type="submit">
                                        <?= $f['status']==='active'?'Dezactivează':'Activează' ?>
                                    </button>
                                </form>
                                <form method="post" class="d-inline" onsubmit="return confirm('Ștergi fișierul?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="file_id" value="<?= (int)$f['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Șterge</button>
                                </form>
                                <form method="post" class="d-flex gap-2 d-inline">
                                    <input type="hidden" name="action" value="rename">
                                    <input type="hidden" name="file_id" value="<?= (int)$f['id'] ?>">
                                    <input type="text" name="new_name" class="form-control form-control-sm" placeholder="Nume nou (fără extensie)">
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Redenumește</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions_downloads.php';

if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$userId = $_SESSION['user_id'];
$files = getUserDownloadableFiles($userId);

$pageTitle = 'Fișiere Descărcabile';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="container my-5">
    <h1 class="h3 mb-4"><i class="bi bi-download me-2"></i>Fișiere Descărcabile</h1>
    <?php if (empty($files)): ?>
        <div class="alert alert-info">Nu ai fișiere disponibile momentan.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($files as $f): ?>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($f['product_name']); ?></h6>
                        <small class="text-muted d-block mb-2"><?php echo htmlspecialchars($f['file_name']); ?></small>
                        <small class="d-block">Mărime: <?php echo number_format($f['file_size']/1024/1024, 2); ?> MB</small>
                        <?php if ((int)$f['download_limit'] > 0): ?>
                            <small class="d-block">Descărcări rămase: <?php echo max(0, (int)$f['download_limit'] - (int)$f['download_count']); ?></small>
                        <?php else: ?>
                            <small class="d-block">Descărcări nelimitate</small>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white border-0 d-flex justify-content-between">
                        <?php $token = generateDownloadToken($f['file_id'], $f['order_id'], $userId); ?>
                        <a class="btn btn-primary btn-sm" href="<?php echo SITE_URL; ?>/pages/download.php?file=<?php echo (int)$f['file_id']; ?>&order=<?php echo (int)$f['order_id']; ?>&token=<?php echo urlencode($token); ?>">
                            <i class="bi bi-arrow-down-circle me-1"></i>Descarcă
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

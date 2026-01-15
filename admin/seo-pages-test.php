<?php
/**
 * SEO Pages Management - VERSIUNE SIMPLĂ TEST
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/seo.php';

// Verificare auth
if (!isLoggedIn() || !isAdmin()) {
    die("Acces interzis - autentificare necesară");
}

$db = getPDO();
$seoPages = getAllSeoPages($db);

// Include header DUPĂ procesare
$pageTitle = "SEO Pages - Admin";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <h1>SEO Pages Management - TEST</h1>
    <p>Total pagini: <?php echo count($seoPages); ?></p>
    
    <?php if (hasMessage()): ?>
        <div class="alert alert-<?php echo getMessageType(); ?>">
            <?php echo getMessage(); ?>
        </div>
    <?php endif; ?>
    
    <table class="table">
        <thead>
            <tr>
                <th>Slug</th>
                <th>Title</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($seoPages as $page): ?>
                <tr>
                    <td><?php echo htmlspecialchars($page['page_slug']); ?></td>
                    <td><?php echo htmlspecialchars($page['title']); ?></td>
                    <td>
                        <?php if ($page['is_active']): ?>
                            <span class="badge bg-success">Activ</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactiv</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

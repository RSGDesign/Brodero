<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "START<br>";

require_once __DIR__ . '/../config/config.php';
echo "Config OK<br>";

require_once __DIR__ . '/../config/database.php';
echo "Database OK<br>";

require_once __DIR__ . '/../includes/seo.php';
echo "SEO OK<br>";

if (!isLoggedIn() || !isAdmin()) {
    die("Not admin");
}
echo "Admin check OK<br>";

$db = getPDO();
echo "PDO OK<br>";

$seoPages = getAllSeoPages($db);
echo "getAllSeoPages OK - Count: " . count($seoPages) . "<br>";

$pageTitle = "SEO Pages - Admin";
echo "PageTitle set<br>";

echo "About to include header...<br>";
require_once __DIR__ . '/../includes/header.php';
echo "Header included<br>";
?>

<div class="container my-5">
    <h1>CONTENT IS HERE</h1>
    <p>If you see this, the page works!</p>
    <p>SEO Pages: <?php echo count($seoPages); ?></p>
</div>

<?php 
echo "About to include footer...<br>";
require_once __DIR__ . '/../includes/footer.php'; 
?>

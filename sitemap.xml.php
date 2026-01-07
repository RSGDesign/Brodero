<?php
/**
 * Sitemap XML Generator - Brodero
 * Generează automat sitemap-ul pentru motoarele de căutare
 */

header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();
$baseUrl = SITE_URL;

// Începe XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// ============================================================================
// 1. HOMEPAGE
// ============================================================================
echo '  <url>' . "\n";
echo '    <loc>' . htmlspecialchars($baseUrl . '/') . '</loc>' . "\n";
echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
echo '    <changefreq>daily</changefreq>' . "\n";
echo '    <priority>1.0</priority>' . "\n";
echo '  </url>' . "\n";

// ============================================================================
// 2. PAGINI STATICE PRINCIPALE
// ============================================================================
$staticPages = [
    '/pages/magazin.php' => ['changefreq' => 'daily', 'priority' => '0.9'],
    '/pages/program-referral.php' => ['changefreq' => 'weekly', 'priority' => '0.8'],
    '/pages/cart.php' => ['changefreq' => 'weekly', 'priority' => '0.5'],
    '/pages/contact.php' => ['changefreq' => 'monthly', 'priority' => '0.6'],
];

foreach ($staticPages as $page => $meta) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($baseUrl . $page) . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    echo '    <changefreq>' . $meta['changefreq'] . '</changefreq>' . "\n";
    echo '    <priority>' . $meta['priority'] . '</priority>' . "\n";
    echo '  </url>' . "\n";
}

// ============================================================================
// 3. CATEGORII
// ============================================================================
$stmt = $db->prepare("
    SELECT id, name, slug 
    FROM categories 
    WHERE is_active = 1 
    ORDER BY name ASC
");
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($categories as $category) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($baseUrl . '/pages/magazin.php?category=' . $category['id']) . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    echo '    <changefreq>weekly</changefreq>' . "\n";
    echo '    <priority>0.8</priority>' . "\n";
    echo '  </url>' . "\n";
}

// ============================================================================
// 4. PRODUSE
// ============================================================================
$stmt = $db->prepare("
    SELECT id, name, slug 
    FROM products 
    WHERE is_active = 1 
    ORDER BY name ASC
");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($products as $product) {
    $productUrl = $baseUrl . '/pages/produs.php?slug=' . urlencode($product['slug']);
    
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($productUrl) . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    echo '    <changefreq>weekly</changefreq>' . "\n";
    echo '    <priority>0.7</priority>' . "\n";
    echo '  </url>' . "\n";
}

// ============================================================================
// 5. BLOG (dacă există)
// ============================================================================
// Adaugă aici când implementezi blog-ul
// $stmt = $db->prepare("SELECT slug, updated_at FROM blog_posts WHERE published = 1");
// ...

// Închide XML
echo '</urlset>';
?>

<?php
/**
 * Sitemap XML Generator - Brodero
 * Generează automat sitemap-ul pentru motoarele de căutare
 * Include: pagini SEO, produse, categorii
 */

// Dezactivează afișarea erorilor (nu trebuie să apară în XML)
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/seo.php';

try {
    $db = getPDO();
    $baseUrl = SITE_URL;

// Începe XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// ============================================================================
// 1. PAGINI SEO ACTIVE (din baza de date)
// ============================================================================
// Obține toate paginile SEO active, excluzând template-urile pentru produse
$stmt = $db->prepare("
    SELECT page_slug, updated_at 
    FROM seo_pages 
    WHERE is_active = 1 
    AND page_slug NOT LIKE 'product:%'
    ORDER BY page_slug ASC
");
$stmt->execute();
$seoPages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mapare sluguri către URL-uri reale
$slugToUrl = [
    'home' => '/',
    'magazin' => '/pages/magazin.php',
    'contact' => '/pages/contact.php',
    'program-referral' => '/pages/program-referral.php',
    'cart' => '/pages/cart.php',
    'modele-la-comanda' => '/pages/modele-la-comanda.php',
];

foreach ($seoPages as $seoPage) {
    $slug = $seoPage['page_slug'];
    $url = $slugToUrl[$slug] ?? '/pages/' . $slug . '.php';
    
    // Determină prioritatea bazată pe slug
    $priority = '0.5';
    $changefreq = 'monthly';
    
    if ($slug === 'home') {
        $priority = '1.0';
        $changefreq = 'daily';
    } elseif ($slug === 'magazin') {
        $priority = '0.9';
        $changefreq = 'daily';
    } elseif (in_array($slug, ['contact', 'program-referral', 'modele-la-comanda'])) {
        $priority = '0.7';
        $changefreq = 'weekly';
    }
    
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($baseUrl . $url) . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d', strtotime($seoPage['updated_at'])) . '</lastmod>' . "\n";
    echo '    <changefreq>' . $changefreq . '</changefreq>' . "\n";
    echo '    <priority>' . $priority . '</priority>' . "\n";
    echo '  </url>' . "\n";
}

// ============================================================================
// 2. PAGINI STATICE SUPLIMENTARE (fără SEO în DB)
// ============================================================================
// Adaugă pagini care nu sunt în seo_pages dar trebuie în sitemap
$additionalPages = [
    '/pages/termeni-conditii.php' => ['changefreq' => 'monthly', 'priority' => '0.3'],
    '/pages/politica-confidentialitate.php' => ['changefreq' => 'monthly', 'priority' => '0.3'],
];

foreach ($additionalPages as $page => $meta) {
    // Verifică dacă nu e deja adăugată din SEO
    $alreadyAdded = false;
    foreach ($seoPages as $seoPage) {
        $slug = $seoPage['page_slug'];
        if (isset($slugToUrl[$slug]) && $slugToUrl[$slug] === $page) {
            $alreadyAdded = true;
            break;
        }
    }
    
    if (!$alreadyAdded) {
        echo '  <url>' . "\n";
        echo '    <loc>' . htmlspecialchars($baseUrl . $page) . '</loc>' . "\n";
        echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        echo '    <changefreq>' . $meta['changefreq'] . '</changefreq>' . "\n";
        echo '    <priority>' . $meta['priority'] . '</priority>' . "\n";
        echo '  </url>' . "\n";
    }
}

// ============================================================================
// 3. PRODUSE ACTIVE
// ============================================================================
$stmt = $db->prepare("
    SELECT p.id, p.name, p.slug, p.updated_at,
           GROUP_CONCAT(c.name SEPARATOR ', ') as categories
    FROM products p
    LEFT JOIN product_categories pc ON p.id = pc.product_id
    LEFT JOIN categories c ON pc.category_id = c.id
    WHERE p.is_active = 1 
    GROUP BY p.id
    ORDER BY p.name ASC
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $product) {
    $productUrl = $baseUrl . '/pages/produs.php?slug=' . urlencode($product['slug']);
    
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($productUrl) . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d', strtotime($product['updated_at'] ?? 'now')) . '</lastmod>' . "\n";
    echo '    <changefreq>weekly</changefreq>' . "\n";
    echo '    <priority>0.8</priority>' . "\n";
    echo '  </url>' . "\n";
}

// ============================================================================
// 4. CATEGORII ACTIVE
// ============================================================================
$stmt = $db->prepare("
    SELECT id, name, slug, updated_at 
    FROM categories 
    WHERE is_active = 1 
    ORDER BY name ASC
");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($categories as $category) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($baseUrl . '/pages/magazin.php?category=' . $category['id']) . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d', strtotime($category['updated_at'] ?? 'now')) . '</lastmod>' . "\n";
    echo '    <changefreq>weekly</changefreq>' . "\n";
    echo '    <priority>0.7</priority>' . "\n";
    echo '  </url>' . "\n";
}

// ============================================================================
// 5. BLOG (dacă există - opțional pentru viitor)
// ============================================================================
// $stmt = $db->prepare("SELECT slug, updated_at FROM blog_posts WHERE published = 1");
// ...

// Închide XML
echo '</urlset>';

} catch (Exception $e) {
    // În caz de eroare, generează un sitemap minim valid
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars(SITE_URL) . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    echo '    <changefreq>daily</changefreq>' . "\n";
    echo '    <priority>1.0</priority>' . "\n";
    echo '  </url>' . "\n";
    echo '</urlset>';
    error_log('Sitemap error: ' . $e->getMessage());
}
?>

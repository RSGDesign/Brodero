<!-- 
==============================================================================
EXEMPLU DE INTEGRARE SEO în includes/header.php
==============================================================================

Adaugă acest cod la începutul fișierului includes/header.php,
ÎNAINTE de <!DOCTYPE html>

Acest cod va:
1. Include sistemul SEO
2. Detecta automat pagina curentă
3. Genera meta tags optimizate
4. Funcționa cu fallback dacă SEO nu există în DB
==============================================================================
-->

<?php
/**
 * Header pentru site-ul Brodero
 * Include navigare, logo și iconițe pentru coș și cont
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// ============================================================================
// ADAUGĂ AICI - SEO SYSTEM INTEGRATION
// ============================================================================
require_once __DIR__ . '/seo.php';

// Detectează pagina curentă pentru SEO
$currentScriptName = basename($_SERVER['PHP_SELF'], '.php');

// Mapare script → page slug
$pageSlugMap = [
    'index' => 'home',
    'magazin' => 'magazin',
    'contact' => 'contact',
    'program-referral' => 'program-referral',
    'cart' => 'cart',
    'produs' => null, // Handled separately pentru produse
];

$currentPageSlug = $pageSlugMap[$currentScriptName] ?? $currentScriptName;

// Pentru pagini produse, detectează din $_GET['slug']
if ($currentScriptName === 'produs' && isset($_GET['slug'])) {
    $isProductPage = true;
    $productSlugForSeo = $_GET['slug'];
} else {
    $isProductPage = false;
}

// Fallback values dacă nu există în DB
$seoFallback = [
    'title' => isset($pageTitle) ? $pageTitle . ' - Brodero' : 'Brodero - Produse Digitale Premium',
    'description' => isset($pageDescription) ? $pageDescription : 'Magazin online cu produse digitale de calitate premium.',
    'keywords' => isset($pageKeywords) ? $pageKeywords : 'produse digitale, șabloane, fonturi, mockup-uri',
];

// ============================================================================
// SFÂRȘIT INTEGRARE SEO
// ============================================================================

// Generare CSRF token pentru formulare
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verificare coș
$cartCount = 0;
if (isLoggedIn()) {
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cartCount = $row['total'] ?? 0;
    $stmt->close();
} elseif (isset($_SESSION['session_id'])) {
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = ?");
    $stmt->bind_param("s", $_SESSION['session_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cartCount = $row['total'] ?? 0;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php
    // ========================================================================
    // ÎNLOCUIEȘTE META TAGS VECHI CU SISTEMUL SEO NOU
    // ========================================================================
    
    if ($isProductPage && isset($productSlugForSeo)) {
        // Pentru pagini produse - trebuie să ai deja datele produsului încărcate
        // Exemplu: $product = getProductBySlug($productSlugForSeo, $db);
        
        // Dacă ai deja obiectul $product în pagina produs.php, folosește:
        if (isset($product)) {
            $productData = [
                'name' => $product['name'],
                'description' => strip_tags($product['description'] ?? ''),
                'category' => $product['category_name'] ?? 'Produse Digitale',
                'image' => SITE_URL . '/uploads/products/' . ($product['image'] ?? 'default.jpg'),
                'price' => $product['price'] ?? 0
            ];
            
            echo renderProductSeoTags($productSlugForSeo, $productData, $db);
        } else {
            // Fallback dacă produsul nu e încă încărcat
            echo renderSeoTags($currentPageSlug, $db, $seoFallback);
        }
    } else {
        // Pentru pagini normale (non-produse)
        echo renderSeoTags($currentPageSlug, $db, $seoFallback);
    }
    ?>
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars(SITE_URL . $_SERVER['REQUEST_URI']); ?>">
    
    <!-- Rest of head... -->
    <!-- Bootstrap, CSS, etc. -->
</head>
<body>
<!-- Rest of header HTML... -->


<!-- 
==============================================================================
NOTĂ IMPORTANTĂ:
==============================================================================

Dacă vrei să păstrezi compatibilitatea cu variabilele vechi $pageTitle, 
$pageDescription, $pageKeywords, poți folosi acest cod în fiecare pagină:

// În pages/contact.php (exemplu):
<?php
$pageTitle = "Contact";  // Opțional, pentru fallback
$pageDescription = "Contactează-ne pentru suport";  // Opțional
require_once __DIR__ . '/../includes/header.php';
?>

Sistemul SEO va folosi mai întâi datele din DB (seo_pages),
iar dacă nu există, va folosi $seoFallback care include $pageTitle, etc.

==============================================================================
ALTERNATIVĂ SIMPLĂ (fără modificare header.php):
==============================================================================

Dacă nu vrei să modifici header.php, poți adăuga SEO manual în fiecare pagină
DUPĂ apelul header.php dar ÎNAINTE de </head>:

<?php require_once __DIR__ . '/../includes/header.php'; ?>
<!-- Adaugă SEO tags aici -->
<script>
// Înlocuiește <title> și <meta> din JavaScript (nu recomandat)
</script>

SAU mai bine, adaugă direct în header.php cum este arătat mai sus.

==============================================================================
-->

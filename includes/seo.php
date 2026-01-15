<?php
/**
 * SEO Helper Functions - Brodero
 * 
 * Funcții pentru gestionarea SEO-ului per pagină:
 * - Obținere SEO din DB
 * - Generare meta tags
 * - Fallback la valori default
 * 
 * @version 1.0.0 - MVP SEO System
 */

if (!defined('DB_HOST')) {
    die('Direct access not permitted');
}

// ============================================================================
// 1. OBȚINERE SEO PENTRU PAGINĂ
// ============================================================================

/**
 * Obține datele SEO pentru o pagină specifică
 * 
 * @param string $pageSlug Slug-ul paginii (ex: 'home', 'magazin', 'product:product-slug')
 * @param PDO $db Conexiunea la baza de date
 * @return array|null Datele SEO sau null dacă nu există
 */
function getSeoForPage($pageSlug, $db) {
    try {
        $stmt = $db->prepare("
            SELECT page_slug, title, description, keywords, og_image, is_active
            FROM seo_pages
            WHERE page_slug = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$pageSlug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SEO Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Obține SEO pentru produse cu fallback la template default
 * 
 * @param string $productSlug Slug-ul produsului
 * @param array $productData Date produs (name, description, category)
 * @param PDO $db Conexiunea la baza de date
 * @return array Datele SEO procesate
 */
function getSeoForProduct($productSlug, $productData, $db) {
    // Verifică dacă există SEO specific pentru acest produs
    $specificSeo = getSeoForPage('product:' . $productSlug, $db);
    
    if ($specificSeo) {
        return $specificSeo;
    }
    
    // Fallback la template default
    $defaultTemplate = getSeoForPage('product:default', $db);
    
    if ($defaultTemplate) {
        // Înlocuiește placeholders cu date reale
        return [
            'title' => str_replace('{product_name}', $productData['name'] ?? 'Produs Digital', $defaultTemplate['title']),
            'description' => str_replace(
                ['{product_name}', '{product_description}', '{product_category}'],
                [
                    $productData['name'] ?? 'Produs Digital',
                    $productData['description'] ?? '',
                    $productData['category'] ?? 'produse digitale'
                ],
                $defaultTemplate['description']
            ),
            'keywords' => str_replace(
                '{product_category}',
                $productData['category'] ?? 'produse digitale',
                $defaultTemplate['keywords']
            ),
            'og_image' => $productData['image'] ?? $defaultTemplate['og_image'],
        ];
    }
    
    // Fallback complet - generare automată
    return [
        'title' => ($productData['name'] ?? 'Produs Digital') . ' - Brodero',
        'description' => $productData['description'] ?? 'Descarcă acest produs digital premium de la Brodero.',
        'keywords' => implode(', ', array_filter([
            $productData['category'] ?? '',
            'produs digital',
            'descărcare instant',
            'brodero'
        ])),
        'og_image' => $productData['image'] ?? null,
    ];
}

// ============================================================================
// 2. GENERARE META TAGS HTML
// ============================================================================

/**
 * Generează meta tags HTML pentru o pagină
 * 
 * @param string $pageSlug Slug-ul paginii
 * @param PDO $db Conexiunea la baza de date
 * @param array $fallback Valori fallback dacă nu există în DB
 * @return string HTML cu meta tags
 */
function renderSeoTags($pageSlug, $db, $fallback = []) {
    $seo = getSeoForPage($pageSlug, $db);
    
    // Dacă nu există SEO în DB, folosește fallback
    if (!$seo) {
        $seo = [
            'title' => $fallback['title'] ?? 'Brodero - Produse Digitale Premium',
            'description' => $fallback['description'] ?? 'Magazin online cu produse digitale de calitate premium.',
            'keywords' => $fallback['keywords'] ?? 'produse digitale, șabloane, fonturi',
            'og_image' => $fallback['og_image'] ?? null,
        ];
    }
    
    // Sanitizare valori
    $title = htmlspecialchars($seo['title'], ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($seo['description'] ?? '', ENT_QUOTES, 'UTF-8');
    $keywords = htmlspecialchars($seo['keywords'] ?? '', ENT_QUOTES, 'UTF-8');
    $ogImage = htmlspecialchars($seo['og_image'] ?? '', ENT_QUOTES, 'UTF-8');
    
    // Generare HTML
    $html = '';
    
    // Title
    $html .= "    <title>{$title}</title>\n";
    
    // Meta Description
    if (!empty($description)) {
        $html .= "    <meta name=\"description\" content=\"{$description}\">\n";
    }
    
    // Meta Keywords
    if (!empty($keywords)) {
        $html .= "    <meta name=\"keywords\" content=\"{$keywords}\">\n";
    }
    
    // Open Graph Tags
    $html .= "    <meta property=\"og:title\" content=\"{$title}\">\n";
    if (!empty($description)) {
        $html .= "    <meta property=\"og:description\" content=\"{$description}\">\n";
    }
    if (!empty($ogImage)) {
        $html .= "    <meta property=\"og:image\" content=\"{$ogImage}\">\n";
    }
    $html .= "    <meta property=\"og:type\" content=\"website\">\n";
    
    // Twitter Card
    $html .= "    <meta name=\"twitter:card\" content=\"summary_large_image\">\n";
    $html .= "    <meta name=\"twitter:title\" content=\"{$title}\">\n";
    if (!empty($description)) {
        $html .= "    <meta name=\"twitter:description\" content=\"{$description}\">\n";
    }
    if (!empty($ogImage)) {
        $html .= "    <meta name=\"twitter:image\" content=\"{$ogImage}\">\n";
    }
    
    return $html;
}

/**
 * Randează SEO tags pentru produse
 * 
 * @param string $productSlug Slug-ul produsului
 * @param array $productData Date produs
 * @param PDO $db Conexiunea la baza de date
 * @return string HTML cu meta tags
 */
function renderProductSeoTags($productSlug, $productData, $db) {
    $seo = getSeoForProduct($productSlug, $productData, $db);
    
    // Sanitizare
    $title = htmlspecialchars($seo['title'], ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($seo['description'] ?? '', ENT_QUOTES, 'UTF-8');
    $keywords = htmlspecialchars($seo['keywords'] ?? '', ENT_QUOTES, 'UTF-8');
    $ogImage = htmlspecialchars($seo['og_image'] ?? '', ENT_QUOTES, 'UTF-8');
    
    $html = '';
    $html .= "    <title>{$title}</title>\n";
    
    if (!empty($description)) {
        $html .= "    <meta name=\"description\" content=\"{$description}\">\n";
    }
    
    if (!empty($keywords)) {
        $html .= "    <meta name=\"keywords\" content=\"{$keywords}\">\n";
    }
    
    // Open Graph pentru produse
    $html .= "    <meta property=\"og:title\" content=\"{$title}\">\n";
    $html .= "    <meta property=\"og:type\" content=\"product\">\n";
    if (!empty($description)) {
        $html .= "    <meta property=\"og:description\" content=\"{$description}\">\n";
    }
    if (!empty($ogImage)) {
        $html .= "    <meta property=\"og:image\" content=\"{$ogImage}\">\n";
    }
    if (isset($productData['price'])) {
        $html .= "    <meta property=\"product:price:amount\" content=\"{$productData['price']}\">\n";
        $html .= "    <meta property=\"product:price:currency\" content=\"RON\">\n";
    }
    
    return $html;
}

// ============================================================================
// 3. FUNCȚII ADMIN - CRUD SEO
// ============================================================================

/**
 * Obține toate paginile SEO pentru dashboard
 * 
 * @param PDO $db Conexiunea la baza de date
 * @return array Lista de pagini SEO
 */
function getAllSeoPages($db) {
    try {
        $stmt = $db->query("
            SELECT id, page_slug, title, description, keywords, is_active, updated_at
            FROM seo_pages
            ORDER BY 
                CASE 
                    WHEN page_slug = 'home' THEN 1
                    WHEN page_slug LIKE 'product:%' THEN 3
                    ELSE 2
                END,
                page_slug ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SEO Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Obține o pagină SEO specifică pentru editare
 * 
 * @param int $id ID-ul paginii SEO
 * @param PDO $db Conexiunea la baza de date
 * @return array|null Datele paginii sau null
 */
function getSeoPageById($id, $db) {
    try {
        $stmt = $db->prepare("
            SELECT id, page_slug, title, description, keywords, og_image, is_active
            FROM seo_pages
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SEO Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Creează sau actualizează o pagină SEO
 * 
 * @param array $data Date SEO (page_slug, title, description, keywords, og_image, is_active)
 * @param PDO $db Conexiunea la baza de date
 * @param int|null $id ID pentru update, null pentru insert
 * @return bool Success
 */
function saveSeoPage($data, $db, $id = null) {
    try {
        if ($id) {
            // Update
            $stmt = $db->prepare("
                UPDATE seo_pages
                SET page_slug = ?, title = ?, description = ?, keywords = ?, og_image = ?, is_active = ?
                WHERE id = ?
            ");
            return $stmt->execute([
                $data['page_slug'],
                $data['title'],
                $data['description'] ?? null,
                $data['keywords'] ?? null,
                $data['og_image'] ?? null,
                $data['is_active'] ?? 1,
                $id
            ]);
        } else {
            // Insert
            $stmt = $db->prepare("
                INSERT INTO seo_pages (page_slug, title, description, keywords, og_image, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['page_slug'],
                $data['title'],
                $data['description'] ?? null,
                $data['keywords'] ?? null,
                $data['og_image'] ?? null,
                $data['is_active'] ?? 1
            ]);
        }
    } catch (PDOException $e) {
        error_log("SEO Save Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Șterge o pagină SEO
 * 
 * @param int $id ID-ul paginii SEO
 * @param PDO $db Conexiunea la baza de date
 * @return bool Success
 */
function deleteSeoPage($id, $db) {
    try {
        $stmt = $db->prepare("DELETE FROM seo_pages WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("SEO Delete Error: " . $e->getMessage());
        return false;
    }
}

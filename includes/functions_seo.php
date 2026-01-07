<?php
/**
 * SEO Helper Functions
 * Funcții ajutătoare pentru generarea de structured data (Schema.org)
 */

/**
 * Generează Product Schema pentru o pagină de produs
 * 
 * @param array $product Array cu detalii produs (name, description, price, sale_price, image, etc.)
 * @return string JSON-LD script tag
 */
function generateProductSchema($product) {
    $baseUrl = SITE_URL;
    
    // Calculare preț final
    $price = $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'];
    $availability = $product['is_active'] ? 'InStock' : 'OutOfStock';
    
    // Imagine produs
    $imageUrl = !empty($product['image']) 
        ? $baseUrl . '/uploads/' . $product['image']
        : $baseUrl . '/assets/images/placeholder.jpg';
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product['name'],
        'description' => strip_tags($product['description']),
        'image' => $imageUrl,
        'sku' => 'PROD-' . $product['id'],
        'offers' => [
            '@type' => 'Offer',
            'url' => $baseUrl . '/pages/produs.php?slug=' . $product['slug'],
            'priceCurrency' => 'RON',
            'price' => number_format($price, 2, '.', ''),
            'availability' => 'https://schema.org/' . $availability,
            'seller' => [
                '@type' => 'Organization',
                'name' => SITE_NAME
            ]
        ]
    ];
    
    // Adaugă rating dacă există
    if (isset($product['rating']) && $product['rating'] > 0) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $product['rating'],
            'ratingCount' => $product['review_count'] ?? 1
        ];
    }
    
    $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
    return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>';
}

/**
 * Generează BreadcrumbList Schema
 * 
 * @param array $breadcrumbs Array de breadcrumbs: [['name' => 'Acasă', 'url' => '/'], ...]
 * @return string JSON-LD script tag
 */
function generateBreadcrumbSchema($breadcrumbs) {
    $items = [];
    
    foreach ($breadcrumbs as $index => $crumb) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $crumb['name'],
            'item' => SITE_URL . $crumb['url']
        ];
    }
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items
    ];
    
    $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
    return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>';
}

/**
 * Generează FAQ Schema
 * 
 * @param array $faqs Array de întrebări: [['question' => '...', 'answer' => '...'], ...]
 * @return string JSON-LD script tag
 */
function generateFAQSchema($faqs) {
    $questions = [];
    
    foreach ($faqs as $faq) {
        $questions[] = [
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer']
            ]
        ];
    }
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $questions
    ];
    
    $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
    return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>';
}

/**
 * Generează WebSite Schema cu search box
 * 
 * @return string JSON-LD script tag
 */
function generateWebsiteSchema() {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => SITE_NAME,
        'url' => SITE_URL,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => SITE_URL . '/pages/magazin.php?search={search_term_string}'
            ],
            'query-input' => 'required name=search_term_string'
        ]
    ];
    
    $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
    return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>';
}

/**
 * Sanitizează text pentru meta description
 * 
 * @param string $text Text de sanitizat
 * @param int $maxLength Lungime maximă (default: 160)
 * @return string Text sanitizat și trunchiat
 */
function sanitizeMetaDescription($text, $maxLength = 160) {
    // Elimină HTML tags
    $text = strip_tags($text);
    
    // Elimină whitespace excesiv
    $text = preg_replace('/\s+/', ' ', $text);
    
    // Trimite
    $text = trim($text);
    
    // Trunchiere la lungime maximă
    if (strlen($text) > $maxLength) {
        $text = substr($text, 0, $maxLength - 3) . '...';
    }
    
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Generează URL canonic curat
 * 
 * @param string $url URL-ul curent (opțional)
 * @return string URL canonic
 */
function getCanonicalUrl($url = null) {
    if ($url === null) {
        $url = SITE_URL . $_SERVER['REQUEST_URI'];
    }
    
    // Elimină parametrii de query
    $url = strtok($url, '?');
    
    // Elimină trailing slash (doar dacă nu e homepage)
    if ($url !== SITE_URL . '/' && substr($url, -1) === '/') {
        $url = rtrim($url, '/');
    }
    
    return $url;
}
?>

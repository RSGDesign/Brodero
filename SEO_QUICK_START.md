# 🚀 Quick Start - SEO System

## În 3 Pași

### 1️⃣ Import Baza de Date

```bash
mysql -u root -p u107933880_brodero < database_seo_pages.sql
```

### 2️⃣ Gestionare din Dashboard

```
URL: https://brodero.online/admin/seo-pages.php
Funcții: Add, Edit, Delete SEO pages
```

### 3️⃣ Integrare în Pagini

**Opțiunea A - În fiecare pagină:**
```php
<?php
require_once __DIR__ . '/../includes/seo.php';
$db = getDB();
?>
<!DOCTYPE html>
<html>
<head>
    <?php echo renderSeoTags('contact', $db); ?>
</head>
```

**Opțiunea B - În header.php (RECOMANDAT):**
```php
<?php
// La începutul includes/header.php
require_once __DIR__ . '/seo.php';
$db = getDB();

// Detectează pagina curentă
$currentPage = $_GET['page'] ?? basename($_SERVER['PHP_SELF'], '.php');
$pageSlug = $currentPage === 'index' ? 'home' : $currentPage;
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php 
    // Renderează SEO tags automat
    echo renderSeoTags($pageSlug, $db, [
        'title' => 'Brodero - Produse Digitale',
        'description' => 'Magazin online produse digitale',
        'keywords' => 'produse digitale'
    ]); 
    ?>
    
    <!-- GA4 și restul head-ului -->
```

**Pentru Produse (pages/produs.php):**
```php
<?php
require_once __DIR__ . '/../includes/seo.php';
$db = getDB();

// După ce obții produsul din DB
$product = getProductBySlug($_GET['slug'], $db);

$productData = [
    'name' => $product['name'],
    'description' => strip_tags($product['description']),
    'category' => $product['category_name'] ?? 'Produse Digitale',
    'image' => SITE_URL . '/uploads/products/' . $product['image'],
    'price' => $product['price']
];
?>
<!DOCTYPE html>
<html>
<head>
    <?php echo renderProductSeoTags($product['slug'], $productData, $db); ?>
</head>
```

---

## ✅ Verificare Rapidă

1. **Dashboard funcțional?**
   - Accesează: `/admin/seo-pages.php`
   - Ar trebui să vezi 6 pagini default

2. **Meta tags apar?**
   - Accesează orice pagină
   - View Source (Ctrl+U)
   - Caută `<meta name="description"`

3. **Sitemap valid?**
   - Accesează: `/sitemap.xml.php`
   - Ar trebui să vezi XML cu pagini

---

## 📊 Exemple Configurare

### Homepage
- **Slug**: `home`
- **Title**: `Brodero - Produse Digitale Premium pentru Creativi`
- **Description**: `Descoperă produse digitale de calitate: șabloane grafice, fonturi, mockup-uri și resurse premium.`
- **Keywords**: `produse digitale, șabloane grafice, fonturi, mockup-uri`

### Magazin
- **Slug**: `magazin`
- **Title**: `Magazin - Produse Digitale Brodero`
- **Description**: `Explorează magazinul nostru cu produse digitale premium: șabloane, fonturi, texture.`
- **Keywords**: `magazin online, produse digitale, șabloane premium`

### Produs Specific
- **Slug**: `product:template-social-media`
- **Title**: `Template Social Media Premium - 50 Design-uri`
- **Description**: `Descarcă pachetul complet de template-uri pentru Instagram, Facebook și TikTok.`
- **Keywords**: `template social media, instagram templates, design`

### Produs Default (Fallback)
- **Slug**: `product:default`
- **Title**: `{product_name} - Brodero`
- **Description**: `Descarcă {product_name} - produs digital premium.`

Variabilele `{product_name}`, `{product_description}`, `{product_category}` sunt înlocuite automat.

---

## 🔍 Google Search Console

### Submit Sitemap
```
1. Google Search Console → Sitemaps
2. Add: https://brodero.online/sitemap.xml.php
3. Submit
```

### Verificare Indexare
```
site:brodero.online
```

---

## 🐛 Troubleshooting Rapid

**Meta tags nu apar?**
→ Verifică că `includes/seo.php` e inclus  
→ Verifică că `renderSeoTags()` e apelat în `<head>`

**"Page not found in DB"?**
→ Adaugă pagina în dashboard  
→ SAU folosește fallback în `renderSeoTags()`

**Sitemap gol?**
→ Verifică conexiunea la DB  
→ Verifică că ai produse/categorii active

---

## 📞 Link-uri Rapide

- Dashboard: `/admin/seo-pages.php`
- Sitemap: `/sitemap.xml.php`
- Robots: `/robots.txt`
- Documentație: `SEO_IMPLEMENTATION.md`

---

**Status:** ✅ Ready to Deploy  
**Next Step:** Import DB → Test Dashboard → Integrate în header.php

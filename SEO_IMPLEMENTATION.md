# 🎯 SEO Management System - Documentație Implementare

## 📋 Overview

Sistem MVP pentru gestionarea SEO-ului per pagină:
- **Meta Tags**: Title, Description, Keywords
- **Dashboard Admin**: CRUD complet pentru pagini SEO
- **Integrare automată**: Meta tags în frontend
- **Sitemap dinamic**: Include SEO pages + produse
- **Robots.txt**: Configurare indexare

---

## ✅ Instalare

### 1️⃣ Rulează Migrarea Bazei de Date

```bash
mysql -u root -p u107933880_brodero < database_seo_pages.sql
```

Sau din phpMyAdmin / Hostinger:
- Importă fișierul `database_seo_pages.sql`
- Va crea tabelul `seo_pages` cu date default

### 2️⃣ Verifică Instalarea

```sql
SELECT COUNT(*) FROM seo_pages;
-- Ar trebui să returneze 6 intrări (pagini default)
```

---

## 📁 Structură Fișiere

```
Brodero/
├── database_seo_pages.sql          # Migrare DB
├── includes/seo.php                # Helper functions SEO
├── admin/seo-pages.php             # Dashboard SEO
├── ajax/seo_pages.php              # AJAX handlers CRUD
├── sitemap.xml.php                 # Sitemap dinamic actualizat
├── robots.txt                      # Robots.txt actualizat
└── SEO_IMPLEMENTATION.md           # Acest fișier
```

---

## 🎛️ Utilizare Dashboard

### Acces Dashboard

```
URL: https://brodero.online/admin/seo-pages.php
Cerință: Login ca Admin
```

**Features:**
- ✅ Vizualizare toate paginile SEO
- ✅ Adăugare pagină SEO nouă
- ✅ Editare SEO existent
- ✅ Ștergere pagină SEO
- ✅ Status activ/inactiv
- ✅ Character counter (title/description)

### Adăugare Pagină SEO Nouă

1. Click **"Adaugă Pagină SEO"**
2. Completează:
   - **Page Slug**: `despre-noi` sau `product:template-premium`
   - **Meta Title**: 50-60 caractere (recomandare)
   - **Meta Description**: 150-160 caractere
   - **Keywords**: `keyword1, keyword2, keyword3`
   - **OG Image**: URL imagine (opțional)
3. Click **"Salvează"**

### Format Page Slug

| Tip Pagină | Format Slug | Exemplu |
|------------|-------------|---------|
| Pagină statică | `slug-pagina` | `contact`, `despre-noi` |
| Produs specific | `product:slug-produs` | `product:template-grafic` |
| Template produse | `product:default` | (folosit ca fallback) |

---

## 🔧 Integrare în Frontend

### Opțiunea 1: Pagini Statice

În orice pagină PHP (ex: `pages/contact.php`):

```php
<?php
require_once __DIR__ . '/../includes/seo.php';
$db = getDB();

// În <head>
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php 
    // Renderează SEO tags pentru pagina 'contact'
    echo renderSeoTags('contact', $db, [
        'title' => 'Contact - Brodero',  // Fallback dacă nu există în DB
        'description' => 'Contactează-ne pentru orice întrebare',
        'keywords' => 'contact, suport'
    ]); 
    ?>
    
    <!-- Restul head-ului -->
</head>
```

### Opțiunea 2: Pagini Produse

În `pages/produs.php`:

```php
<?php
require_once __DIR__ . '/../includes/seo.php';
$db = getDB();

// Obține datele produsului
$product = getProductBySlug($_GET['slug'], $db);

$productData = [
    'name' => $product['name'],
    'description' => $product['description'],
    'category' => $product['category_name'],
    'image' => SITE_URL . '/uploads/products/' . $product['image'],
    'price' => $product['price']
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    
    <?php 
    // Renderează SEO tags pentru produs
    echo renderProductSeoTags($product['slug'], $productData, $db);
    ?>
</head>
```

### Opțiunea 3: Header Global

În `includes/header.php` (recomandare):

```php
<?php
require_once __DIR__ . '/seo.php';
$db = getDB();

// Detectează pagina curentă
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageSlug = $currentPage === 'index' ? 'home' : $currentPage;
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php 
    echo renderSeoTags($pageSlug, $db, [
        'title' => 'Brodero - Produse Digitale Premium',
        'description' => 'Magazin online cu produse digitale de calitate',
        'keywords' => 'produse digitale'
    ]); 
    ?>
    
    <!-- Rest of header -->
</head>
```

---

## 📊 Output Meta Tags

Funcția `renderSeoTags()` generează:

```html
<title>Brodero - Produse Digitale Premium</title>
<meta name="description" content="Descoperă produse digitale de calitate...">
<meta name="keywords" content="produse digitale, șabloane grafice, fonturi">

<!-- Open Graph -->
<meta property="og:title" content="Brodero - Produse Digitale Premium">
<meta property="og:description" content="Descoperă produse digitale...">
<meta property="og:image" content="https://brodero.online/assets/images/og.jpg">
<meta property="og:type" content="website">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Brodero - Produse Digitale Premium">
<meta name="twitter:description" content="Descoperă produse digitale...">
<meta name="twitter:image" content="https://brodero.online/assets/images/og.jpg">
```

---

## 🗺️ Sitemap Dinamic

**URL:** `https://brodero.online/sitemap.xml.php`

### Include:

1. **Pagini SEO active** (din `seo_pages`)
   - Homepage, magazin, contact, etc.
2. **Produse active** (din `products`)
   - Cu lastmod din `updated_at`
3. **Categorii active** (din `categories`)

### Submit la Google

```
Google Search Console → Sitemaps → Add new sitemap
URL: https://brodero.online/sitemap.xml.php
```

---

## 🤖 Robots.txt

**URL:** `https://brodero.online/robots.txt`

### Configurare:

✅ **Permite indexare:**
- Pagini publice (`/`, `/pages/magazin.php`, `/pages/produs.php`)
- Assets și imagini (`/assets/`, `/uploads/`)

❌ **Blochează indexare:**
- Admin (`/admin/`)
- Checkout și cont (`/pages/checkout.php`, `/pages/cont.php`)
- Config și logs (`/config/`, `/logs/`)
- Fișiere sensibile (`*.sql`, `*.md`)

---

## 🧪 Testare

### 1. Test Dashboard SEO

```
1. Login ca admin
2. Accesează: https://brodero.online/admin/seo-pages.php
3. Verifică că apar 6 pagini default
4. Adaugă o pagină nouă: slug = "test", title = "Test SEO"
5. Editează și șterge
```

### 2. Test Frontend

```
1. Accesează: https://brodero.online/pages/magazin.php
2. View Source (Ctrl+U)
3. Caută <title> și <meta name="description">
4. Verifică că apar valorile din dashboard
```

### 3. Test Sitemap

```
1. Accesează: https://brodero.online/sitemap.xml.php
2. Verifică că apar:
   - Pagina home
   - Pagini SEO active
   - Produse active
   - Categorii active
```

### 4. Test Robots.txt

```
1. Accesează: https://brodero.online/robots.txt
2. Verifică că blochează /admin/, /checkout, etc.
```

### 5. Test Google Rich Results

```
URL: https://search.google.com/test/rich-results
Testează: https://brodero.online/pages/produs.php?slug=exemplu
Verifică: Meta tags, OG tags, structured data
```

---

## 📝 Exemple Utilizare

### Exemplu 1: Adaugă SEO pentru "Despre Noi"

```sql
-- Manual în DB (sau prin dashboard)
INSERT INTO seo_pages (page_slug, title, description, keywords, is_active) VALUES
('despre-noi', 
 'Despre Brodero - Echipa Ta de Produse Digitale',
 'Aflați povestea Brodero și misiunea noastră de a oferi produse digitale premium pentru creativi și antreprenori.',
 'despre noi, echipă, misiune, brodero story',
 1);
```

### Exemplu 2: SEO Specific pentru un Produs

```sql
-- Produs cu slug "template-social-media"
INSERT INTO seo_pages (page_slug, title, description, keywords, is_active) VALUES
('product:template-social-media', 
 'Template Social Media Premium - 50 Design-uri Gata Făcute',
 'Descarcă pachetul complet de template-uri pentru Instagram, Facebook și TikTok. Format editabil, 4K quality.',
 'template social media, instagram templates, facebook templates, social media design',
 1);
```

### Exemplu 3: Update prin PHP

```php
require_once __DIR__ . '/includes/seo.php';
$db = getDB();

$data = [
    'page_slug' => 'magazin',
    'title' => 'Magazin Nou - Brodero 2026',
    'description' => 'Explorează noua colecție de produse digitale.',
    'keywords' => 'magazin, produse noi, colecție 2026',
    'is_active' => 1
];

$id = 2; // ID-ul paginii 'magazin'
saveSeoPage($data, $db, $id);
```

---

## 🔍 API Functions

### `getSeoForPage($pageSlug, $db)`

Returnează datele SEO pentru un page_slug.

```php
$seo = getSeoForPage('contact', $db);
// Returns: ['title' => '...', 'description' => '...', 'keywords' => '...']
```

### `getSeoForProduct($productSlug, $productData, $db)`

Returnează SEO pentru produse cu fallback la template.

```php
$seo = getSeoForProduct('template-grafic', [
    'name' => 'Template Grafic Premium',
    'description' => 'Design modern...',
    'category' => 'Templates',
    'image' => '/uploads/...'
], $db);
```

### `renderSeoTags($pageSlug, $db, $fallback)`

Generează HTML meta tags.

```php
echo renderSeoTags('magazin', $db, [
    'title' => 'Magazin - Brodero',
    'description' => 'Fallback description',
    'keywords' => 'fallback, keywords'
]);
```

### `renderProductSeoTags($productSlug, $productData, $db)`

Generează meta tags pentru produse (include product schema).

```php
echo renderProductSeoTags('template-social', [
    'name' => 'Template Social Media',
    'description' => '...',
    'category' => 'Templates',
    'price' => 99.00,
    'image' => '...'
], $db);
```

---

## 🚀 Best Practices SEO

### Meta Title
- ✅ 50-60 caractere
- ✅ Include keyword principal
- ✅ Format: `[Keyword] - Brodero`
- ❌ Nu duplica titluri

### Meta Description
- ✅ 150-160 caractere
- ✅ Call-to-action clar
- ✅ Descriere utilă pentru user
- ❌ Nu keyword stuffing

### Keywords
- ✅ 5-10 keywords relevante
- ✅ Separate prin virgulă
- ✅ Include variații
- ❌ Nu keywords irelevante

### Open Graph Image
- ✅ 1200x630px recomandat
- ✅ Format: JPG/PNG
- ✅ URL absolut
- ✅ Relevantă pentru conținut

---

## 📊 Google Search Console Setup

### 1. Verificare Proprietate

```
1. Accesează: https://search.google.com/search-console
2. Adaugă proprietate: https://brodero.online
3. Verificare prin DNS sau HTML tag
```

### 2. Submit Sitemap

```
Sitemaps → Add new sitemap
URL: https://brodero.online/sitemap.xml.php
```

### 3. Monitorizare

- **Performance**: Click-uri, impressions, CTR
- **Coverage**: Pagini indexate vs. erori
- **Enhancements**: Rich results, mobile usability

---

## 🐛 Troubleshooting

### Problema: Meta tags nu apar

**Soluție:**
```php
// Verifică include-ul în header.php
require_once __DIR__ . '/seo.php';

// Verifică că funcția e apelată ÎNAINTE de </head>
echo renderSeoTags('home', $db);
```

### Problema: "Page not found in seo_pages"

**Soluție:**
```php
// Folosește fallback
echo renderSeoTags('pagina-noua', $db, [
    'title' => 'Pagina Nouă - Brodero',
    'description' => 'Descriere default',
    'keywords' => 'keywords, default'
]);

// SAU adaugă în dashboard
```

### Problema: Sitemap nu apare în Google

**Soluție:**
```bash
# 1. Verifică că e accesibil
curl https://brodero.online/sitemap.xml.php

# 2. Verifică robots.txt
curl https://brodero.online/robots.txt

# 3. Submit manual în Google Search Console
```

### Problema: Duplicate title/description

**Soluție:**
```sql
-- Verifică duplicate
SELECT title, COUNT(*) 
FROM seo_pages 
GROUP BY title 
HAVING COUNT(*) > 1;

-- Update duplicate
UPDATE seo_pages 
SET title = 'Titlu Unic' 
WHERE id = X;
```

---

## 📈 Extensii Viitoare (Post-MVP)

### Opțional (nu în MVP):
- ❌ SEO Scoring (analiza calității SEO)
- ❌ Multi-language SEO
- ❌ Bulk import/export SEO
- ❌ AI-generated descriptions
- ❌ Schema.org structured data advanced
- ❌ Canonical URLs management
- ❌ Hreflang tags

---

## ✅ Checklist Deployment

- [x] Import `database_seo_pages.sql`
- [x] Verify `seo_pages` table created
- [x] Add SEO link in admin sidebar
- [x] Test dashboard: add/edit/delete SEO pages
- [x] Integrate `renderSeoTags()` in header/pages
- [x] Test frontend meta tags (view source)
- [x] Verify sitemap.xml.php includes SEO pages
- [x] Submit sitemap to Google Search Console
- [x] Test robots.txt blocking /admin/
- [x] Verify OG tags in Facebook Debugger
- [ ] Monitor Google Search Console for errors
- [ ] Add custom SEO for top 10 products

---

## 📞 Support

**Dashboard:** `https://brodero.online/admin/seo-pages.php`  
**Sitemap:** `https://brodero.online/sitemap.xml.php`  
**Robots:** `https://brodero.online/robots.txt`

**Documentație completă:** `SEO_IMPLEMENTATION.md`

---

**Status:** ✅ MVP Complete - Ready for Production  
**Versiune:** 1.0.0  
**Data:** 15 Ianuarie 2026

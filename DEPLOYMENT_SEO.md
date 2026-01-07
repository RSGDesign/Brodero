# 🚀 Deployment Guide - SEO Setup pentru Brodero

## ✅ Fișiere Noi Create

### 1. **SEO Core Files**
```
robots.txt                          - Permite crawling + referință sitemap
sitemap.xml.php                     - Generator sitemap dinamic
includes/functions_seo.php          - Helper functions pentru Schema.org
SEO_CHECKLIST.md                    - Documentație completă SEO
```

### 2. **Fișiere Modificate**
```
includes/header.php                 - Meta tags SEO + Open Graph + Schema.org
pages/produs.php                    - Product schema + slug-based URLs
.htaccess                           - (trebuie actualizat manual)
```

---

## 📋 Pași de Deployment

### PASUL 1: Upload Fișiere

Încarcă următoarele fișiere noi pe server:

```bash
# Root directory
/robots.txt
/sitemap.xml.php
/favicon.ico
/SEO_CHECKLIST.md

# Includes directory
/includes/functions_seo.php

# Fișiere modificate
/includes/header.php (SUPRASCRIE)
/pages/produs.php (SUPRASCRIE)
```

### PASUL 2: Actualizează .htaccess

Adaugă următoarele reguli în `.htaccess` (în root):

```apache
# ============================================================================
# SITEMAP REDIRECT
# ============================================================================
RewriteEngine On
RewriteRule ^sitemap\.xml$ sitemap.xml.php [L]

# ============================================================================
# HTTPS REDIRECT (Decomentează când ai SSL)
# ============================================================================
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# ============================================================================
# WWW REDIRECT (Alege: cu sau fără www)
# ============================================================================
# Fără www → cu www:
# RewriteCond %{HTTP_HOST} !^www\. [NC]
# RewriteRule ^(.*)$ https://www.%{HTTP_HOST}/$1 [L,R=301]

# Cu www → fără www:
# RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
# RewriteRule ^(.*)$ https://%1/$1 [L,R=301]

# ============================================================================
# COMPRESSION
# ============================================================================
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

# ============================================================================
# BROWSER CACHING
# ============================================================================
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>

# ============================================================================
# SECURITY HEADERS
# ============================================================================
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"
</IfModule>
```

### PASUL 3: Verifică config.php

Asigură-te că `SITE_URL` este corect în `config/config.php`:

```php
// ❌ GREȘIT (localhost)
define('SITE_URL', 'http://localhost/brodero');

// ✅ CORECT (producție)
define('SITE_URL', 'https://brodero.ro');
// SAU
define('SITE_URL', 'https://www.brodero.ro');
```

### PASUL 4: Actualizează robots.txt

Deschide `robots.txt` și actualizează URL-ul sitemap-ului:

```
User-agent: *
Disallow:

Sitemap: https://brodero.ro/sitemap.xml
```

**⚠️ IMPORTANT**: Înlocuiește `https://brodero.ro` cu domeniul tău real!

---

## 🧪 Testare Post-Deployment

### 1. Verifică robots.txt
```
https://brodero.ro/robots.txt
```

**Ce ar trebui să vezi:**
```
User-agent: *
Disallow:

Sitemap: https://brodero.ro/sitemap.xml
```

### 2. Verifică sitemap.xml
```
https://brodero.ro/sitemap.xml
```

**Ce ar trebui să vezi:**
- XML valid cu toate URL-urile
- Homepage, pagini statice, categorii, produse
- Format: `<url>`, `<loc>`, `<lastmod>`, etc.

### 3. Testează Meta Tags

Accesează homepage și View Source (Ctrl+U):

**Verifică prezența:**
- `<title>` unic
- `<meta name="description">`
- `<link rel="canonical">`
- `<meta property="og:...">` (Open Graph)
- `<script type="application/ld+json">` (Schema.org)

### 4. Testează o Pagină de Produs

Accesează: `https://brodero.ro/pages/produs.php?slug=nume-produs`

**View Source și verifică:**
- Product Schema (JSON-LD)
- Open Graph image (og:image)
- Canonical URL

### 5. Test Mobile-Friendly

Vizitează: https://search.google.com/test/mobile-friendly

Introdu URL-ul site-ului și verifică că este mobile-friendly.

### 6. Test Rich Results

Vizitează: https://search.google.com/test/rich-results

Testează o pagină de produs pentru Product Schema.

---

## 📊 Google Search Console Setup

### PASUL 1: Adaugă Property

1. Mergi la: https://search.google.com/search-console
2. Apasă **Add Property**
3. Alege **URL prefix**: `https://brodero.ro`

### PASUL 2: Verifică Proprietatea

**Opțiunea 1: HTML Tag** (RECOMANDAT)
1. Copiază tag-ul: `<meta name="google-site-verification" content="...">``
2. Adaugă în `includes/header.php` după `<meta name="robots">`
3. Re-upload `header.php`
4. Apasă **Verify** în GSC

**Opțiunea 2: HTML File Upload**
1. Descarcă fișierul `google...html`
2. Încarcă în root (lângă `index.php`)
3. Apasă **Verify**

### PASUL 3: Trimite Sitemap-ul

1. În GSC, mergi la **Sitemaps** (meniu stânga)
2. Introdu: `https://brodero.ro/sitemap.xml`
3. Apasă **Submit**

**Status ar trebui să fie:** ✅ Success

### PASUL 4: Monitorizare

**Verifică după 48-72 ore:**
- **Coverage** - pagini indexate
- **Mobile Usability** - erori mobile
- **Core Web Vitals** - performanță
- **Manual Actions** - penalizări

---

## 🔍 Debugging - Dacă ceva nu merge

### robots.txt nu se încarcă
- Verifică că fișierul este în root (`/robots.txt`)
- Verifică permisiuni: `chmod 644 robots.txt`
- Test: `curl https://brodero.ro/robots.txt`

### sitemap.xml returnează 404
- Verifică că `.htaccess` are regula de rewrite
- Verifică că `sitemap.xml.php` există în root
- Verifică că `mod_rewrite` este activat pe server
- Test manual: `https://brodero.ro/sitemap.xml.php`

### Meta tags nu apar
- Curăță cache-ul browser-ului (Ctrl+Shift+R)
- Verifică că `header.php` actualizat este pe server
- Verifică `config.php` - `SITE_URL` corect?

### Schema.org erori
- Testează cu: https://validator.schema.org/
- Verifică că `functions_seo.php` este încărcat
- Verifică că produsul are toate câmpurile (name, price, etc.)

### Produsele nu apar în sitemap
- Verifică că produsele au `is_active = 1` în DB
- Verifică conectarea la baza de date în `sitemap.xml.php`
- Verifică logs-urile PHP pentru erori

---

## ✅ Checklist Final - Înainte de Launch

- [ ] Toate fișierele noi uploadate
- [ ] `header.php` și `produs.php` suprascrise
- [ ] `.htaccess` actualizat cu reguli SEO
- [ ] `config.php` are URL-ul de producție corect
- [ ] `robots.txt` verificat în browser
- [ ] `sitemap.xml` verificat în browser (returnează XML valid)
- [ ] Meta tags verificate pe 3-5 pagini (View Source)
- [ ] Product Schema verificat cu Rich Results Test
- [ ] Mobile-Friendly Test passed
- [ ] Google Search Console property adăugată
- [ ] Sitemap trimis în GSC
- [ ] SSL activ (HTTPS) - OBLIGATORIU pentru producție
- [ ] WWW redirect configurat (alege: cu sau fără www)

---

## 📞 Support & Next Steps

### După 7-14 zile de la launch:

1. **Verifică GSC** pentru:
   - Pagini indexate (Coverage)
   - Erori de crawling
   - Core Web Vitals

2. **Optimizări suplimentare:**
   - Adaugă imagini optimizate (WebP)
   - Implementează lazy loading
   - Optimizează Core Web Vitals
   - Creează conținut blog (fresh content)

3. **Link Building:**
   - Directoare locale
   - Social media profiles
   - Guest posting
   - Partnerships

---

## 📅 Timeline Indexare

- **24-48 ore**: robots.txt & sitemap descoperite
- **3-7 zile**: Primele pagini indexate (homepage, produse principale)
- **2-4 săptămâni**: Indexare completă
- **1-3 luni**: Început de ranking pentru long-tail keywords

**⚠️ Notă**: Indexarea depinde de: domain authority, conținut, backlinks, competiție

---

**Data creării**: 7 ianuarie 2026  
**Versiune**: 1.0 - SEO MVP Ready  
**Status**: ✅ Ready for Deployment

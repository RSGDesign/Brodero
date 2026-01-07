# 📊 Checklist SEO MVP - Brodero

## ✅ Implementat

### 1. **Acces Crawlere**
- ✅ **robots.txt** creat
  - Permite accesul tuturor crawlerelor (`User-agent: *`)
  - Include referință la sitemap
  - Locație: `/robots.txt`

### 2. **Sitemap XML**
- ✅ **sitemap.xml.php** creat
  - Generare dinamică din baza de date
  - Include: homepage, pagini statice, categorii, produse
  - Format standard XML cu `<loc>`, `<lastmod>`, `<changefreq>`, `<priority>`
  - Accesibil la: `/sitemap.xml` (prin .htaccess redirect)
  - Referit în robots.txt: `Sitemap: https://brodero.online/sitemap.xml`

### 3. **Meta Tags SEO**
- ✅ **Title tag** unic pe fiecare pagină
  - Format: `[Page Title] - Brodero - Design de Broderie Premium`
  - Limit ~60 caractere
  
- ✅ **Meta description** pe fiecare pagină
  - Descriere unică și relevantă
  - Limit ~160 caractere
  - Fallback pentru pagini fără descriere definită

- ✅ **Meta keywords** (opțional, pentru compatibilitate)
- ✅ **Meta robots**: `index, follow` pe pagini publice
- ✅ **Meta author**: Brodero

### 4. **Canonical URLs**
- ✅ Implementat pe toate paginile
- ✅ Curăță parametrii de tracking din URL
- ✅ Format: `<link rel="canonical" href="URL-ul corect">`

### 5. **Open Graph Tags**
- ✅ og:type, og:url, og:title, og:description
- ✅ og:image (cu fallback la imagine default)
- ✅ og:site_name, og:locale

### 6. **Twitter Cards**
- ✅ twitter:card, twitter:url, twitter:title
- ✅ twitter:description, twitter:image

### 7. **Structured Data (Schema.org)**
- ✅ Organization schema pentru companie
- ✅ JSON-LD format
- 📝 TODO: Product schema pentru fiecare produs

### 8. **Structură HTML Semantică**
- ✅ `<header>` pentru navigare
- ✅ `<main>` pentru conținut principal
- ✅ `<footer>` pentru footer
- ✅ `<nav>` cu role="navigation"
- ✅ `<section>`, `<article>` unde este cazul
- ✅ Heading hierarchy: H1 → H2 → H3

### 9. **URL-uri SEO-friendly**
- ✅ Lowercase
- ✅ Separate prin `-`
- ✅ Folosesc slug-uri: `/pages/produs.php?slug=logo-brand`
- 📝 TODO: Rewrite rules pentru URL-uri și mai curate (`/produs/logo-brand`)

### 10. **Mobile-Friendly**
- ✅ Viewport meta tag
- ✅ Responsive design (Bootstrap 5)
- ✅ Touch-friendly buttons
- ✅ Text lizibil fără zoom

### 11. **Performance**
- ✅ Server-side rendering (PHP)
- ✅ CSS/JS minificate (CDN)
- ✅ Browser caching (.htaccess)
- ✅ GZIP compression (.htaccess)
- 📝 TODO: Optimizare imagini (WebP, lazy loading)

### 12. **Security Headers**
- ✅ X-Frame-Options: SAMEORIGIN
- ✅ X-XSS-Protection: 1; mode=block
- ✅ X-Content-Type-Options: nosniff

---

## 📋 Verificări Necesare

### Înainte de Launch:

1. **Google Search Console**
   - [ ] Adaugă site-ul în GSC
   - [ ] Verifică proprietatea (HTML tag / DNS / Google Analytics)
   - [ ] Trimite sitemap.xml
   - [ ] Verifică erori de indexare
   - [ ] Verifică Mobile Usability
   - [ ] Verifică Core Web Vitals

2. **Teste SEO**
   - [ ] Verifică robots.txt: `https://brodero.online/robots.txt`
   - [ ] Verifică sitemap.xml: `https://brodero.online/sitemap.xml`
   - [ ] Test Mobile-Friendly: [Google Mobile-Friendly Test](https://search.google.com/test/mobile-friendly)
   - [ ] Test Rich Results: [Rich Results Test](https://search.google.com/test/rich-results)
   - [ ] PageSpeed Insights: [PageSpeed](https://pagespeed.web.dev/)

3. **Meta Tags pe Pagini Principale**
   - [ ] Homepage - verifică title, description, H1
   - [ ] Magazin - verifică meta tags
   - [ ] Pagini Produs - verifică title unic, description, schema
   - [ ] Categorii - verifică meta tags
   - [ ] Contact - verifică meta tags

4. **Verificare Link-uri**
   - [ ] Nu există link-uri sparte (404)
   - [ ] Toate imaginile au atribut `alt`
   - [ ] Link-uri interne funcționează
   - [ ] Link-uri externe au `rel="noopener"` unde e cazul

5. **Indexability**
   - [ ] Nu există `<meta name="robots" content="noindex">` pe pagini publice
   - [ ] Paginile admin/login au `noindex, nofollow`
   - [ ] 404 pages nu sunt indexabile

---

## 🎯 Next Steps (Post-MVP)

### SEO Avansat:
1. **Product Schema** pentru fiecare produs
   ```json
   {
     "@type": "Product",
     "name": "Nume produs",
     "description": "...",
     "image": "...",
     "offers": {
       "@type": "Offer",
       "price": "99.00",
       "priceCurrency": "RON"
     }
   }
   ```

2. **BreadcrumbList Schema**
3. **FAQ Schema** (dacă există pagină FAQ)
4. **Hreflang tags** (dacă vei avea multi-language)
5. **AMP pages** (opțional)

### Conținut SEO:
1. Blog pentru conținut fresh
2. Landing pages pentru keywords specifice
3. Internal linking strategy
4. Content freshness (update produse)

### Technical SEO:
1. SSL/HTTPS (OBLIGATORIU pentru producție)
2. CDN pentru assets
3. Image optimization (WebP, lazy loading)
4. Core Web Vitals optimization
5. URL rewriting (clean URLs fără .php)

---

## 📞 Cum să testezi

### 1. Verifică robots.txt
```bash
curl https://brodero.online/robots.txt
```

### 2. Verifică sitemap.xml
```bash
curl https://brodero.online/sitemap.xml
```

### 3. Testează Meta Tags
- Deschide source code (Ctrl+U în browser)
- Caută după `<title>`, `<meta name="description">`, `<link rel="canonical">`

### 4. Test Mobile
- Chrome DevTools → Toggle device toolbar (Ctrl+Shift+M)
- Testează navigarea pe diferite rezoluții

### 5. Simulare Google Bot
```bash
curl -A "Googlebot" https://brodero.online
```

---

## ✅ Criterii Acceptanță MVP SEO

Site-ul este **SEO-ready** când:

- ✅ robots.txt permite crawling
- ✅ sitemap.xml este valid și complet
- ✅ Toate paginile publice au title + description unice
- ✅ HTML este semantic (header, main, footer, nav)
- ✅ URL-urile sunt curate (slug-based)
- ✅ Canonical tags sunt prezente
- ✅ Open Graph tags sunt complete
- ✅ Nu există noindex pe pagini publice
- ✅ Site-ul este mobile-friendly
- ✅ Site-ul poate fi adăugat în Search Console fără erori
- ✅ Heading hierarchy este corectă (un singur H1 per pagină)

---

## 📅 Deployment Checklist

Înainte de a merge LIVE:

1. ✅ Încarcă toate fișierele noi:
   - `robots.txt`
   - `sitemap.xml.php`
   - `.htaccess` (actualizat)
   - `includes/header.php` (actualizat)

2. ✅ Verifică că URL-ul din `config.php` este corect:
   ```php
   define('SITE_URL', 'https://brodero.online');
   ```

3. ✅ Testează în browser:
   - https://brodero.online/robots.txt
   - https://brodero.online/sitemap.xml

4. ✅ Adaugă site-ul în Google Search Console

5. ✅ Trimite sitemap-ul în GSC

6. ✅ Monitorizează erori în GSC după 48-72 ore

---

**Data implementării**: 7 ianuarie 2026  
**Status**: ✅ MVP SEO Complete - Ready for Indexing

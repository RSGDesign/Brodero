# Image Optimization Guide

## Probleme identificate din PageSpeed Insights
- **11.256 KiB** economii posibile prin optimizarea imaginilor
- Imaginile trebuie servite în formate moderne (WebP, AVIF)
- Dimensiunile imaginilor trebuie să corespundă cu afișarea

## Soluții implementate

### 1. Lazy Loading
✅ Adăugat `loading="lazy"` pe toate imaginile (except hero images)
✅ Adăugat `width` și `height` pentru a preveni CLS (Cumulative Layout Shift)
✅ Implementat JavaScript IntersectionObserver pentru compatibilitate

### 2. Prioritizare imagini critice
✅ Hero image pe index.php are `fetchpriority="high"` (fără lazy loading)
✅ Imaginea principală pe produs.php are `content-visibility: auto`

### 3. Dimensiuni fixe
✅ width="400" height="300" pentru product cards
✅ width="600" height="450" pentru hero și main product image
✅ width="100" height="100" pentru thumbnails

## Optimizări recomandate pentru viitor

### Convertire WebP (economii ~70% din mărimea fișierului)
```bash
# Instalează imagick sau cwebp
# Pentru toate imaginile din uploads:
for file in uploads/*.jpg; do
    cwebp -q 80 "$file" -o "${file%.jpg}.webp"
done
```

### HTML cu fallback WebP:
```php
<picture>
    <source type="image/webp" srcset="image.webp">
    <img src="image.jpg" alt="" loading="lazy" width="400" height="300">
</picture>
```

### Responsive images cu srcset:
```php
<img 
    src="image-400.jpg"
    srcset="image-400.jpg 400w, image-800.jpg 800w, image-1200.jpg 1200w"
    sizes="(max-width: 768px) 400px, (max-width: 1200px) 800px, 1200px"
    alt=""
    loading="lazy">
```

## Rezultate așteptate
- **LCP improvement**: 16.7s → ~4-5s (cu optimizare imagini)
- **CLS improvement**: Prevenit prin width/height
- **Data savings**: ~11KB reducere + lazy loading = ~50-70% mai puțină bandă

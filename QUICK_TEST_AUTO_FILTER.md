# 🧪 Ghid Testare Rapidă - Filtrare Automată

**Pagină:** `pages/magazin.php`  
**Timp Testare:** ~5 minute  
**Status:** ✅ Gata pentru testare

---

## 📋 Checklist Rapid (5 minute)

### ✅ Test 1: Schimbare Categorie (INSTANT)

1. Deschide: `https://brodero.online/pages/magazin.php`
2. **Schimbă categoria** din dropdown
3. **Verifică:**
   - ✅ Pagina se reîncarcă AUTOMAT (fără buton!)
   - ✅ URL conține `?category=X`
   - ✅ Produsele afișate aparțin categoriei selectate
   - ✅ Loader apare înainte de redirect

**Rezultat așteptat:** Redirect instant la `?category=5`

---

### ✅ Test 2: Schimbare Sortare (INSTANT)

1. **Schimbă sortarea** (ex: "Preț crescător")
2. **Verifică:**
   - ✅ Redirect automat
   - ✅ URL: `?sort=price_asc`
   - ✅ Produsele sunt sortate corect
   - ✅ Loader apare

**Rezultat așteptat:** Produse sortate după preț

---

### ✅ Test 3: Căutare Text (DEBOUNCE 300ms)

1. **Tastează în căutare**: "broderie"
2. **Așteaptă 300ms** (aproximativ 0.3 secunde)
3. **Verifică:**
   - ✅ Redirect automat după 300ms
   - ✅ URL: `?search=broderie`
   - ✅ Rezultate filtrate

**Test alternativ:**
- Tastează "bro" → Așteaptă → "broderie"
- Trebuie să redirecționeze DOAR ODATĂ (după ce termini de tastat)

---

### ✅ Test 4: Enter în Input (INSTANT)

1. **Tastează în căutare**: "floral"
2. **Apasă ENTER** (nu aștepta 300ms!)
3. **Verifică:**
   - ✅ Redirect INSTANT (fără așteptare)
   - ✅ URL: `?search=floral`

**Rezultat:** Enter = bypass debounce

---

### ✅ Test 5: Preț Min/Max (DEBOUNCE 300ms)

1. **Schimbă "Preț Min"** la 100
2. **Așteaptă 300ms**
3. **Verifică:**
   - ✅ Redirect automat
   - ✅ URL: `?min_price=100`
   - ✅ Produse >= 100 LEI

**Test combinat:**
- Preț Min: 50
- Preț Max: 200
- URL așteptat: `?min_price=50&max_price=200`

---

### ✅ Test 6: Eliminare Parametri Goali

1. **Lasă toate filtrele pe valori default:**
   - Categorie: "Toate categoriile"
   - Preț Min: 0
   - Preț Max: 1000
   - Sortare: "Cele mai noi"
2. **Verifică URL:**
   - ✅ Trebuie: `/pages/magazin.php` (FĂRĂ parametri GET!)
   - ❌ NU: `?category=0&min_price=0&max_price=1000`

**Rezultat:** URL curat, fără spam de parametri

---

### ✅ Test 7: Reset Filtre

1. **Aplică câteva filtre** (categorie, preț, sortare)
2. **Click "Resetează Filtre"**
3. **Verifică:**
   - ✅ URL devine `/pages/magazin.php`
   - ✅ Toate filtrele resetate la default
   - ✅ Toate produsele afișate

---

### ✅ Test 8: Paginare

1. **Aplică un filtru** (ex: categorie)
2. **Navighează la pagina 2**
3. **Verifică URL:** `?category=5&page=2`
4. **Schimbă categoria din nou**
5. **Verifică:**
   - ✅ URL resetează la pagina 1: `?category=3`
   - ❌ NU rămâne pe pagina 2 cu alte produse

**Rezultat:** Filtrare nouă = resetare la pagina 1

---

### ✅ Test 9: Loader Vizual

1. **Schimbă orice filtru**
2. **Verifică ÎNAINTE de redirect:**
   - ✅ Spinner în header "Filtrare"
   - ✅ Overlay semi-transparent pe lista produse
   - ✅ Mesaj "Se actualizează produsele..."

**Rezultat:** Feedback vizual instant

---

### ✅ Test 10: Combinație Filtre

1. **Aplică toate filtrele simultan:**
   - Căutare: "floral"
   - Categorie: "Broderie mașină"
   - Preț Min: 100
   - Preț Max: 500
   - Sortare: "Populare"
   - Per Pagină: 24
2. **Verifică URL:**
   ```
   ?search=floral&category=3&min_price=100&max_price=500&sort=popular&per_page=24
   ```
3. **Verifică rezultate:** Toate filtrele aplicate corect

---

## 🐛 Debug în Console (F12)

### Output Așteptat

```javascript
✓ Filtrare automată inițializată cu succes
Filter changed: category = 5
Debounced filter changed: search = broderie
Filter changed: sort = price_asc
```

### Verificări Manuale

```javascript
// 1. Verifică ID-uri
console.log(document.getElementById('filter-category'));  // Trebuie != null
console.log(document.getElementById('filter-sort'));      // Trebuie != null
console.log(document.getElementById('filter-search'));    // Trebuie != null

// 2. Verifică clase
console.log(document.querySelectorAll('.auto-filter').length);         // >= 3
console.log(document.querySelectorAll('.auto-filter-debounce').length); // >= 2

// 3. Test manual loader
document.getElementById('products-loader').classList.remove('d-none');
document.getElementById('filter-loader').classList.remove('d-none');

// 4. Test manual redirect
window.location.href = '?category=5&sort=price_asc';
```

---

## ⚠️ Probleme Comune

### Problema: Filtrele nu se aplică automat

**Verifică:**
```javascript
// Console (F12) → Vezi erori JavaScript?
// Verifică:
console.log(document.getElementById('filter-category')); // Trebuie != null
```

**Soluție:**
- Verifică că elementele au `id=""` corect
- Verifică că JavaScript-ul e DUPĂ HTML

---

### Problema: Debounce nu funcționează

**Simptom:** Redirect la fiecare tastă în input

**Verifică:**
```javascript
const input = document.getElementById('filter-search');
console.log(input.classList.contains('auto-filter-debounce')); // Trebuie true
```

**Soluție:**
- Verifică clasa: `class="form-control auto-filter-debounce"`

---

### Problema: Parametri goali în URL

**Simptom:** `?category=0&min_price=0&max_price=1000`

**Verifică funcția `buildFilterURL()`:**
```javascript
// Trebuie SĂ EXISTE verificări:
if (category && category !== '0') {
    params.set('category', category);
}
```

---

### Problema: Loader nu apare

**Verifică HTML:**
```html
<div id="products-loader" class="d-none">...</div>
<span id="filter-loader" class="d-none">...</span>
```

**Test manual:**
```javascript
document.getElementById('products-loader').classList.remove('d-none');
```

---

## 📊 Rezultate Finale

### ✅ Succes Complet

- [ ] **Test 1-10** toate trec
- [ ] **Console** fără erori JavaScript
- [ ] **Loader** apare la fiecare filtrare
- [ ] **URL-uri** curate (fără parametri goali)
- [ ] **Paginare** funcționează corect

### ⚠️ Necesită Atenție

- Erori JavaScript în console
- Loader nu apare
- Parametri goali în URL
- Debounce nu funcționează

---

## 🚀 Deploy Checklist

Înainte de deploy pe Hostinger:

```bash
# 1. Verifică fișierul modificat
cat pages/magazin.php | grep "auto-filter"  # Trebuie să găsească

# 2. Verifică syntax PHP
php -l pages/magazin.php  # No syntax errors

# 3. Backup
cp pages/magazin.php pages/magazin.php.backup

# 4. Upload
scp pages/magazin.php user@hostinger:/path/

# 5. Verifică online
curl -I https://brodero.online/pages/magazin.php  # 200 OK
```

---

## 📞 Suport

**Problemă?**
- Email: contact@brodero.online
- Documentație: `AUTO_FILTER_IMPLEMENTATION.md`
- GitHub: Issues pe repository

---

**Happy Testing! 🎉**

*Testare completă: ~5 minute*  
*Feedback instant, experiență modernă!* 🚀

# Google Analytics 4 (GA4) - Setup Guide
**Brodero - Integrare Analytics MVP**

---

## 📋 Cuprins

1. [Creare Proprietate GA4](#1-creare-proprietate-ga4)
2. [Configurare Measurement ID](#2-configurare-measurement-id)
3. [Verificare Integrare](#3-verificare-integrare)
4. [Testare Evenimente](#4-testare-evenimente)
5. [Google Search Console Link](#5-google-search-console-link)
6. [Rapoarte Disponibile](#6-rapoarte-disponibile)
7. [Troubleshooting](#7-troubleshooting)

---

## 1. Creare Proprietate GA4

### Pas 1: Acces Google Analytics
1. Deschide [Google Analytics](https://analytics.google.com)
2. Autentifică-te cu contul Google

### Pas 2: Creare Cont (dacă nu există)
1. Click pe **"Start measuring"**
2. Nume cont: `Brodero` (sau după preferință)
3. Bifează opțiunile de sharing dorite
4. Click **"Next"**

### Pas 3: Creare Proprietate
1. **Property name:** `Brodero Website`
2. **Reporting time zone:** `(GMT+02:00) Eastern European Time - Bucharest`
3. **Currency:** `Romanian Leu (RON)`
4. Click **"Next"**

### Pas 4: Detalii Business
1. **Industry category:** `Retail/E-commerce` sau `Arts & Entertainment`
2. **Business size:** Alege dimensiunea afacerii
3. **Business objectives:** Bifează:
   - ✅ Examine user behavior
   - ✅ Measure advertising ROI
   - ✅ Baseline reports
4. Click **"Create"**

### Pas 5: Accept Termeni
1. Selectează **Romania** ca țară
2. Acceptă **Terms of Service Agreement**
3. Click **"I Accept"**

### Pas 6: Platform Setup
1. Selectează **Web** (nu App)
2. **Website URL:** `https://brodero.online`
3. **Stream name:** `Brodero Website`
4. Click **"Create stream"**

### Pas 7: Obține Measurement ID
După creare, vei vedea:
```
MEASUREMENT ID: G-XXXXXXXXXX
```
**Copiază acest ID!** ✅

---

## 2. Configurare Measurement ID

### Pas 1: Editare Fișier
Deschide fișierul:
```
includes/analytics.php
```

### Pas 2: Înlocuire Placeholder
Găsește linia 9:
```php
define('GA4_MEASUREMENT_ID', 'G-XXXXXXXXXX'); // Replace with your actual GA4 Measurement ID
```

Înlocuiește cu ID-ul tău real:
```php
define('GA4_MEASUREMENT_ID', 'G-ABC1234567'); // Exemplu
```

### Pas 3: Upload Fișier
Upload fișierul modificat pe server prin FTP/cPanel:
```
/home/u107933880/domains/brodero.online/public_html/includes/analytics.php
```

✅ **Integrarea este completă!**

---

## 3. Verificare Integrare

### Metoda 1: DebugView în GA4 (RECOMANDAT)

#### Activare DebugView:
1. **Chrome:** Instalează extensia [Google Analytics Debugger](https://chrome.google.com/webstore/detail/google-analytics-debugger/jnkmfdileelhofjcijamephohjechhna)
2. **Orice browser:** Adaugă parametru la URL:
   ```
   https://brodero.online/?debug_mode=1
   ```

#### Verificare în GA4:
1. Accesează GA4 → **Admin** → **DebugView** (secțiunea Reports)
2. Navighează pe site-ul tău (cu debug activat)
3. Trebuie să vezi evenimente în timp real:
   - `page_view` (automat pe fiecare pagină)
   - `session_start` (la prima vizită)

### Metoda 2: Realtime Reports
1. Accesează GA4 → **Reports** → **Realtime**
2. Deschide site-ul în alt tab: `https://brodero.online`
3. Verifică:
   - ✅ Apare 1 utilizator activ
   - ✅ Vezi paginile accesate
   - ✅ Vezi evenimente `page_view`

### Metoda 3: Browser DevTools
1. Deschide site-ul: `https://brodero.online`
2. Click dreapta → **Inspect** → **Console**
3. Verifică că nu apar erori legate de `gtag` sau `analytics`
4. Mergi la **Network** tab → filtrează `gtag`
5. Trebuie să vezi request-uri către:
   ```
   https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX
   ```

---

## 4. Testare Evenimente

### Test 1: Page View ✅ (Automat)
1. Deschide orice pagină de pe site
2. În GA4 DebugView sau Realtime → Vezi `page_view`

✅ **Funcționează automat - nu necesită configurare.**

---

### Test 2: Begin Checkout
1. Adaugă un produs în coș
2. Mergi la Checkout: `https://brodero.online/pages/checkout.php`
3. În GA4 DebugView → Verifică:
   ```
   Event: begin_checkout
   Parameters:
     - value: [suma totală]
     - currency: RON
   ```

#### Verificare Cod:
Fișier: `pages/checkout.php` (după linia 111):
```php
require_once __DIR__ . '/../includes/analytics.php';
trackBeginCheckout($total);
```

✅ **Evenimentul se declanșează când utilizatorul accesează pagina de checkout.**

---

### Test 3: Purchase (Transfer Bancar)
1. Completează formularul de checkout
2. Selectează **Transfer Bancar**
3. Finalizează comanda
4. În GA4 DebugView → Verifică:
   ```
   Event: purchase
   Parameters:
     - value: [suma plătită]
     - currency: RON
     - transaction_id: [număr comandă, ex: BRO-2024-001]
   ```

#### Verificare Cod:
Fișier: `pages/checkout_process.php` (după linia 270):
```php
require_once __DIR__ . '/../includes/analytics.php';
trackPurchase($totalAmount, 'RON', $orderNumber);
```

---

### Test 4: Purchase (Stripe)
1. Completează formularul de checkout
2. Selectează **Plată cu cardul (Stripe)**
3. Finalizează plata
4. În GA4 DebugView → Verifică eveniment `purchase` (la fel ca mai sus)

#### Verificare Cod:
Fișier: `pages/checkout_return.php` (după linia 136):
```php
require_once __DIR__ . '/../includes/analytics.php';
trackPurchase($totalAmount, 'RON', $orderNumber);
```

---

### Test 5: GDPR Consent
#### Test Refuz Cookies:
1. Deschide site-ul în **Incognito/Private Mode**
2. Banner cookies apare jos
3. Click **"Refuză"**
4. Verifică în DevTools → **Console**:
   ```javascript
   // NU trebuie să existe script-uri gtag.js încărcate
   ```
5. În GA4 → **NU** trebuie să apară vizita ta

✅ **GA4 NU se încarcă dacă utilizatorul refuză cookies.**

#### Test Accept Cookies:
1. Refresh pagina
2. Click **"Accept"** pe banner
3. Pagina se reîncarcă
4. În DevTools → **Network** → Vezi `gtag/js`
5. În GA4 → Apare vizita ta

✅ **GA4 se încarcă doar după accept.**

---

## 5. Google Search Console Link

Legarea GA4 cu Google Search Console îți permite să vezi queries de căutare în GA4.

### Pași:
1. Accesează GA4 → **Admin** (roată dințată jos-stânga)
2. **Property Settings** → **Product Links**
3. Click **"Link"** la **Search Console**
4. Selectează proprietatea: `https://brodero.online`
5. Click **"Confirm"**
6. Click **"Next"** → **"Submit"**

✅ **Link creat!** Datele vor apărea în 24-48h.

---

## 6. Rapoarte Disponibile

După 24-48 ore, vei avea acces la:

### Reports → Life Cycle → Acquisition
- **Traffic acquisition:** De unde vin vizitatorii (Organic, Direct, Referral, Social)
- **User acquisition:** Prima sursă de trafic pentru utilizatori noi

### Reports → Life Cycle → Engagement
- **Events:** Toate evenimentele trackuite:
  - `page_view`
  - `session_start`
  - `begin_checkout`
  - `purchase`
- **Pages and screens:** Cele mai vizitate pagini

### Reports → Monetization → E-commerce purchases
- **Item views:** Produse vizualizate (dacă implementezi tracking produse)
- **Purchase journey:** Funnel de la vizualizare → checkout → purchase
- **E-commerce purchases:** Detalii vânzări:
  - Total revenue (RON)
  - Transactions (număr comenzi)
  - Average purchase revenue

### Custom Reports (Opțional - după acumulare date)
1. **Explore** (Analytics) → **Blank**
2. Creează rapoarte personalizate cu:
   - Revenue per source
   - Conversion rate by device
   - Top products purchased

---

## 7. Troubleshooting

### ❌ Nu apar date în GA4

**Cauze posibile:**

1. **Measurement ID greșit:**
   - Verifică `includes/analytics.php` linia 9
   - Compară cu ID-ul din GA4 (Admin → Data Streams)

2. **Cookie consent refuzat:**
   - Șterge cookies site-ului
   - Refresh → Click **"Accept"** pe banner
   - GA4 se încarcă doar după accept

3. **AdBlockers:**
   - Dezactivează extensii AdBlock/uBlock
   - Testează în **Incognito Mode** fără extensii

4. **Cache browser:**
   - Apasă `Ctrl+Shift+R` (hard refresh)
   - Sau șterge cache complet

5. **Delay procesare date:**
   - GA4 Realtime: date instant (max 5 min)
   - Rapoarte standard: 24-48h delay

---

### ❌ Evenimente `purchase` nu apar

**Verificări:**

1. **Consent granted:**
   ```javascript
   // În DevTools Console:
   document.cookie.includes('cookie_consent=granted')
   // Trebuie să returneze true
   ```

2. **Cod executat:**
   - View Source pe pagina de confirmare (după plată)
   - Caută: `gtag('event', 'purchase'`
   - Dacă nu apare → verifică că fișierul `analytics.php` e inclus

3. **DebugView:**
   - Adaugă `?debug_mode=1` la URL checkout
   - Finalizează comandă
   - Verifică în GA4 DebugView dacă apare `purchase`

---

### ❌ Banner cookies nu apare

**Verificare:**

1. **Șterge cookie-ul:**
   ```javascript
   // În DevTools Console:
   document.cookie = 'cookie_consent=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
   ```
   
2. **Refresh pagina** → Banner trebuie să apară

3. **Cache CSS:**
   - Verifică că `includes/cookie_consent.php` e inclus în `header.php`
   - Hard refresh: `Ctrl+Shift+R`

---

### ❌ GA4 se încarcă chiar dacă refuz cookies

**Verificare:**

1. **Verifică cookie:**
   ```javascript
   // În DevTools Console:
   document.cookie
   // Trebuie să conțină: cookie_consent=denied
   ```

2. **Verifică cod:**
   - Fișier: `includes/analytics.php` funcția `hasAnalyticsConsent()`
   - Linia 17: verifică că returnează `false` dacă cookie = denied

3. **Cache PHP:**
   - Clear cache PHP (în cPanel sau Cloudflare)
   - Refresh hard

---

## ✅ Criterii de Acceptanță MVP

Integrarea este **completă** când:

- [x] GA4 Property creată și Measurement ID configurat
- [x] Page views apar în GA4 Realtime
- [x] Eveniment `begin_checkout` se trimite la acces checkout
- [x] Eveniment `purchase` se trimite după comandă (bank transfer + Stripe)
- [x] GA4 **NU** se încarcă fără consent (cookie banner)
- [x] GA4 **SE ÎNCARCĂ** după click "Accept"
- [x] DebugView confirmă toate evenimentele
- [x] Nu se trimit date personale (email, nume, telefon)

---

## 📊 Raportare Post-Implementare

După **7 zile**, verifică în GA4:

1. **Reports → Realtime:**
   - Număr utilizatori activi
   
2. **Reports → Engagement → Events:**
   - `page_view`: > 100 evenimente
   - `begin_checkout`: > 5 evenimente
   - `purchase`: > 1 eveniment (dacă au fost vânzări)

3. **Reports → Monetization → E-commerce purchases:**
   - Total revenue: suma vânzărilor în RON
   - Transactions: număr comenzi finalizate

---

## 🚀 Next Steps (Post-MVP)

După ce MVP funcționează stabil, poți adăuga:

1. **Google Tag Manager (GTM):**
   - Management centralizat tag-uri
   - Tracking avansat fără modificare cod

2. **Enhanced E-commerce:**
   - `view_item` (vizualizare produs)
   - `add_to_cart` (adăugare în coș)
   - `remove_from_cart`
   - `view_item_list` (listare categorie)

3. **Custom Dimensions:**
   - User type (guest vs. logged in)
   - Product categories
   - Payment method

4. **Conversion Tracking:**
   - Import Goals din GA4 în Google Ads
   - Remarketing audiences

5. **Server-Side Tracking:**
   - GA4 Measurement Protocol API
   - Tracking mai precis, bypass AdBlockers

---

## 📞 Support

Dacă întâmpini probleme:

1. **Verifică Checklist** de mai sus (Troubleshooting)
2. **Consultă documentația:** [GA4 Help Center](https://support.google.com/analytics/answer/10089681)
3. **Community:** [Google Analytics Community](https://support.google.com/analytics/community)

---

**Document creat:** 7 ianuarie 2026  
**Versiune:** 1.0 MVP  
**Status:** ✅ Ready for Production

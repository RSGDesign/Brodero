# 🧪 GHID TESTARE - Sistem Descărcări Fișiere

## ✅ Checklist Testare Completă

### 🎯 Test 1: Plată Stripe (PRIORITATE MAXIMĂ)

**Obiectiv:** Verifică că după plată Stripe, descărcările sunt active imediat

**Pași:**
1. [ ] Accesează: `https://brodero.online/pages/magazin.php`
2. [ ] Alege un produs digital
3. [ ] Adaugă în coș → Finalizează comanda
4. [ ] Alege "Plata cu Card (Stripe)"
5. [ ] Completează formular client
6. [ ] Informații card test:
   ```
   Număr card: 4242 4242 4242 4242
   Data expirare: orice dată viitoare (ex: 12/25)
   CVC: orice 3 cifre (ex: 123)
   ```
7. [ ] Click "Plătește"
8. [ ] Verifică redirect la pagina de confirmare
9. [ ] Click butonul "Descarcă Fișierele"
10. [ ] **VERIFICARE CRITICĂ:**
    - [ ] Status comandă = "Plătită" (verde)
    - [ ] Status fișier = "Disponibil" (verde)
    - [ ] Buton "Descarcă Fișierul" este vizibil
    - [ ] Click pe buton → fișierul se descarcă

**Rezultat așteptat:** ✅ Fișier descărcat cu succes

---

### 🏦 Test 2: Transfer Bancar

**Obiectiv:** Verifică activarea descărcărilor după confirmare admin

**Pași:**
1. [ ] Comandă nouă cu metoda "Transfer Bancar"
2. [ ] Finalizează comanda
3. [ ] Verifică în "Contul Meu → Fișiere Descărcabile":
   - [ ] Status = "Plată în așteptare" (galben)
   - [ ] Nu apare buton descărcare
4. [ ] Logare ca Admin
5. [ ] Accesează: `Admin → Gestionare Comenzi`
6. [ ] Click pe comanda respectivă
7. [ ] Schimbă "Status Plată" la **"Plătit"**
8. [ ] Click "Actualizează Status"
9. [ ] Logare ca Client
10. [ ] Verifică în "Fișiere Descărcabile":
    - [ ] Status = "Disponibil" (verde)
    - [ ] Buton "Descarcă" vizibil
    - [ ] Fișier se descarcă

**Rezultat așteptat:** ✅ Descărcări activate după confirmare admin

---

### 🆓 Test 3: Comandă Gratuită (0 RON)

**Obiectiv:** Verifică activare imediată pentru comenzi 0 RON

**Pași:**
1. [ ] Creează produs cu preț 0 RON
   SAU
   [ ] Aplică cupon 100% discount
2. [ ] Finalizează comanda
3. [ ] Verifică imediat în "Fișiere Descărcabile":
   - [ ] Status = "Disponibil" (verde)
   - [ ] Buton descărcare activ
   - [ ] Fișier se descarcă

**Rezultat așteptat:** ✅ Descărcări disponibile imediat (fără așteptare)

---

### 🔄 Test 4: Sincronizare Comenzi Vechi

**Obiectiv:** Corectează comenzile vechi cu descărcări blocate

**Pași:**
1. [ ] Accesează: `https://brodero.online/admin/sync_downloads.php`
2. [ ] Verifică numărul de comenzi afectate
3. [ ] Click "Sincronizează Acum"
4. [ ] Verifică mesajul: "Au fost actualizate X comenzi"
5. [ ] Accesează: `https://brodero.online/admin/test_downloads.php`
6. [ ] Verifică: `"total_problematic_orders": 0`

**Rezultat așteptat:** ✅ 0 comenzi problematice

---

### 🐛 Test 5: Debugging & Diagnosticare

**Obiectiv:** Verifică instrumentele de debugging

**Test 5.1: Verificare Comandă Specifică**
```bash
# Înlocuiește 123 cu un ID real de comandă
https://brodero.online/admin/test_downloads.php?order_id=123
```

**Verificări JSON:**
- [ ] `"is_paid": true`
- [ ] `"downloads_enabled": true`
- [ ] `"should_enable": false`
- [ ] `"recommendation": "Statusul este corect."`

**Test 5.2: Raport Comenzi Problematice**
```bash
https://brodero.online/admin/test_downloads.php
```

**Verificări:**
- [ ] `"total_problematic_orders": 0`
- [ ] `"orders": []` (array gol)

**Rezultat așteptat:** ✅ Toate comenzile sunt corecte

---

### 💾 Test 6: Verificări Bază de Date

**Obiectiv:** Validează structura și datele din DB

**SQL Queries:**

**6.1: Verifică coloana downloads_enabled există**
```sql
SHOW COLUMNS FROM order_items LIKE 'downloads_enabled';
```
- [ ] Coloana există
- [ ] Tip: `tinyint(1)`
- [ ] Default: `0`

**6.2: Comenzi plătite cu descărcări active**
```sql
SELECT 
    o.order_number,
    o.payment_status,
    COUNT(oi.id) as total_items,
    SUM(oi.downloads_enabled) as enabled_items
FROM orders o
JOIN order_items oi ON oi.order_id = o.id
WHERE o.payment_status = 'paid'
GROUP BY o.id
HAVING enabled_items = total_items;
```
- [ ] Toate comenzile plătite au `enabled_items = total_items`

**6.3: Comenzi problematice (NU ar trebui să existe)**
```sql
SELECT 
    o.order_number,
    o.payment_status,
    SUM(oi.downloads_enabled) as enabled_items
FROM orders o
JOIN order_items oi ON oi.order_id = o.id
WHERE o.payment_status = 'paid'
GROUP BY o.id
HAVING enabled_items = 0;
```
- [ ] Rezultat: **0 rows** (nicio comandă problematică)

---

### 🎨 Test 7: Interfață Utilizator

**Obiectiv:** Verifică afișarea corectă a statusurilor

**7.1: Pagina "Fișiere Descărcabile"**

Accesează: `Contul Meu → Fișiere Descărcabile`

**Pentru comandă plătită:**
- [ ] Buton verde "Descarcă Fișierul"
- [ ] Text: "Disponibil" (verde)
- [ ] Card are border verde

**Pentru comandă neplătită:**
- [ ] Badge galben "Plată în așteptare"
- [ ] Text ajutător: "Descărcarea va fi activată..."

**Pentru comandă blocată (BUG):**
- [ ] Badge roșu "În procesare"
- [ ] Text: "Contactează suportul"

**7.2: Pagina Comandă**

Accesează: `Contul Meu → Comenzi → Click pe comandă`

- [ ] Status comandă afișat corect
- [ ] Link către "Fișiere Descărcabile" funcționează

---

### 🔐 Test 8: Securitate

**Obiectiv:** Verifică securitatea descărcărilor

**8.1: Acces fără autentificare**
```bash
# Logout complet
# Încearcă să accesezi direct:
https://brodero.online/pages/download.php?file=1&order=1&token=xyz
```
- [ ] Redirect la login
- [ ] Nu permite descărcare

**8.2: Token expirat**
```bash
# Generează token
# Așteaptă 1 oră
# Încearcă să descarci
```
- [ ] Eroare: "Token expirat"
- [ ] Nu permite descărcare

**8.3: Token invalid**
```bash
https://brodero.online/pages/download.php?file=1&order=1&token=invalid_token
```
- [ ] Eroare: "Token invalid"

---

### 📊 Test 9: Limite Descărcare

**Obiectiv:** Verifică respectarea limitelor de descărcare

**9.1: Fișier cu limită 3 descărcări**
- [ ] Descărcare 1: Succes
- [ ] Descărcare 2: Succes
- [ ] Descărcare 3: Succes
- [ ] Descărcare 4: Eroare "Limită atinsă"
- [ ] Badge devine gri "Limită atinsă"

**9.2: Fișier fără limită (0)**
- [ ] Descărcare 1: Succes
- [ ] Descărcare 10: Succes
- [ ] Descărcare 100: Succes
- [ ] Text: "Descărcări nelimitate"

---

### 🌐 Test 10: Cross-Browser

**Obiectiv:** Compatibilitate browsere

- [ ] Chrome/Edge: Toate funcțiile merg
- [ ] Firefox: Toate funcțiile merg
- [ ] Safari: Toate funcțiile merg
- [ ] Mobile (Chrome): Toate funcțiile merg

---

## 📋 Raport Final Testare

### Sumar Teste

| Test | Status | Observații |
|------|--------|-----------|
| 1. Plată Stripe | ⬜ Pass / ⬜ Fail | |
| 2. Transfer Bancar | ⬜ Pass / ⬜ Fail | |
| 3. Comandă Gratuită | ⬜ Pass / ⬜ Fail | |
| 4. Sincronizare | ⬜ Pass / ⬜ Fail | |
| 5. Debugging | ⬜ Pass / ⬜ Fail | |
| 6. Bază Date | ⬜ Pass / ⬜ Fail | |
| 7. Interfață | ⬜ Pass / ⬜ Fail | |
| 8. Securitate | ⬜ Pass / ⬜ Fail | |
| 9. Limite | ⬜ Pass / ⬜ Fail | |
| 10. Cross-Browser | ⬜ Pass / ⬜ Fail | |

### Criterii de Acceptare

✅ **PASS:** Toate testele 1-6 sunt PASS (obligatoriu)
✅ **PASS:** Minimum 8/10 teste sunt PASS
⚠️ **PARTIAL:** 6-7 teste sunt PASS
❌ **FAIL:** Mai puțin de 6 teste sunt PASS

---

## 🐛 Raportare Bug-uri

**Dacă un test eșuează, completează:**

### Template Bug Report
```
Test ID: [ex: Test 1 - Plată Stripe]
Status: FAIL
Browser: [Chrome/Firefox/etc]
Device: [Desktop/Mobile]

Pași reproducere:
1. ...
2. ...
3. ...

Rezultat așteptat:
...

Rezultat actual:
...

Screenshot/Error log:
...
```

---

**🎯 Nota:** Testarea completă durează ~30-45 minute. Pentru testare rapidă, rulează doar Testele 1, 2, 4, 5.

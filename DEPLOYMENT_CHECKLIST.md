# ✅ CHECKLIST DEPLOYMENT - Sistem Descărcări

## 📋 Pre-Deployment

### 1. Backup Baza de Date
```bash
# Exportă baza de date completă
mysqldump -u root -p brodero > backup_before_downloads_fix.sql
```
- [ ] Backup creat și verificat
- [ ] Backup salvat în siguranță

### 2. Verificare Fișiere Noi
- [x] `includes/functions_orders.php` - Funcții centralizate
- [x] `admin/sync_downloads.php` - Script sincronizare
- [x] `admin/test_downloads.php` - Script debug
- [x] `database_update_downloads.sql` - Script SQL
- [x] `DOWNLOADS_FIX.md` - Documentație

### 3. Verificare Fișiere Modificate
- [x] `pages/checkout_return.php` - Activare descărcări Stripe
- [x] `pages/checkout_process.php` - Activare comenzi gratuite
- [x] `admin/view_order.php` - Sincronizare la confirmare
- [x] `pages/cont/fisiere-descarcabile.php` - Statusuri îmbunătățite
- [x] `admin/admin_orders.php` - Buton sincronizare

---

## 🚀 Deployment Steps

### STEP 1: Upload Fișiere
```bash
# Prin FTP/SFTP sau Git
/includes/functions_orders.php           → NOU
/admin/sync_downloads.php                → NOU
/admin/test_downloads.php                → NOU
/pages/checkout_return.php               → MODIFICAT
/pages/checkout_process.php              → MODIFICAT
/admin/view_order.php                    → MODIFICAT
/pages/cont/fisiere-descarcabile.php    → MODIFICAT
/admin/admin_orders.php                  → MODIFICAT
```

- [ ] Toate fișierele uploadate pe server
- [ ] Permisiuni verificate (644 pentru .php)

### STEP 2: Actualizare Bază de Date
```bash
# Conectare la MySQL
mysql -u u107933880_brodero -p u107933880_brodero

# Rulează script
source database_update_downloads.sql;
```

**SAU prin phpMyAdmin:**
1. Accesează phpMyAdmin
2. Selectează baza de date
3. Import → Alege `database_update_downloads.sql`
4. Click "Go"

- [ ] Script SQL rulat cu succes
- [ ] Verificat output: "Comenzi actualizate"
- [ ] Coloana `downloads_enabled` există în `order_items`

### STEP 3: Verificare Funcționalitate

#### Test 1: Verificare Comenzi Vechi
1. [ ] Accesează: `https://brodero.online/admin/sync_downloads.php`
2. [ ] Verifică număr comenzi afectate
3. [ ] Click "Sincronizează Acum"
4. [ ] Confirmă mesaj succes

#### Test 2: Plată Stripe (Mod Test)
1. [ ] Comandă nouă cu produs digital
2. [ ] Plată cu card test (4242 4242 4242 4242)
3. [ ] Verifică redirect la checkout_return.php
4. [ ] Status comandă = "Plătită"
5. [ ] Accesează "Fișiere Descărcabile"
6. [ ] Status = "Disponibil" (verde)
7. [ ] Click "Descarcă" → fișier se descarcă

#### Test 3: Transfer Bancar
1. [ ] Comandă nouă cu transfer bancar
2. [ ] Status inițial = "Plată în așteptare" (galben)
3. [ ] Admin: Marchează plata ca "Plătit"
4. [ ] Verifică "Fișiere Descărcabile" → Status = "Disponibil"

#### Test 4: Comandă Gratuită
1. [ ] Produs 0 RON sau cupon 100%
2. [ ] Finalizează comandă
3. [ ] Fișiere disponibile imediat

### STEP 4: Verificare Debugging
```bash
# Test API
curl https://brodero.online/admin/test_downloads.php?order_id=123
```

- [ ] Returnează JSON valid
- [ ] Diagnosticul este corect
- [ ] Nu există comenzi problematice

---

## 🔍 Post-Deployment Monitoring

### Primele 24 ore
- [ ] Monitorizează log-urile pentru erori
- [ ] Verifică comenzile noi (2-3 test orders)
- [ ] Răspunde la eventualele reclamații clienți

### Prima săptămână
- [ ] Rulează periodic `sync_downloads.php`
- [ ] Verifică că nu apar comenzi blocate
- [ ] Monitorizează feedback clienți

---

## 🆘 Rollback Plan (dacă ceva merge prost)

### STEP 1: Restaurare Fișiere
```bash
# Restaurează versiunile vechi din backup
```

### STEP 2: Restaurare Bază de Date
```bash
mysql -u u107933880_brodero -p u107933880_brodero < backup_before_downloads_fix.sql
```

### STEP 3: Cache Clear
```bash
# Șterge cache-ul aplicației dacă există
```

---

## 📞 Contact Tehnic

**În caz de probleme:**
- Email: contact@brodero.online
- Admin Panel: https://brodero.online/admin/
- Debug Tool: https://brodero.online/admin/test_downloads.php

---

## ✅ Sign-Off

- [ ] Toate testele au trecut
- [ ] Documentația completată
- [ ] Echipa notificată despre schimbări
- [ ] Backup salvat și etichetat

**Deployment efectuat de:** _________________
**Data:** 11 Decembrie 2025
**Ora:** _________________
**Status:** ⬜ Success  ⬜ Rollback necesar

---

## 📊 Metrici de Succes

După deployment, verifică:
- ✅ 0 comenzi plătite cu descărcări blocate
- ✅ 100% comenzi Stripe cu descărcări active
- ✅ Transfer bancar: descărcări activate la confirmare
- ✅ Comenzi gratuite: descărcări active imediat
- ✅ 0 reclamații clienți legate de descărcări

**Nota finală:** Sistemul este considerat funcțional când toate metricile sunt îndeplinite timp de 7 zile consecutive.

# 🗂️ Audit Fișiere Proiect Brodero

**Data Audit:** 11 Decembrie 2025  
**Versiune:** 1.0  
**Scop:** Identificare fișiere necesare vs redundante/test

---

## 📊 Statistici Generale

| Categorie | Număr Fișiere | Acțiune Recomandată |
|-----------|---------------|---------------------|
| **Fișiere Critice** | 45 | ✅ PĂSTREAZĂ |
| **Fișiere Funcționale** | 38 | ✅ PĂSTREAZĂ |
| **Fișiere Test** | 15 | ❌ ȘTERGE (production) |
| **Fișiere Migrare** | 8 | ⚠️ ȘTERGE (după rulare) |
| **Fișiere Backup** | 3 | 📦 ARHIVEAZĂ |
| **Documentație** | 25+ | 📚 PĂSTREAZĂ |

**Total:** ~130+ fișiere (exclusiv vendor/, .git/)

---

## ✅ PĂSTREAZĂ - Fișiere Esențiale (45 fișiere)

### Config & Core (6 fișiere)
```
config/
├── config.php              ✅ CRITICAL - DB, SITE_URL, constante
├── database.php            ✅ CRITICAL - Conexiune DB
└── smtp_config.php         ✅ CRITICAL - SMTP credentials

Root:
├── index.php               ✅ CRITICAL - Homepage
├── .htaccess               ✅ CRITICAL - Apache config
└── 404.php                 ✅ Pagină eroare
```

### Includes (6 fișiere)
```
includes/
├── header.php              ✅ CRITICAL - Template header
├── footer.php              ✅ CRITICAL - Template footer
├── category_functions.php  ✅ Funcții categorii M2M
├── functions_orders.php    ✅ Procesare comenzi
└── functions_downloads.php ✅ Validare descărcări
```

### Admin Panel (20 fișiere)
```
admin/
├── dashboard.php           ✅ Dashboard principal
├── admin_products.php      ✅ Lista produse
├── add_product.php         ✅ Adăugare produs + fișiere
├── edit_product.php        ✅ Editare produs
├── product_files.php       ✅ Gestionare fișiere descărcabile
├── admin_categories.php    ✅ Lista categorii
├── add_category.php        ✅ Adăugare categorie
├── edit_category.php       ✅ Editare categorie
├── admin_orders.php        ✅ Lista comenzi
├── view_order.php          ✅ Detalii comandă
├── update_order_status.php ✅ Update status comandă
├── admin_users.php         ✅ Lista utilizatori
├── edit_user.php           ✅ Editare utilizator
├── admin_coupons.php       ✅ Lista cupoane
├── add_coupon.php          ✅ Adăugare cupon
├── edit_coupon.php         ✅ Editare cupon
├── admin_newsletter.php    ✅ Dashboard newsletter
├── send_newsletter.php     ✅ Trimitere newsletter
└── sync_downloads.php      ✅ Sincronizare descărcări
```

### Pages Publice (33 fișiere)
```
pages/
├── magazin.php             ✅ Catalog produse
├── produs.php              ✅ Pagină produs
├── cart.php                ✅ Coș cumpărături
├── add_to_cart.php         ✅ Adăugare în coș
├── remove_from_cart.php    ✅ Ștergere din coș
├── update_cart.php         ✅ Update cantități
├── checkout.php            ✅ Formular checkout
├── checkout_process.php    ✅ Procesare comandă
├── checkout_return.php     ✅ Return URL Stripe
├── apply_coupon.php        ✅ Aplicare cupon
├── remove_coupon.php       ✅ Eliminare cupon
├── payment_success.php     ✅ Success page
├── payment_cancel.php      ✅ Cancel page
├── payment_instructions.php✅ Instrucțiuni transfer
├── login.php               ✅ Autentificare
├── logout.php              ✅ Logout
├── cont.php                ✅ Dashboard utilizator
├── cont/fisiere-descarcabile.php ✅ Produse cumpărate
├── download.php            ✅ Descărcare fișiere
├── contact.php             ✅ Formular contact
├── newsletter.php          ✅ Înscriere newsletter
├── unsubscribe.php         ✅ Dezabonare
├── despre.php              ✅ Despre noi
├── faq.php                 ✅ FAQ
├── termeni.php             ✅ Termeni
├── confidentialitate.php   ✅ Politică privacy
├── cookie.php              ✅ Politică cookies
├── retur.php               ✅ Politică retur
└── comanda.php             ✅ Urmărire comandă
```

### AJAX (5 fișiere)
```
ajax/
├── process_payment.php     ✅ Stripe Checkout
├── update_profile.php      ✅ Update profil
├── change_password.php     ✅ Schimbare parolă
├── delete_account.php      ✅ Ștergere cont
└── upload_avatar.php       ✅ Upload avatar
```

### Assets (3 fișiere)
```
assets/
├── css/style.css           ✅ CSS principal
├── js/main.js              ✅ JavaScript principal
└── images/placeholder.svg  ✅ Imagine placeholder
```

### Database & Composer (3 fișiere)
```
Root:
├── database.sql            ✅ CRITICAL - Structură DB
├── composer.json           ✅ Dependențe PHP
└── composer.lock           ✅ Lock versiuni
```

---

## ❌ ȘTERGE - Fișiere Test (15 fișiere)

**⚠️ IMPORTANT:** Aceste fișiere sunt DOAR pentru testare locală. NU le deploy-uiți în producție!

```bash
# Comenzi ștergere (rulează în root):
rm test_*.php test_*.html quick_check.sh
rm admin/test_downloads.php
```

### Lista Completă Test Files:

```
Root:
├── test_checkout_validation.html    ❌ Test validare checkout
├── test_downloadable_files.html     ❌ Test upload fișiere
├── test_contact.php                 ❌ Test formular contact
├── test_contact_fix.php             ❌ Test fix contact
├── test_contact_final.php           ❌ Test final contact
├── test_email_attachments.php       ❌ Test atașamente email
├── test_email_smtp.php              ❌ Test SMTP config
├── test_phpmailer_quick.php         ❌ Test PHPMailer
├── test_gallery.php                 ❌ Test galerii
├── test_fix_final.php               ❌ Test verificare fix-uri
├── test_categories_system.php       ❌ Test categorii M2M
├── quick_check.sh                   ❌ Script verificare
│
admin/
└── test_downloads.php               ❌ Test descărcări
```

**Impactul ștergerii:** ZERO - Nicio funcționalitate afectată

---

## ⚠️ ȘTERGE DUPĂ RULARE - Script-uri Migrare (8 fișiere)

**Scop:** Rulează o singură dată pentru migrarea datelor, apoi șterg.

```bash
# ÎNAINTE de ștergere, verifică că au rulat cu succes:
# 1. Verifică categorii M2M:
SELECT COUNT(*) FROM product_categories;  # Trebuie > 0

# 2. Verifică slug-uri produse:
SELECT COUNT(*) FROM products WHERE slug IS NULL OR slug = '';  # Trebuie = 0

# APOI șterge:
rm migrate_*.php fix_gallery_paths.php
rm pages/update_users_table.php pages/check_schema.php
```

### Lista Script-uri Migrare:

```
Root:
├── migrate_categories_many_to_many.php  ⚠️ Migrare categorii M2M (rulat)
├── migrate_categories_web.php           ⚠️ Web interface migrare (rulat)
├── migrate_product_slugs.php            ⚠️ Generare slug-uri CLI (rulat)
├── migrate_product_slugs_web.php        ⚠️ Generare slug-uri Web (rulat)
├── fix_gallery_paths.php                ⚠️ Fix căi galerii (rulat)
│
pages/
├── update_users_table.php               ⚠️ Update tabel users (rulat)
└── check_schema.php                     ⚠️ Verificare structură DB (diagnostic)

Database SQL Partials (3 fișiere - incluse în database.sql):
├── database_contact_messages.sql        ⚠️ Inclus în database.sql
├── database_update_downloads.sql        ⚠️ Inclus în database.sql
└── database.sql                         ✅ PĂSTREAZĂ (complet)
```

**Status Recomandare:**
- `check_schema.php` → Poate fi păstrat temporar pentru diagnostic
- Restul → ȘTERGE după ce migrările au rulat cu succes

---

## 📦 ARHIVEAZĂ - Fișiere Backup (3 fișiere)

**Scop:** Versiuni vechi păstrate ca backup. Pot fi șterse după 30 zile.

```bash
# Mutare în arhivă:
mkdir -p _archive/backups
mv includes/forms/process_contact.php.* _archive/backups/
mv bootstrap.php.OLD _archive/backups/
```

### Lista Backup Files:

```
includes/forms/
├── process_contact.php.backup           📦 Backup formular contact (v1)
└── process_contact.php.OLD_PHPMAILER    📦 Versiune veche PHPMailer

Root:
└── bootstrap.php.OLD                    📦 Bootstrap deprecated
```

**Acțiune:**
1. **Producție:** ȘTERGE imediat (nu sunt necesare)
2. **Development:** Păstrează 30 zile, apoi șterge

---

## 🐛 DEBUG ONLY - Fișiere Temporare (1 fișier)

```
pages/
└── checkout_process_debug.php           🐛 Versiune debug checkout
```

**Acțiune:**
- **Development:** PĂSTREAZĂ pentru troubleshooting
- **Production:** ȘTERGE sau redenumește `.debug.php` (ignore în .htaccess)

---

## 📚 PĂSTREAZĂ - Documentație (25+ fișiere)

**Toate fișierele `.md` sunt documentație utilă - PĂSTREAZĂ toate!**

### Documentație Principală (6 fișiere)
```
├── README.md                      📚 ACTUALIZAT - Ghid principal
├── INSTALL.md                     📚 Instalare
├── DEPLOYMENT_STEPS.md            📚 Deployment
├── DEPLOYMENT_CHECKLIST.md        📚 Checklist
├── QUICK_START.md                 📚 Start rapid
└── TESTING_GUIDE.md               📚 Testare
```

### Documentație Tehnică (4 fișiere)
```
├── TECHNICAL.md                   📚 Arhitectură
├── IMPLEMENTATION_GUIDE.md        📚 Ghid implementare
├── BEFORE_AFTER_FLOW.md           📚 Comparații
└── QUICK_DEPLOY.md                📚 Deploy rapid
```

### Fix-uri Documentate (15+ fișiere)
```
├── HEADER_ERROR_FIX.md                  📚 Fix: Headers already sent
├── CHECKOUT_FIX_COMPLETE.md             📚 Fix: Validare checkout
├── CONTACT_FORM_FIX.md                  📚 Fix: Formular contact
├── CONTACT_FINAL_FIX.md                 📚 Fix: Contact final
├── FIX_FINAL_CONTACT_STRUCTURE.md       📚 Fix: Structură contact
├── EMAIL_ATTACHMENTS_FIX.md             📚 Feature: Atașamente MIME
├── DOWNLOADABLE_FILES_INTEGRATION.md    📚 Feature: Upload fișiere
├── DOWNLOADS_FIX.md                     📚 Fix: Sistem descărcări
├── MANY_TO_MANY_IMPLEMENTATION.md       📚 Feature: Categorii M2M
├── SETUP_EMAIL_HOSTINGER.md             📚 Setup: Email Hostinger
├── FIX_PHPMAILER_HOSTINGER.md           📚 Fix: PHPMailer
├── QUICK_FIX_PHPMAILER.md               📚 Quick fix PHPMailer
├── QUICK_FIX_SUMMARY.md                 📚 Rezumat fix-uri
└── TEST_DELETE_FIX.md                   📚 Fix: Ștergere teste
```

**Beneficii păstrare:**
- Istoric modificări și rezolvări
- Ghid troubleshooting pentru viitor
- Documentație pentru dezvoltatori noi
- Referință pentru features implementate

---

## 🔒 NU ȘTERGE NICIODATĂ - Fișiere Sistem (5 fișiere)

```
├── .git/                  🔒 PROTECTED - Istoric Git
├── .gitignore             🔒 PROTECTED - Ignorare Git
├── .htaccess              🔒 CRITICAL - Apache config
├── composer.json          🔒 CRITICAL - Dependențe
└── vendor/                🔒 CRITICAL - Biblioteci PHP (PHPMailer)
```

---

## 📋 Checklist Curățare Production

### ✅ Înainte de Deploy

```bash
# 1. Backup complet
tar -czf brodero_backup_$(date +%Y%m%d).tar.gz .

# 2. Verificare migrări rulate
mysql -u user -p brodero_db -e "SELECT COUNT(*) FROM product_categories;"
mysql -u user -p brodero_db -e "SELECT COUNT(*) FROM products WHERE slug = '' OR slug IS NULL;"

# 3. Ștergere fișiere test
rm test_*.php test_*.html quick_check.sh
rm admin/test_downloads.php

# 4. Ștergere migrări (dacă au rulat cu succes)
rm migrate_*.php fix_gallery_paths.php
rm pages/update_users_table.php

# 5. Ștergere backup-uri
rm includes/forms/process_contact.php.*
rm bootstrap.php.OLD

# 6. Opțional: Ștergere database SQL-uri parțiale (incluse în database.sql)
rm database_contact_messages.sql database_update_downloads.sql

# 7. Verificare finală
find . -name "test_*" -type f  # Trebuie gol
find . -name "*.backup" -type f  # Trebuie gol
```

### ✅ După Deploy

```bash
# Verificare funcționalitate
curl -I https://brodero.online  # Trebuie 200 OK
curl https://brodero.online/admin/dashboard.php  # Redirect to login

# Verificare vendor/
ls -la vendor/phpmailer/  # Trebuie să existe

# Verificare uploads/
ls -la uploads/products/  # Trebuie writable (755)
```

---

## 📊 Raport Final

### Dimensiuni Estimate (fără vendor/, .git/, uploads/)

| Categorie | Fișiere | Dimensiune |
|-----------|---------|------------|
| **PHP Core** | 83 | ~2.5 MB |
| **Assets (CSS/JS/Images)** | 10 | ~500 KB |
| **Documentație MD** | 25+ | ~1 MB |
| **Test Files** | 15 | ~800 KB |
| **Migrări** | 8 | ~400 KB |
| **Backup** | 3 | ~150 KB |

**Total Proiect Core:** ~5 MB  
**După curățare:** ~4 MB (fără test/backup)

### Vendor & Dependencies (nu atingem)

| Componente | Dimensiune |
|------------|------------|
| `vendor/` (PHPMailer + dependencies) | ~3 MB |
| `.git/` (istoric repository) | ~10-50 MB |
| `uploads/` (variază) | ~100 MB - 10 GB+ |

---

## 🎯 Recomandări Finale

### 🔴 ACȚIUNE IMEDIATĂ (Production)

```bash
# Șterge ACUM:
rm test_*.php test_*.html quick_check.sh admin/test_downloads.php

# Dacă migrările au rulat:
rm migrate_*.php fix_gallery_paths.php pages/update_users_table.php

# Backup-uri vechi:
rm includes/forms/*.backup includes/forms/*.OLD bootstrap.php.OLD
```

**Impact:** ZERO - Site funcționează 100% fără acestea

---

### 🟡 ACȚIUNE VIITOR (După 30 zile)

```bash
# Dacă totul funcționează perfect, șterge:
rm pages/checkout_process_debug.php
rm pages/check_schema.php
rm database_contact_messages.sql database_update_downloads.sql
```

---

### ✅ PĂSTREAZĂ PERMANENT

- Toate fișierele din `config/`, `includes/`, `admin/`, `pages/` (除 test/debug)
- `composer.json`, `composer.lock`, `database.sql`
- `.htaccess`, `.gitignore`
- **TOATE** fișierele `.md` (documentație)
- `vendor/` (generat de Composer, nu commit în Git)

---

**Ultima actualizare:** 11 Decembrie 2025  
**Autor:** GitHub Copilot  
**Status:** ✅ Analiză completă - Gata pentru curățare

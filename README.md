# 🧵 Brodero - Magazin Online Design-uri de Broderie

**Platformă PHP completă pentru vânzarea produselor digitale (design-uri de broderie)**

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-Proprietary-red)]()

## 📋 Cuprins

- [Prezentare](#prezentare-generală)
- [Instalare](#instalare-rapidă)
- [Structură Proiect](#structură-proiect)
- [Fișiere Importante](#fișiere-importante)
- [Dezvoltare](#ghid-dezvoltare)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)

---

## ✨ Prezentare Generală

**Brodero** este o platformă e-commerce completă specializată în design-uri digitale de broderie, construită cu:
- **Backend:** PHP 7.4+ (OOP, prepared statements, sessions)
- **Frontend:** Bootstrap 5.3, JavaScript ES6
- **Bază de date:** MySQL (structură relațională optimizată)
- **Email:** PHPMailer cu SMTP (suport atașamente MIME)
- **Plăți:** Stripe Checkout + Transfer Bancar
- **Admin Panel:** Dashboard complet pentru gestionare produse, comenzi, utilizatori

---

## 🚀 Instalare Rapidă

### 1️⃣ Cerințe Sistem

| Componentă | Versiune Minimă | Recomandată |
|------------|----------------|-------------|
| **PHP** | 7.4 | 8.0+ |
| **MySQL** | 5.7 | 8.0+ |
| **Apache/Nginx** | - | Apache 2.4+ |
| **Composer** | 2.0+ | Latest |
| **Extensii PHP** | `mysqli`, `gd`, `json`, `mbstring` | + `openssl`, `curl` |

### 2️⃣ Pași Instalare

```bash
# 1. Clone/Download proiect
git clone https://github.com/RSGDesign/Brodero.git
cd Brodero

# 2. Instalare dependențe (PHPMailer)
composer install

# 3. Creare bază de date
mysql -u root -p
CREATE DATABASE brodero_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# 4. Import structură și date
mysql -u root -p brodero_db < database.sql

# 5. Configurare
cp config/config.php.example config/config.php  # (dacă există template)
# Editați config/config.php cu credențiale DB și SITE_URL

# 6. Configurare SMTP (pentru emailuri)
# Editați config/smtp_config.php cu credențiale Hostinger SMTP
# Vezi: SETUP_EMAIL_HOSTINGER.md pentru ghid complet

# 7. Permisiuni directoare
chmod 755 uploads/ logs/
chmod 644 config/*.php

# 8. Testare
php -S localhost:8000
# Accesați: http://localhost:8000
```

### 3️⃣ Login Admin Implicit

| Câmp | Valoare |
|------|---------|
| **URL Admin** | `/admin/dashboard.php` |
| **Email** | `admin@brodero.online` |
| **Parolă** | `password` |

⚠️ **IMPORTANT:** Schimbați parola imediat după primul login!

---

## 📁 Structură Proiect

```
Brodero/
├── 📁 admin/                    # Panou administrare (PROTECTED)
│   ├── dashboard.php            # Dashboard principal admin
│   ├── admin_products.php       # Gestionare produse
│   ├── add_product.php          # Adăugare produs + fișiere
│   ├── edit_product.php         # Editare produs
│   ├── product_files.php        # Gestionare fișiere descărcabile
│   ├── admin_categories.php     # Gestionare categorii
│   ├── add_category.php         # Adăugare categorie
│   ├── edit_category.php        # Editare categorie
│   ├── admin_orders.php         # Vizualizare comenzi
│   ├── view_order.php           # Detalii comandă
│   ├── update_order_status.php  # Actualizare status comandă
│   ├── admin_users.php          # Gestionare utilizatori
│   ├── edit_user.php            # Editare utilizator
│   ├── admin_coupons.php        # Gestionare cupoane
│   ├── add_coupon.php           # Adăugare cupon
│   ├── edit_coupon.php          # Editare cupon
│   ├── admin_newsletter.php     # Newsletter dashboard
│   ├── send_newsletter.php      # Trimitere newsletter
│   ├── sync_downloads.php       # Sincronizare descărcări
│   └── test_downloads.php       # Test sistem descărcare
│
├── 📁 ajax/                     # Endpoint-uri AJAX
│   ├── process_payment.php      # Procesare plată Stripe
│   ├── update_profile.php       # Actualizare profil user
│   ├── change_password.php      # Schimbare parolă
│   ├── delete_account.php       # Ștergere cont
│   └── upload_avatar.php        # Upload avatar utilizator
│
├── 📁 assets/                   # Resurse frontend
│   ├── 📁 css/
│   │   └── style.css            # CSS principal site
│   ├── 📁 js/
│   │   └── main.js              # JavaScript principal
│   └── 📁 images/
│       ├── placeholder.svg      # Imagine placeholder
│       └── 📁 about/            # Imagini pagină "Despre"
│
├── 📁 config/                   # Configurări (SENSITIVE!)
│   ├── config.php               # Config principal: DB, SITE_URL, constante
│   ├── database.php             # Funcții conexiune DB (getDB, closeDB)
│   └── smtp_config.php          # Configurare SMTP pentru PHPMailer
│
├── 📁 includes/                 # Template-uri și funcții reutilizabile
│   ├── header.php               # Header HTML (navbar, meta tags)
│   ├── footer.php               # Footer HTML (scripts, copyright)
│   ├── category_functions.php   # Funcții categorii (CRUD, many-to-many)
│   ├── functions_orders.php     # Funcții comenzi (create, update, status)
│   ├── functions_downloads.php  # Funcții descărcări (validare, logging)
│   └── 📁 forms/
│       ├── process_contact.php.backup          # Backup formular contact
│       └── process_contact.php.OLD_PHPMAILER   # Versiune veche PHPMailer
│
├── 📁 pages/                    # Pagini publice și utilizatori
│   ├── index.php                # Homepage (redirecționare către root)
│   ├── magazin.php              # Catalog produse (grid, filtre)
│   ├── produs.php               # Pagină produs individual
│   ├── cart.php                 # Coș de cumpărături
│   ├── add_to_cart.php          # Adăugare în coș (POST)
│   ├── remove_from_cart.php     # Ștergere din coș (POST)
│   ├── update_cart.php          # Actualizare cantități (POST)
│   ├── checkout.php             # Finalizare comandă (formular)
│   ├── checkout_process.php     # Procesare comandă (validare, DB)
│   ├── checkout_process_debug.php # Versiune debug checkout
│   ├── checkout_return.php      # Return URL Stripe
│   ├── apply_coupon.php         # Aplicare cod reducere
│   ├── remove_coupon.php        # Eliminare cupon aplicat
│   ├── payment_success.php      # Success page plată
│   ├── payment_cancel.php       # Cancel page plată
│   ├── payment_instructions.php # Instrucțiuni transfer bancar
│   ├── login.php                # Login utilizatori
│   ├── logout.php               # Logout (destroy session)
│   ├── cont.php                 # Cont utilizator (dashboard)
│   ├── 📁 cont/
│   │   └── fisiere-descarcabile.php # Listă fișiere cumpărate
│   ├── download.php             # Download fișier (cu validare)
│   ├── contact.php              # Formular contact (cu atașamente)
│   ├── newsletter.php           # Înscriere newsletter
│   ├── unsubscribe.php          # Dezabonare newsletter
│   ├── despre.php               # Pagină "Despre Noi"
│   ├── faq.php                  # Întrebări frecvente
│   ├── termeni.php              # Termeni și condiții
│   ├── confidentialitate.php    # Politică confidențialitate
│   ├── cookie.php               # Politică cookies
│   ├── retur.php                # Politică retur
│   ├── comanda.php              # Urmărire comandă
│   ├── check_schema.php         # Verificare structură DB
│   └── update_users_table.php   # Update structură tabel users
│
├── 📁 uploads/                  # Fișiere încărcate (WRITABLE)
│   ├── 📁 products/             # Imagini produse
│   │   └── 📁 gallery/          # Galerii produse
│   ├── 📁 downloads/            # Fișiere descărcabile (per produs)
│   │   └── 📁 {product_id}/     # Ex: uploads/downloads/42/fisier.zip
│   ├── 📁 avatars/              # Avatar-uri utilizatori
│   └── 📁 contact/              # Atașamente formular contact
│
├── 📁 logs/                     # Log-uri sistem (WRITABLE)
│   └── error_log                # Log-uri PHP și aplicație
│
├── 📁 vendor/                   # Dependențe Composer (PHPMailer)
│   └── ...                      # (generat de composer install)
│
├── 📄 index.php                 # Homepage site (afișare produse)
├── 📄 404.php                   # Pagină eroare 404
├── 📄 .htaccess                 # Configurare Apache (URL rewriting, securitate)
├── 📄 .gitignore                # Fișiere ignorate de Git
├── 📄 composer.json             # Dependențe PHP (PHPMailer)
├── 📄 composer.lock             # Lock versiuni dependențe
├── 📄 database.sql              # Structură completă bază de date
│
├── 📁 Migration Scripts/        # Script-uri migrare DB
│   ├── migrate_categories_many_to_many.php  # CLI: Migrare categorii M2M
│   ├── migrate_categories_web.php           # Web: Migrare categorii
│   ├── migrate_product_slugs.php            # CLI: Generare slug-uri
│   ├── migrate_product_slugs_web.php        # Web: Generare slug-uri
│   ├── fix_gallery_paths.php                # Fix căi galerii
│   └── database_*.sql                       # SQL-uri migrări parțiale
│
├── 📁 Test Files/               # Fișiere testare (NU pentru producție!)
│   ├── test_checkout_validation.html        # Test validare checkout
│   ├── test_downloadable_files.html         # Test upload fișiere
│   ├── test_contact.php                     # Test formular contact
│   ├── test_contact_fix.php                 # Test fix contact
│   ├── test_contact_final.php               # Test final contact
│   ├── test_email_attachments.php           # Test atașamente email
│   ├── test_email_smtp.php                  # Test configurare SMTP
│   ├── test_phpmailer_quick.php             # Test rapid PHPMailer
│   ├── test_gallery.php                     # Test galerii imagini
│   ├── test_fix_final.php                   # Test verificare fix-uri
│   ├── test_categories_system.php           # Test sistem categorii
│   └── quick_check.sh                       # Script verificare rapidă
│
├── 📁 Documentation/            # Documentație completă (MARKDOWN)
│   ├── README.md                            # Acest fișier (ghid principal)
│   ├── INSTALL.md                           # Ghid instalare detaliată
│   ├── DEPLOYMENT_STEPS.md                  # Pași deployment production
│   ├── DEPLOYMENT_CHECKLIST.md              # Checklist deployment
│   ├── QUICK_DEPLOY.md                      # Deployment rapid
│   ├── QUICK_START.md                       # Start rapid dezvoltare
│   ├── TESTING_GUIDE.md                     # Ghid testare
│   ├── TECHNICAL.md                         # Documentație tehnică
│   ├── IMPLEMENTATION_GUIDE.md              # Ghid implementare features
│   ├── BEFORE_AFTER_FLOW.md                 # Comparații înainte/după
│   ├── HEADER_ERROR_FIX.md                  # Fix: Erori header
│   ├── CHECKOUT_FIX_COMPLETE.md             # Fix: Validare checkout
│   ├── CONTACT_FORM_FIX.md                  # Fix: Formular contact
│   ├── CONTACT_FINAL_FIX.md                 # Fix: Contact final
│   ├── FIX_FINAL_CONTACT_STRUCTURE.md       # Fix: Structură contact
│   ├── EMAIL_ATTACHMENTS_FIX.md             # Fix: Atașamente email
│   ├── DOWNLOADABLE_FILES_INTEGRATION.md    # Feature: Upload fișiere
│   ├── DOWNLOADS_FIX.md                     # Fix: Sistem descărcări
│   ├── MANY_TO_MANY_IMPLEMENTATION.md       # Feature: Categorii M2M
│   ├── SETUP_EMAIL_HOSTINGER.md             # Setup: Email Hostinger
│   ├── FIX_PHPMAILER_HOSTINGER.md           # Fix: PHPMailer Hostinger
│   ├── QUICK_FIX_PHPMAILER.md               # Quick fix PHPMailer
│   ├── QUICK_FIX_SUMMARY.md                 # Rezumat fix-uri
│   ├── TEST_DELETE_FIX.md                   # Fix: Ștergere teste
│   └── bootstrap.php.OLD                    # Bootstrap vechi (DEPRECATED)
│
└── 📄 .git/                     # Repository Git (istoricul proiectului)

```

---

## 📋 Fișiere Importante

### 🔴 **CRITICE - Necesare pentru Funcționare**

| Fișier | Scop | Note |
|--------|------|------|
| `config/config.php` | Configurare principală (DB, SITE_URL, constante) | ⚠️ SENSIBIL - nu commit |
| `config/database.php` | Funcții conexiune MySQL (`getDB()`, prepared statements) | Singleton pattern |
| `config/smtp_config.php` | Credențiale SMTP pentru PHPMailer | ⚠️ SENSIBIL - nu commit |
| `includes/header.php` | Template header HTML (navbar, meta, CSS) | Inclus în toate paginile |
| `includes/footer.php` | Template footer HTML (scripts, copyright) | Închide HTML corect |
| `includes/category_functions.php` | CRUD categorii + many-to-many | Used by admin + pages |
| `includes/functions_orders.php` | Procesare comenzi, status update | Used by checkout + admin |
| `includes/functions_downloads.php` | Validare descărcări, logging | Used by download.php |
| `index.php` | Homepage (catalog produse) | Entry point principal |
| `pages/magazin.php` | Pagină magazin (produse grid) | Filtre, paginare |
| `pages/produs.php` | Pagină produs individual | Galerie, descriere, buy |
| `pages/cart.php` | Coș cumpărături | Session-based cart |
| `pages/checkout.php` | Formular finalizare comandă | Validare JS + PHP |
| `pages/checkout_process.php` | Procesare comandă (DB insert) | Critical business logic |
| `admin/dashboard.php` | Dashboard admin (statistici) | Auth required |
| `admin/admin_products.php` | Lista produse admin | CRUD interface |
| `admin/add_product.php` | Adăugare produs + fișiere | Multi-file upload |
| `.htaccess` | Configurare Apache (routing, security) | URL rewriting |
| `database.sql` | Structură completă DB | Import la setup |
| `composer.json` | Dependențe PHP (PHPMailer) | Run `composer install` |

### 🟡 **IMPORTANTE - Funcționalități Esențiale**

| Fișier | Scop | Status |
|--------|------|--------|
| `pages/contact.php` | Formular contact cu atașamente MIME | ✅ Funcțional |
| `pages/download.php` | Descărcare fișiere cu validare user | ✅ Funcțional |
| `pages/login.php` | Autentificare utilizatori | ✅ Funcțional |
| `pages/cont.php` | Dashboard cont utilizator | ✅ Funcțional |
| `pages/cont/fisiere-descarcabile.php` | Listă produse cumpărate | ✅ Funcțional |
| `ajax/process_payment.php` | Stripe Checkout session | ✅ Funcțional |
| `admin/product_files.php` | Gestionare fișiere descărcabile | ✅ Funcțional |
| `admin/admin_orders.php` | Gestionare comenzi | ✅ Funcțional |
| `admin/view_order.php` | Detalii comandă individuală | ✅ Funcțional |
| `assets/css/style.css` | CSS principal site | ✅ Folosit |
| `assets/js/main.js` | JavaScript principal | ✅ Folosit |

### 🔵 **UTILE - Administrare și Extindere**

| Fișier | Scop | Status |
|--------|------|--------|
| `admin/admin_categories.php` | Gestionare categorii (many-to-many) | ✅ Funcțional |
| `admin/admin_coupons.php` | Gestionare cupoane reducere | ✅ Funcțional |
| `admin/admin_users.php` | Gestionare utilizatori | ✅ Funcțional |
| `admin/admin_newsletter.php` | Dashboard newsletter | ✅ Funcțional |
| `admin/send_newsletter.php` | Trimitere email în masă | ✅ Funcțional |
| `pages/newsletter.php` | Înscriere newsletter (frontend) | ✅ Funcțional |
| `pages/apply_coupon.php` | Aplicare cod reducere | ✅ Funcțional |
| `pages/despre.php` | Pagină "Despre Noi" | ✅ Funcțional |
| `pages/faq.php` | Întrebări frecvente | ✅ Funcțional |
| `pages/termeni.php` | Termeni și condiții | ✅ Funcțional |

### 🟢 **MIGRĂRI - Script-uri One-Time (Rulează o singură dată)**

| Fișier | Scop | Status |
|--------|------|--------|
| `migrate_categories_many_to_many.php` | Migrare categorii la M2M | ✅ Rulat - ȘTERGE după |
| `migrate_categories_web.php` | Web interface migrare | ✅ Rulat - ȘTERGE după |
| `migrate_product_slugs.php` | Generare slug-uri produse CLI | ✅ Rulat - ȘTERGE după |
| `migrate_product_slugs_web.php` | Generare slug-uri Web | ✅ Rulat - ȘTERGE după |
| `fix_gallery_paths.php` | Fix căi imagini galerie | ✅ Rulat - ȘTERGE după |
| `pages/update_users_table.php` | Update structură tabel users | ✅ Rulat - ȘTERGE după |
| `pages/check_schema.php` | Verificare structură DB | ⚠️ Diagnostic only |

### 🔴 **TEST - Fișiere Testare (NU DEPLOY în Producție!)**

| Fișier | Scop | Status |
|--------|------|--------|
| `test_checkout_validation.html` | Test validare formular checkout | 🧪 Test only |
| `test_downloadable_files.html` | Test upload fișiere descărcabile | 🧪 Test only |
| `test_contact.php` | Test formular contact | 🧪 Test only |
| `test_contact_fix.php` | Test fix contact | 🧪 Test only |
| `test_contact_final.php` | Test final contact | 🧪 Test only |
| `test_email_attachments.php` | Test atașamente email | 🧪 Test only |
| `test_email_smtp.php` | Test SMTP configuration | 🧪 Test only |
| `test_phpmailer_quick.php` | Test rapid PHPMailer | 🧪 Test only |
| `test_gallery.php` | Test galerii imagini | 🧪 Test only |
| `test_fix_final.php` | Test verificare fix-uri | 🧪 Test only |
| `test_categories_system.php` | Test categorii many-to-many | 🧪 Test only |
| `admin/test_downloads.php` | Test sistem descărcare | 🧪 Test only |
| `quick_check.sh` | Script verificare rapidă | 🧪 Test only |

### ⚪ **BACKUP/DEPRECATED - Fișiere Vechi (Pot fi Șterse)**

| Fișier | Scop | Status |
|--------|------|--------|
| `includes/forms/process_contact.php.backup` | Backup formular contact | 📦 Backup - șterge după 30 zile |
| `includes/forms/process_contact.php.OLD_PHPMAILER` | Versiune veche PHPMailer | 📦 Deprecated - șterge |
| `bootstrap.php.OLD` | Bootstrap vechi | 📦 Deprecated - șterge |
| `pages/checkout_process_debug.php` | Versiune debug checkout | 🐛 Debug - păstrează temporar |
| `database_contact_messages.sql` | SQL parțial (contact messages) | 📦 Inclus în database.sql |
| `database_update_downloads.sql` | SQL parțial (downloads) | 📦 Inclus în database.sql |

### ⚫ **DOCUMENTAȚIE - Ghiduri și Referințe**

| Fișier | Scop | Status |
|--------|------|--------|
| `README.md` | **Acest fișier** - Ghid principal | ✅ Actualizat |
| `DEPLOYMENT_STEPS.md` | Pași deployment production | 📚 Referință |
| `SETUP_EMAIL_HOSTINGER.md` | Setup email Hostinger | 📚 Referință |
| `CHECKOUT_FIX_COMPLETE.md` | Fix eroare checkout | 📚 Rezolvat |
| `EMAIL_ATTACHMENTS_FIX.md` | Implementare atașamente | 📚 Implementat |
| `DOWNLOADABLE_FILES_INTEGRATION.md` | Upload fișiere în add_product | 📚 Implementat |
| *+ 15 alte fișiere MD* | Diverse fix-uri și features | 📚 Arhivă |

---

## 🎨 Sistem Email

### ✅ Formular Contact - Implementare Simplă (FUNCȚIONEAZĂ!)

**Metodă:** Funcția PHP `mail()` - identică cu Newsletter-ul din Admin Dashboard

**Caracteristici:**
- ✅ **Template HTML profesional** (gradient header, design modern)
- ✅ **Protecție anti-spam:** CSRF tokens, honeypot
- ✅ **Backup automat** în database
- ✅ **Validare completă** input + fișiere atașate
- ✅ **Reply-To** setat la email-ul utilizatorului

**Cum funcționează:**
```php
// EXACT CA ÎN NEWSLETTER (care FUNCȚIONEAZĂ!)
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Brodero <noreply@brodero.online>\r\n";
$headers .= "Reply-To: " . $email . "\r\n";

mail($toEmail, $subject, $htmlContent, $headers);
```

**Test formular:**
1. Accesează: `pages/contact.php`
2. Completează și trimite formular
3. Verifică inbox: `contact@brodero.online`

**Documentație:**
- **CONTACT_FINAL_FIX.md** - Implementare completă și testare
- **test_contact_final.php** - Script verificare

### 📬 Newsletter Admin (FUNCȚIONEAZĂ PERFECT!)

**Locație:** `admin/send_newsletter.php`

**Metodă:** Funcția PHP `mail()` cu HTML templates

**Features:**
- ✅ Trimitere bulk către abonați
- ✅ Template HTML profesional
- ✅ Filtrare destinatari (activi/inactivi/toți)
- ✅ Statistici trimitere (succes/eșuat)

## 📋 Funcționalități Principale

### Pentru Vizitatori
✅ Navigare intuitivă prin categorii de produse  
✅ Filtrare și sortare avansată  
✅ Căutare produse  
✅ Vizualizare detalii produse  
✅ **Formular contact cu protecție anti-spam** (CSRF, honeypot, rate limiting)  

### Pentru Utilizatori Autentificați
✅ Cont personal cu dashboard  
✅ Vizualizare istoric comenzi  
✅ Descărcare fișiere digitale  
✅ Gestionare profil  

### Pentru Administratori
✅ Dashboard cu statistici complete  
✅ **Gestionare Produse** - CRUD complet cu upload imagini și galerie  
✅ **Gestionare Categorii** - Organizare produse pe categorii  
✅ **Gestionare Comenzi** - Vizualizare, actualizare status, filtrare  
✅ **Gestionare Utilizatori** - CRUD complet, blocare/activare conturi, statistici  
✅ **Gestionare Newsletter** - Abonați, trimitere campanii email, statistici  
✅ Statistici vânzări și comenzi  

## 📁 Structura Fișierelor

```
Brodero/
├── 📂 admin/              # Panou administrare
│   ├── dashboard.php      # Dashboard principal
│   ├── admin_products.php # Gestionare produse
│   ├── add_product.php    # Adăugare produs
│   ├── edit_product.php   # Editare produs
│   ├── admin_categories.php # Gestionare categorii
│   ├── add_category.php   # Adăugare categorie
│   ├── edit_category.php  # Editare categorie
│   ├── admin_orders.php   # Gestionare comenzi
│   ├── view_order.php     # Detalii comandă
│   ├── admin_users.php    # Gestionare utilizatori
│   ├── edit_user.php      # Editare utilizator
│   ├── admin_newsletter.php # Gestionare abonați newsletter
│   └── send_newsletter.php # Trimitere campanii email
├── 📂 assets/
│   ├── css/              # Stiluri personalizate
│   ├── js/               # JavaScript
│   └── images/           # Imagini și SVG
├── 📂 config/            # Configurări și conexiune DB
├── 📂 includes/          # Header și Footer
├── 📂 pages/             # Toate paginile site-ului
│   ├── magazin.php       # Catalog produse
│   ├── produs.php        # Detalii produs cu galerie
│   ├── despre.php        # Despre companie
│   ├── contact.php       # Formular contact
│   ├── cont.php          # Dashboard utilizator
│   ├── login.php         # Autentificare
│   ├── unsubscribe.php   # Dezabonare newsletter
│   └── ...               # Alte pagini
├── 📂 uploads/           # Fișiere uploadate
│   ├── products/         # Imagini produse
│   │   └── gallery/      # Galerii produse
│   └── categories/       # Imagini categorii
├── 📄 index.php          # Pagina principală
├── 📄 404.php            # Pagină eroare personalizată
├── 📄 database.sql       # Structura bazei de date
└── 📄 INSTALL.md         # Ghid detaliat instalare
```

## 🎨 Pagini Disponibile

### Frontend
- **/** - Pagina principală cu hero și produse featured
- **/pages/despre.php** - Despre companie
- **/pages/magazin.php** - Catalog produse cu filtrare și sortare
- **/pages/produs.php** - Detalii produs cu galerie foto interactivă
- **/pages/contact.php** - Formular contact
- **/pages/cont.php** - Dashboard utilizator
- **/pages/login.php** - Autentificare și înregistrare
- **/404.php** - Pagină eroare personalizată cu redirect automat

### Pagini Legale
- Termeni și Condiții
- Politica de Confidențialitate  
- Politica Cookie
- Politica de Retur
- FAQ

### Backend
- **/admin/dashboard.php** - Panou administrare principal
- **/admin/admin_products.php** - Gestionare produse (listare, adăugare, editare, ștergere)
- **/admin/admin_categories.php** - Gestionare categorii produse
- **/admin/admin_orders.php** - Gestionare comenzi (listare, filtrare, actualizare status)
- **/admin/view_order.php** - Vizualizare detalii comandă completă
- **/admin/admin_users.php** - Gestionare utilizatori (listare, editare, blocare, ștergere)
- **/admin/edit_user.php** - Editare detalii utilizator complet
- **/admin/admin_newsletter.php** - Gestionare abonați newsletter
- **/admin/send_newsletter.php** - Compunere și trimitere campanii email

## 🛠️ Tehnologii

- **Backend:** PHP 7.4+, MySQL
- **Frontend:** Bootstrap 5.3, JavaScript ES6
- **Icons:** Bootstrap Icons
- **Fonts:** Google Fonts (Poppins)
- **Security:** Prepared Statements, Password Hashing

## 🔒 Securitate

✅ SQL Injection Prevention (Prepared Statements)  
✅ XSS Protection (htmlspecialchars)  
✅ CSRF Protection (sesiuni)  
✅ Password Hashing (bcrypt)  
✅ Input Validation & Sanitization  

## 📱 Design Responsive

Site-ul este complet responsive și optimizat pentru:
- 📱 Telefoane mobile
- 📱 Tablete  
- 💻 Desktop
- 🖥️ Large screens

## 🎯 Caracteristici Tehnice

### Gestionare Produse
- Upload imagine principală
- Galerie multiple imagini (până la 5MB/imagine)
- Categorii organizate
- Filtrare și căutare avansată
- Status: activ/inactiv, în stoc/epuizat
- Prețuri și reduceri

### Gestionare Categorii  
- Upload imagine categorie
- Slug URL-friendly generat automat
- Ordine afișare personalizabilă
- Descriere SEO-friendly

### Gestionare Comenzi
- Filtrare după: client, status, dată
- 6 tipuri statistici: total, pending, processing, completed, cancelled, revenue
- Actualizare status rapid (modal) sau detaliat
- Vizualizare completă detalii comandă
- Status plată: neplătit/plătit/rambursat
- Printare comandă optimizată

### Galerie Produse
- Lightbox modal pentru vizualizare mărită
- Navigare cu săgeți (←/→) și tastatură
- Thumbnails interactive cu border activ
- Zoom și preview imagini complete
- Support mouse și touch

### Gestionare Utilizatori
- CRUD complet utilizatori
- Blocare/reactivare conturi
- Schimbare rol (client/admin)
- Protecție auto-blocare și admin unic
- Validări complete (email unic, username unic, parolă min 6 caractere)
- Statistici comenzi per utilizator
- Filtrare după nume, email, rol, status

### Gestionare Newsletter
- 5 carduri statistici: total, activi, dezabonați, noi astăzi, luna curentă
- Adăugare manuală abonați
- Dezabonare/reactivare abonați
- Ștergere abonați cu confirmare
- Filtrare după email și status
- Formular trimitere campanii email
- Template-uri HTML predefinite (salut, ofertă, produs, buton)
- Preview newsletter înainte de trimitere
- Selectare destinatari: toți/activi/inactivi
- Email template profesional cu header/footer Brodero
- Link dezabonare automat în fiecare email
- Pagină publică de dezabonare (unsubscribe.php)

### Design Modern
- Layout minimalist și clean
- Palet de culori profesională (#6366f1 primary)
- Animații subtile
- Icons intuitive (Bootstrap Icons)

### Performanță
- Lazy loading imagini
- CSS/JS optimizat
- Queries database eficiente
- Caching static assets
- Paginare (20 items/pagină)

### UX/UI
- Navigare intuitivă
- Feedback vizual (badge-uri colorate)
- Mesaje de eroare clare
- Formulare validate
- Confirmare înainte de ștergere

---

## 👨‍💻 Ghid Dezvoltare

### Convenții Cod

| Aspect | Convenție | Exemplu |
|--------|-----------|---------|
| **Fișiere** | `snake_case.php` | `admin_products.php` |
| **Variabile PHP** | `$camelCase` | `$productId`, `$userName` |
| **Constante** | `UPPER_CASE` | `SITE_URL`, `DB_HOST` |
| **Funcții** | `camelCase()` | `getProductById()` |
| **SQL** | Prepared statements | `$stmt->bind_param("i", $id)` |
| **Validare** | Server + Client | `isset()` + `trim()` + JavaScript |
| **Output** | `htmlspecialchars()` | Previne XSS |

### Template Pagină Nouă

```php
<?php
/**
 * Nume Pagină - Descriere
 */

// 1. Include config/database PRIMUL
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// 2. Procesare POST ÎNAINTE de header (pentru redirect fără erori)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare + procesare
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    
    if ($name === '') {
        setMessage("Câmp obligatoriu!", "danger");
        redirect('/pages/pagina.php');
    }
    
    // Salvare în DB
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO table (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    
    if ($stmt->execute()) {
        setMessage("Succes!", "success");
        redirect('/pages/pagina.php');
    }
}

// 3. Include header DUPĂ procesare POST
$pageTitle = "Titlu Pagină";
require_once __DIR__ . '/../includes/header.php';

// 4. Query-uri pentru afișare
$db = getDB();
$stmt = $db->prepare("SELECT * FROM table WHERE active = 1");
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!-- 5. HTML Content -->
<section class="py-5">
    <div class="container">
        <h1><?php echo $pageTitle; ?></h1>
        
        <?php foreach ($items as $item): ?>
            <div class="card">
                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- 6. JavaScript (la final) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Your JS here
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
```

---

## 🐛 Troubleshooting

### 1. **"Headers already sent" Error**

**Simptom:** `Warning: Cannot modify header information - headers already sent`

**Cauză:** HTML output înainte de `redirect()` sau `header()`

**Soluție:**
```php
// ❌ GREȘIT - Header inclus înainte de redirect
require_once 'header.php';  // Output HTML
if ($_POST) {
    redirect('/page.php');  // EROARE!
}

// ✅ CORECT - Procesare POST înainte de header
if ($_POST) {
    // Procesare și redirect
    redirect('/page.php');
}
require_once 'header.php';  // Acum e safe
```

**Documentație:** `HEADER_ERROR_FIX.md`

---

### 2. **Email nu se trimite / PHPMailer Error**

**Verificări:**
```bash
# 1. Verifică logs
tail -f logs/error_log

# 2. Verifică Composer
composer show phpmailer/phpmailer
# Dacă lipsește:
composer install

# 3. Testează SMTP
# (doar în development, NU în producție!)
php test_email_smtp.php
```

**Verifică credențiale:**
```php
// config/smtp_config.php
define('SMTP_HOST', 'smtp.hostinger.com');  // ✅
define('SMTP_PORT', 587);                    // ✅ SAU 465 cu SSL
define('SMTP_USERNAME', 'your@email.com');   // ✅
define('SMTP_PASSWORD', 'parola_reala');     // ⚠️ NU comita în Git!
define('SMTP_ENCRYPTION', 'tls');            // ✅ 'tls' pentru 587, 'ssl' pentru 465
```

**Documentație:** `SETUP_EMAIL_HOSTINGER.md`, `FIX_PHPMAILER_HOSTINGER.md`

---

### 3. **"Completați toate câmpurile" (chiar dacă sunt completate)**

**Cauză:** Neconcordanță nume câmpuri HTML ↔ PHP validare

**Verificare:**
```html
<!-- HTML Formular trebuie să folosească: -->
<input name="customer_name">        <!-- NU first_name! -->
<input name="customer_email">       <!-- NU email! -->
<input name="customer_phone">       <!-- NU phone! -->
<textarea name="shipping_address">  <!-- NU address! -->
```

```php
// PHP Validare trebuie să verifice:
$name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
if ($name === '') { // NU empty()! Atenție la whitespace
    // Eroare
}
```

**Documentație:** `CHECKOUT_FIX_COMPLETE.md`

---

### 4. **"Duplicate entry '' for key 'slug'" la adăugare produs**

**Cauză:** Produs fără slug generat (sau slug gol în DB)

**Soluție 1: Migrare existente**
```bash
# Generează slug-uri pentru produse existente
php migrate_product_slugs.php
# SAU prin web (apoi șterge fișierul!):
https://brodero.online/migrate_product_slugs_web.php
```

**Soluție 2: Verificare cod**
```php
// add_product.php și edit_product.php TREBUIE să includă:
$slug = generateUniqueSlug($db, $productName, 'products');
// în INSERT/UPDATE query
```

**Documentație:** `QUICK_FIX_SUMMARY.md`

---

### 5. **Imagini nu se afișează**

**Verificări:**
```bash
# Permisiuni directoare
ls -la uploads/products/
# Trebuie: drwxr-xr-x (755)

chmod 755 uploads/
chmod 755 uploads/products/
chmod 644 uploads/products/*.jpg

# Verifică căi în DB
SELECT id, name, image, gallery FROM products LIMIT 5;
# image: "products/product_123.jpg" (relativ la uploads/)
# gallery: ["products/gallery/img1.jpg", ...] (JSON)
```

**Fix galerii (doar o dată):**
```bash
php fix_gallery_paths.php
```

---

### 6. **Fișiere descărcabile nu se descarcă**

**Verificări:**
```sql
-- 1. Verifică dacă fișierul există în DB
SELECT pf.id, pf.product_id, pf.file_name, pf.file_path, pf.status
FROM product_files pf
WHERE pf.product_id = ?;

-- 2. Verifică dacă user a cumpărat produsul
SELECT o.id, o.user_id, oi.product_id
FROM orders o
JOIN order_items oi ON o.id = oi.order_id
WHERE o.user_id = ? AND oi.product_id = ?;

-- 3. Verifică limită descărcări
SELECT download_limit, download_count FROM product_files WHERE id = ?;
```

**Debug `pages/download.php`:**
```php
// Adaugă debugging temporar:
error_log("Download attempt - User: $userId, File: $fileId");
error_log("Has access: " . ($hasAccess ? 'YES' : 'NO'));
```

**Documentație:** `DOWNLOADS_FIX.md`

---

### 7. **Categorii nu se afișează / Produse fără categorii**

**Cauză:** Sistem many-to-many nu e migrat

**Verificare:**
```sql
-- Verifică tabelul product_categories (many-to-many)
SELECT * FROM product_categories LIMIT 10;

-- Verifică coloane produse (NU ar trebui să existe `category_id`)
DESCRIBE products;
```

**Soluție:**
```bash
# Migrare la many-to-many (doar o dată!)
php migrate_categories_many_to_many.php
# SAU:
https://brodero.online/migrate_categories_web.php
```

**Documentație:** `MANY_TO_MANY_IMPLEMENTATION.md`

---

### 8. **Eroare conexiune bază de date**

**Verificări:**
```php
// config/config.php
define('DB_HOST', 'localhost');        // SAU IP server
define('DB_USER', 'user');
define('DB_PASS', 'parola');
define('DB_NAME', 'brodero_db');

// Test conexiune:
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";
```

**Hostinger specific:**
```php
// Hostinger folosește uneori:
define('DB_HOST', 'localhost');  // NU IP-ul!
// SAU:
define('DB_HOST', '127.0.0.1');
```

---

### 9. **Eroare 404 pe toate paginile**

**Cauză:** `.htaccess` lipsă sau `SITE_URL` incorect

**Verificare `.htaccess`:**
```apache
# Trebuie să existe în root:
RewriteEngine On
RewriteBase /

# Verifică permisiuni
ls -la .htaccess
# Trebuie: -rw-r--r-- (644)
```

**Verificare `SITE_URL`:**
```php
// config/config.php
define('SITE_URL', 'https://brodero.online');  // FĂRĂ trailing slash!

// Test:
echo SITE_URL . '/pages/magazin.php';
// Trebuie: https://brodero.online/pages/magazin.php
```

---

### 10. **Upload fișiere eșuează**

**Verificări PHP:**
```php
// Verifică în php.ini:
upload_max_filesize = 200M
post_max_size = 210M
max_file_uploads = 20
memory_limit = 256M

// Verifică efectiv:
echo ini_get('upload_max_filesize');
echo ini_get('post_max_size');
```

**Verificări permisiuni:**
```bash
chmod 755 uploads/
chmod 755 uploads/downloads/
chmod 755 uploads/products/
chmod 755 uploads/contact/
```

---

## 🚀 Deployment Production

### Checklist Pre-Deploy

- [ ] **Fișiere test ȘTERSE**
  ```bash
  rm test_*.php test_*.html quick_check.sh
  ```

- [ ] **Migrări ȘTERSE** (după ce au rulat)
  ```bash
  rm migrate_*.php fix_gallery_paths.php
  rm pages/update_users_table.php pages/check_schema.php
  ```

- [ ] **Config actualizat**
  ```php
  // config/config.php
  define('DEBUG_MODE', false);  // ⚠️ IMPORTANT!
  define('SITE_URL', 'https://brodero.online');
  // DB credentials pentru production
  ```

- [ ] **SMTP configurat**
  ```php
  // config/smtp_config.php
  define('SMTP_PASSWORD', 'parola_reala_hostinger');
  ```

- [ ] **Composer dependențe**
  ```bash
  composer install --no-dev --optimize-autoloader
  ```

- [ ] **Permisiuni**
  ```bash
  chmod 755 uploads/ logs/
  chmod 644 config/*.php
  ```

- [ ] **Parola admin schimbată**
  - Login → Admin → Schimbă parola

---

## 📚 Documentație Suplimentară

### Fișiere Markdown Disponibile

| Fișier | Conținut |
|--------|----------|
| `DEPLOYMENT_STEPS.md` | Ghid complet deployment Hostinger |
| `SETUP_EMAIL_HOSTINGER.md` | Configurare email SMTP pas cu pas |
| `CHECKOUT_FIX_COMPLETE.md` | Fix validare formular checkout |
| `EMAIL_ATTACHMENTS_FIX.md` | Implementare atașamente MIME |
| `DOWNLOADABLE_FILES_INTEGRATION.md` | Upload fișiere în add_product.php |
| `MANY_TO_MANY_IMPLEMENTATION.md` | Sistem categorii many-to-many |
| `HEADER_ERROR_FIX.md` | Rezolvare "headers already sent" |

---

## 📧 Suport & Contact

**Pentru asistență tehnică:**
- **Email:** contact@brodero.online
- **Telefon:** 0741133343
- **GitHub Issues:** [github.com/RSGDesign/Brodero/issues](https://github.com/RSGDesign/Brodero/issues)

**Ore suport:** Luni-Vineri, 09:00-17:00 (EET)

---

## 📜 Licență

© 2022-2025 **Brodero**. Toate drepturile rezervate.

**Proprietar:** RSG Design  
**Dezvoltat pentru:** Brodero.online

---

## 🎯 Roadmap Viitor

### v2.0 (Q1 2026)
- [ ] **API REST** pentru integrări externe
- [ ] **Sistem review-uri** cu rating produse
- [ ] **Wishlist** salvat în cont
- [ ] **Notificări email** automate (comanda procesată, expediere)
- [ ] **Export rapoarte** (PDF/Excel) pentru vânzări

### v2.1 (Q2 2026)
- [ ] **Multi-limbă** (RO/EN)
- [ ] **Wallet utilizator** (credit store)
- [ ] **Programe fidelitate** (puncte, discount-uri recurente)
- [ ] **Chat suport** live
- [ ] **Blog integrat**

### v3.0 (Q3 2026)
- [ ] **Mobile app** (React Native)
- [ ] **AR preview** design-uri pe țesături
- [ ] **Design customizer** în browser
- [ ] **Marketplace** (vânzători multipli)

---

**Dezvoltat cu ❤️ și ☕ pentru comunitatea de broderie românească**

*Happy coding! 🧵✨*

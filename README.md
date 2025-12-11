# 🧵 Brodero - Magazin Design de Broderie

Website complet în PHP cu Bootstrap pentru magazinul online de design-uri de broderie Brodero.

## ✨ Prezentare Generală

Brodero este o platformă modernă și elegantă pentru vânzarea de design-uri digitale de broderie. Site-ul oferă o experiență completă pentru utilizatori și administratori, cu design responsive și funcționalități avansate.

## 🚀 Instalare Rapidă

### 1. Cerințe Minime
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- Composer (pentru PHPMailer și dependențe)

### 2. Pași de Instalare

```bash
# 1. Clonați/Copiați proiectul în directorul web
# Exemplu: C:\xampp\htdocs\brodero

# 2. Instalați dependențe Composer
composer update

# 3. Creați baza de date
# - Accesați phpMyAdmin
# - Creați baza de date: brodero_db
# - Importați: database.sql

# 4. Configurați conexiunea
# Editați config/config.php:
# - DB_HOST, DB_USER, DB_PASS, DB_NAME
# - SITE_URL

# 5. Configurați emailul (IMPORTANT!)
# Editați config/smtp_config.php:
# - Setați SMTP_PASSWORD cu parola reală
# Vezi: DEPLOYMENT_STEPS.md pentru ghid complet

# 6. Accesați site-ul
# http://localhost/brodero
```

### 3. Login Admin
- **Email:** admin@brodero.online
- **Parolă:** password
- ⚠️ **Schimbați parola imediat!**

## 📧 Sistem Email

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

## 📝 Notițe Importante

1. **Configurare inițială**: Verificați și ajustați setările din `config/config.php`
2. **Securitate**: Schimbați parola admin-ului după prima autentificare
3. **Permisiuni**: Setați permisiuni corecte pentru directorul `uploads/`
4. **Email**: Configurați SMTP pentru funcționalitatea de email (opțional)

## 🐛 Troubleshooting

### Eroare conexiune bază de date
- Verificați credențialele în `config/config.php`
- Asigurați-vă că MySQL rulează
- Verificați că baza de date `brodero_db` există

### Eroare 404 pe pagini
- Verificați `SITE_URL` în `config/config.php`
- Asigurați-vă că `.htaccess` este activ

### Upload-uri nu funcționează
- Verificați permisiunile directorului `uploads/`
- Verificați setările PHP: `upload_max_filesize`

## 📧 Support

Pentru întrebări sau probleme:
- **Email:** contact@brodero.online
- **Telefon:** 0741133343

## 🔄 Actualizări Viitoare

- [ ] Integrare gateway plată
- [ ] Sistem wishlist
- [ ] Review-uri produse
- [ ] Email notifications
- [ ] Export rapoarte
- [ ] API REST

## 📜 Licență

© 2022-2025 Brodero. Toate drepturile rezervate.

---

**Creat cu ❤️ și dedicație pentru comunitatea de broderie**

*Enjoy coding! 🧵*

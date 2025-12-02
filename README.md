# 🧵 Brodero - Magazin Design de Broderie

Website complet în PHP cu Bootstrap pentru magazinul online de design-uri de broderie Brodero.

## ✨ Prezentare Generală

Brodero este o platformă modernă și elegantă pentru vânzarea de design-uri digitale de broderie. Site-ul oferă o experiență completă pentru utilizatori și administratori, cu design responsive și funcționalități avansate.

## 🚀 Instalare Rapidă

### 1. Cerințe Minime
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx

### 2. Pași de Instalare

```bash
# 1. Clonați/Copiați proiectul în directorul web
# Exemplu: C:\xampp\htdocs\brodero

# 2. Creați baza de date
# - Accesați phpMyAdmin
# - Creați baza de date: brodero_db
# - Importați: database.sql

# 3. Configurați conexiunea
# Editați config/config.php:
# - DB_HOST, DB_USER, DB_PASS, DB_NAME
# - SITE_URL

# 4. Accesați site-ul
# http://localhost/brodero
```

### 3. Login Admin
- **Email:** admin@brodero.online
- **Parolă:** password
- ⚠️ **Schimbați parola imediat!**

## 📋 Funcționalități Principale

### Pentru Vizitatori
✅ Navigare intuitivă prin categorii de produse  
✅ Filtrare și sortare avansată  
✅ Căutare produse  
✅ Vizualizare detalii produse  
✅ Formular contact cu upload fișiere  

### Pentru Utilizatori Autentificați
✅ Cont personal cu dashboard  
✅ Vizualizare istoric comenzi  
✅ Descărcare fișiere digitale  
✅ Gestionare profil  

### Pentru Administratori
✅ Dashboard cu statistici  
✅ Gestionare produse (CRUD)  
✅ Gestionare comenzi  
✅ Gestionare utilizatori  
✅ Vizualizare mesaje contact  

## 📁 Structura Fișierelor

```
Brodero/
├── 📂 admin/              # Panou administrare
├── 📂 assets/
│   ├── css/              # Stiluri personalizate
│   ├── js/               # JavaScript
│   └── images/           # Imagini și SVG
├── 📂 config/            # Configurări și conexiune DB
├── 📂 includes/          # Header și Footer
├── 📂 pages/             # Toate paginile site-ului
├── 📂 uploads/           # Fișiere uploadate
├── 📄 index.php          # Pagina principală
├── 📄 database.sql       # Structura bazei de date
└── 📄 INSTALL.md         # Ghid detaliat instalare
```

## 🎨 Pagini Disponibile

### Frontend
- **/** - Pagina principală cu hero și produse featured
- **/pages/despre.php** - Despre companie
- **/pages/magazin.php** - Catalog produse
- **/pages/contact.php** - Formular contact
- **/pages/cont.php** - Dashboard utilizator
- **/pages/login.php** - Autentificare

### Pagini Legale
- Termeni și Condiții
- Politica de Confidențialitate  
- Politica Cookie
- Politica de Retur
- FAQ

### Backend
- **/admin/dashboard.php** - Panou administrare

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

### Design Modern
- Layout minimalist și clean
- Palet de culori profesională
- Animații subtile
- Icons intuitive

### Performanță
- Lazy loading imagini
- CSS/JS optimizat
- Queries database eficiente
- Caching static assets

### UX/UI
- Navigare intuitivă
- Feedback vizual
- Mesaje de eroare clare
- Formulare validate

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

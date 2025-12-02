# Brodero - Website Magazin Design de Broderie

Website complet în PHP cu Bootstrap pentru magazinul de design de broderie **Brodero**.

## 🎨 Caracteristici

- **Design Responsive** - Funcționează perfect pe desktop, tabletă și mobil
- **Interface Modernă** - Design minimalist și elegant cu Bootstrap 5
- **Sistem Complet de Autentificare** - Login, Register, Logout cu sesiuni PHP
- **Gestionare Produse** - Filtrare, sortare, căutare și pagination
- **Dashboard Admin** - Gestionare produse, comenzi, utilizatori și statistici
- **Pagini Legale Complete** - Termeni, Confidențialitate, Cookie, Retur, FAQ
- **Formular Contact** - Cu upload fișiere și validare

## 📁 Structura Proiectului

```
Brodero/
├── admin/                  # Dashboard admin
│   └── dashboard.php      # Panou administrare
├── assets/
│   ├── css/
│   │   └── style.css      # Stiluri personalizate
│   ├── js/
│   │   └── main.js        # JavaScript principal
│   └── images/            # Imagini site
├── config/
│   ├── config.php         # Configurare generală
│   └── database.php       # Conexiune bază de date
├── includes/
│   ├── header.php         # Header comun
│   └── footer.php         # Footer comun
├── pages/                 # Pagini site
│   ├── despre.php         # Despre noi
│   ├── magazin.php        # Magazin produse
│   ├── contact.php        # Formular contact
│   ├── cont.php           # Contul utilizatorului
│   ├── login.php          # Autentificare
│   ├── logout.php         # Deconectare
│   ├── newsletter.php     # Abonare newsletter
│   ├── termeni.php        # Termeni și condiții
│   ├── confidentialitate.php  # Politica confidențialitate
│   ├── cookie.php         # Politica cookie
│   ├── retur.php          # Politica retur
│   └── faq.php            # Întrebări frecvente
├── uploads/               # Fișiere uploadate
├── database.sql           # Structura bazei de date
├── index.php              # Pagina principală
└── README.md             # Acest fișier
```

## 🚀 Instalare și Configurare

### Cerințe

- PHP 7.4 sau mai nou
- MySQL 5.7 sau mai nou
- Apache/Nginx cu mod_rewrite activat
- Extensii PHP: mysqli, gd, fileinfo

### Pași de Instalare

1. **Copiați fișierele** în directorul web server (ex: `C:\xampp\htdocs\brodero`)

2. **Creați baza de date**
   - Deschideți phpMyAdmin
   - Creați o bază de date nouă numită `brodero_db`
   - Importați fișierul `database.sql`

3. **Configurați conexiunea la baza de date**
   
   Deschideți `config/config.php` și ajustați:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');          // Username MySQL
   define('DB_PASS', '');              // Parola MySQL
   define('DB_NAME', 'brodero_db');
   ```

4. **Configurați URL-ul site-ului**
   
   În `config/config.php`, ajustați:
   ```php
   define('SITE_URL', 'http://localhost/brodero');
   ```

5. **Setați permisiuni pentru directorul uploads**
   ```bash
   chmod 755 uploads/
   ```

6. **Accesați site-ul**
   - Frontend: `http://localhost/brodero`
   - Admin: `http://localhost/brodero/admin/dashboard.php`

## 👤 Conturi Implicite

### Administrator
- **Email:** admin@brodero.online
- **Parolă:** password
- **⚠️ IMPORTANT:** Schimbați parola după prima autentificare!

## 📄 Pagini Principale

### Frontend
- **Acasă** (`/`) - Banner hero, produse featured, features
- **Despre Noi** (`/pages/despre.php`) - Povestea, misiunea, valorile
- **Magazin** (`/pages/magazin.php`) - Filtrare, sortare, pagination produse
- **Contact** (`/pages/contact.php`) - Formular cu upload fișiere
- **Contul Meu** (`/pages/cont.php`) - Comenzi, fișiere, profil

### Backend
- **Dashboard Admin** (`/admin/dashboard.php`) - Statistici și gestionare

### Pagini Legale
- Termeni și Condiții
- Politica de Confidențialitate
- Politica Cookie
- Politica de Retur
- FAQ

## 🎨 Tehnologii Utilizate

- **PHP** - Backend și logică aplicație
- **MySQL** - Bază de date
- **Bootstrap 5.3** - Framework CSS
- **Bootstrap Icons** - Iconițe
- **Google Fonts** (Poppins) - Tipografie
- **JavaScript** - Interactivitate frontend

## 🔐 Securitate

- Parole criptate cu `password_hash()`
- Validare și sanitizare input-uri
- Protecție împotriva SQL injection (prepared statements)
- Protecție împotriva XSS (htmlspecialchars)
- Sesiuni PHP securizate

## 🛠️ Funcționalități Viitoare (Opțional)

- [ ] Sistem coș de cumpărături complet
- [ ] Integrare gateway plată (stripe/PayPal)
- [ ] Sistem de review-uri produse
- [ ] Wishlist pentru utilizatori
- [ ] Export rapoarte admin (CSV/PDF)
- [ ] Multi-language support
- [ ] Email notificări automate
- [ ] Optimizare SEO avansată

## 📱 Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Opera (latest)

## 📧 Contact

Pentru întrebări sau suport:
- **Email:** contact@brodero.online
- **Telefon:** 0741133343

## 📝 Licență

Acest proiect este creat pentru uz educațional și comercial. Toate drepturile rezervate © 2022-2025 Brodero.

---

**Creat cu ❤️ pentru comunitatea de broderie**

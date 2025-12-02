# 📘 Documentație Tehnică - Brodero

## Arhitectură Aplicație

### Pattern MVC Simplificat

Brodero folosește o arhitectură simplificată inspirată din pattern-ul MVC:

```
┌─────────────┐
│   Browser   │
└──────┬──────┘
       │
       v
┌─────────────────────────────┐
│     index.php / pages/      │  ← Controller Layer
│  (Logică și procesare)      │
└──────────┬──────────────────┘
           │
           v
    ┌──────┴──────┐
    │             │
    v             v
┌────────┐   ┌────────────┐
│  View  │   │   Model    │
│ (HTML) │   │ (Database) │
└────────┘   └────────────┘
```

## Structura Bazei de Date

### Tabele Principale

#### 1. `users` - Utilizatori
```sql
- id (PK)
- username
- email (UNIQUE)
- password (hashed)
- first_name, last_name
- phone
- role (user/admin)
- created_at, updated_at
```

#### 2. `products` - Produse
```sql
- id (PK)
- category_id (FK)
- name, slug (UNIQUE)
- description
- price, sale_price
- image, gallery (JSON)
- file_path
- is_featured, is_active
- stock_status
- views
- created_at, updated_at
```

#### 3. `orders` - Comenzi
```sql
- id (PK)
- user_id (FK)
- order_number (UNIQUE)
- total_amount
- status (pending/processing/completed/cancelled)
- payment_status (unpaid/paid/refunded)
- payment_method
- notes
- created_at, updated_at
```

#### 4. `order_items` - Detalii Comenzi
```sql
- id (PK)
- order_id (FK)
- product_id (FK)
- product_name
- price, quantity
- subtotal
```

### Relații între Tabele

```
users (1) ──< (N) orders
products (1) ──< (N) order_items
orders (1) ──< (N) order_items
categories (1) ──< (N) products
```

## Configurări Importante

### config/config.php

Constante globale și funcții helper:

```php
// Database
DB_HOST, DB_USER, DB_PASS, DB_NAME

// Site
SITE_NAME, SITE_URL, SITE_EMAIL, SITE_PHONE

// Upload
MAX_FILE_SIZE (5MB)
ALLOWED_EXTENSIONS

// Pagination
PRODUCTS_PER_PAGE (12)

// Social Media
FACEBOOK_URL, INSTAGRAM_URL, etc.
```

### Funcții Helper Globale

```php
isLoggedIn()           // Verifică autentificare
isAdmin()              // Verifică rol admin
redirect($url)         // Redirect helper
cleanInput($data)      // Sanitizare input
setMessage()           // Setare mesaj sesiune
getMessage()           // Obține mesaj sesiune
```

## Flow-uri Importante

### 1. Autentificare Utilizator

```
┌─────────────┐
│ Login Form  │
└──────┬──────┘
       │
       v
┌──────────────────┐
│  Validare Input  │
└──────┬───────────┘
       │
       v
┌──────────────────┐
│  Query Database  │
└──────┬───────────┘
       │
       v
┌─────────────────────┐
│ Verificare Parolă   │
│ (password_verify)   │
└──────┬──────────────┘
       │
       v
┌─────────────────────┐
│  Creare Sesiune     │
│  - user_id          │
│  - user_email       │
│  - user_role        │
└──────┬──────────────┘
       │
       v
┌─────────────────┐
│  Redirect cont  │
└─────────────────┘
```

### 2. Afișare Produse cu Filtrare

```
┌──────────────────┐
│  GET Parameters  │
│  - category      │
│  - search        │
│  - min/max price │
│  - sort          │
│  - page          │
└────────┬─────────┘
         │
         v
┌──────────────────────┐
│  Build WHERE Clause  │
│  (dynamic query)     │
└────────┬─────────────┘
         │
         v
┌──────────────────────┐
│  Count Total Items   │
└────────┬─────────────┘
         │
         v
┌──────────────────────┐
│  Calculate Pages     │
└────────┬─────────────┘
         │
         v
┌──────────────────────┐
│  Fetch Products      │
│  (LIMIT/OFFSET)      │
└────────┬─────────────┘
         │
         v
┌──────────────────────┐
│  Display Grid +      │
│  Pagination          │
└──────────────────────┘
```

### 3. Upload Fișiere (Contact)

```
┌──────────────────┐
│  Form Submit     │
│  (multipart)     │
└────────┬─────────┘
         │
         v
┌──────────────────────┐
│  Validare Input      │
└────────┬─────────────┘
         │
         v
┌──────────────────────┐
│  Loop prin fișiere   │
└────────┬─────────────┘
         │
         v
┌──────────────────────┐
│  Validare fiecare    │
│  - size              │
│  - extension         │
└────────┬─────────────┘
         │
         v
┌──────────────────────┐
│  Move to uploads/    │
│  (unique filename)   │
└────────┬─────────────┘
         │
         v
┌──────────────────────┐
│  Save paths în DB    │
│  (JSON array)        │
└────────┬─────────────┘
         │
         v
┌──────────────────────┐
│  Success message     │
└──────────────────────┘
```

## Securitate

### 1. Protecție SQL Injection

**❌ GREȘIT:**
```php
$query = "SELECT * FROM users WHERE email = '$email'";
```

**✅ CORECT:**
```php
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
```

### 2. Protecție XSS

**❌ GREȘIT:**
```php
echo $user_input;
```

**✅ CORECT:**
```php
echo htmlspecialchars($user_input);
```

### 3. Password Hashing

**❌ GREȘIT:**
```php
// Nu stoca parole plain text sau MD5!
```

**✅ CORECT:**
```php
$hashed = password_hash($password, PASSWORD_DEFAULT);
password_verify($password, $hashed);
```

### 4. Session Security

```php
session_start();
// Regenerare session ID la login
session_regenerate_id(true);

// Verificare timeout
if (time() - $_SESSION['last_activity'] > 1800) {
    session_destroy();
}
```

## Optimizări Performanță

### 1. Database Indexing

```sql
-- Indexuri importante
INDEX idx_email ON users(email)
INDEX idx_category ON products(category_id)
INDEX idx_slug ON products(slug)
INDEX idx_featured ON products(is_featured)
```

### 2. Lazy Loading Imagini

```javascript
const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
        }
    });
});
```

### 3. CSS/JS Minification

Pentru producție:
- Minify CSS/JS
- Combine files
- Use CDN pentru Bootstrap

### 4. Database Connection Pooling

```php
// Singleton pattern pentru DB connection
class Database {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
}
```

## Debugging și Logging

### Development Mode

În `config/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Production Mode

```php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');
```

## Testare

### Checklist Testare

- [ ] Autentificare/Înregistrare
- [ ] CRUD produse (admin)
- [ ] Filtrare și sortare magazin
- [ ] Formular contact cu upload
- [ ] Responsive design (mobil/tablet)
- [ ] Cross-browser compatibility
- [ ] Validare formulare
- [ ] Mesaje eroare/succes
- [ ] Securitate (SQL injection, XSS)
- [ ] Performanță (loading time)

## Deployment

### Pre-deployment Checklist

1. ✅ Schimbați parola admin
2. ✅ Actualizați `SITE_URL` în config
3. ✅ Activați HTTPS
4. ✅ Setați `display_errors = 0`
5. ✅ Configurați backup database
6. ✅ Verificați permisiuni fișiere
7. ✅ Testați în environment producție
8. ✅ Configurați SSL certificate
9. ✅ Optimizați imagini
10. ✅ Testați toate funcționalitățile

### Server Requirements

```
PHP >= 7.4
MySQL >= 5.7
Apache/Nginx
mod_rewrite (Apache)
SSL Certificate
```

## Întreținere

### Backup Database

```bash
# Manual backup
mysqldump -u root -p brodero_db > backup.sql

# Restore
mysql -u root -p brodero_db < backup.sql
```

### Monitorizare

- Log files PHP errors
- Monitor database queries (slow queries)
- Track user activity
- Monitor disk space (uploads/)

## Contribuții și Extinderi

Structura modulară permite adăugarea ușoară de:

- Noi metode de plată
- Sistem review-uri
- Wishlist
- Comparare produse
- Multi-language
- API REST
- Email marketing integration

---

**Document actualizat:** <?php echo date('d.m.Y'); ?>

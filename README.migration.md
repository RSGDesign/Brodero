# Brodero E-commerce - Laravel Migration

## 🎯 Migrare completă de la Node.js/Express/SQLite la Laravel 9

### ✅ Ce a fost migrat

**Backend:**
- ✅ Modele Eloquent (User, Product, Category, Coupon, Cart, CartItem, Order, OrderItem, ProductFile, Page, Setting, Newsletter)
- ✅ Relații: belongsTo, hasMany, hasOne între entități
- ✅ Controllere pentru shop public (ProductController)
- ✅ Cart & Checkout logic (CartController, CheckoutController)
- ✅ Admin CRUD (ProductController, CategoryController, CouponController, OrderController)
- ✅ Middleware admin pentru protecție rute
- ✅ Autentificare Laravel Breeze (login, register, password reset)
- ✅ Sistem cupoane (aplicare, validare, incrementare utilizări)
- ✅ Free order logic (comenzi cu total 0 după discount)
- ✅ Transfer bancar & plată card Stripe
- ✅ Webhook Stripe pentru finalizare comenzi
- ✅ Migrations pentru toate tabelele

**Frontend:**
- ✅ Layout Blade reutilizabil (shop.blade.php, app.blade.php)
- ✅ Shop index cu listare produse & paginare
- ✅ Product detail page
- ✅ Cart cu update cantitate, aplicare cupon, ștergere articole
- ✅ Checkout cu formular customer details și metodă plată
- ✅ Success page (free/transfer/card)
- ✅ Admin dashboard cu meniuri către CRUD-uri
- ✅ Admin views: products, categories, coupons, orders (list, create, edit, show)

---

## 🚀 Setup inițial

### Cerințe
- PHP 8.0+
- Composer
- MySQL (XAMPP)
- Node.js & npm

### Pași instalare

```powershell
# 1. Clonează sau navighează în folder
cd "C:\Users\PC\Desktop\brodero site\laravel-app"

# 2. Instalează dependențe PHP
composer install

# 3. Instalează dependențe Node.js
npm install

# 4. Configurare .env
# Verifică că DB_DATABASE=brodero, DB_USERNAME=root, DB_PASSWORD=
# Adaugă STRIPE_SECRET_KEY și STRIPE_WEBHOOK_SECRET

# 5. Generează application key (deja făcut)
php artisan key:generate

# 6. Rulează migrations (deja făcut)
php artisan migrate

# 7. Construiește assets
npm run dev

# 8. Pornește serverul
php artisan serve
```

---

## 📊 Structura bazei de date

- **users**: utilizatori (role: admin/customer, reset_token, reset_expires)
- **categories**: categorii produse (name, slug)
- **products**: produse (title, description, category_id, price_cents, image_url, is_published)
- **coupons**: cupoane (code, type, value, expires_at, active, max_uses, uses_count, min_order_value)
- **carts**: coșuri utilizatori (user_id, coupon_code, discount_cents)
- **cart_items**: articole în coș (cart_id, product_id, quantity, price_cents_snapshot)
- **orders**: comenzi (user_id, total_cents, payment_method, status, customer_name, customer_email, customer_phone, notes)
- **order_items**: articole comandă (order_id, product_id, quantity, price_cents_snapshot)
- **product_files**: fișiere descărcabile (product_id, filename, original_name, filesize)
- **pages**: pagini statice (title, slug, content, is_published)
- **settings**: configurări site (key, value)
- **newsletter**: abonați newsletter (email, subscribed_at)

---

## 🔐 Autentificare & roluri

**Breeze routes:**
- `/login` - Autentificare
- `/register` - Înregistrare
- `/forgot-password` - Recuperare parolă

**Roluri:**
- `customer` (default) - accesează shop, cart, checkout
- `admin` - accesează `/admin/*` (CRUD produse, categorii, cupoane, comenzi)

**Creare admin:**
```php
// În tinker sau seeder
use App\Models\User;
User::create([
    'name' => 'Admin',
    'email' => 'admin@brodero.ro',
    'password' => bcrypt('password'),
    'role' => 'admin'
]);
```

---

## 🛒 Fluxuri principale

### 1. Shopping (public + autentificat)
- `/shop` - listare produse
- `/products/{id}` - detalii produs
- POST `/cart/add` - adaugă în coș (necesită auth)

### 2. Cart & Coupon
- `/cart` - vizualizare coș
- PATCH `/cart/items/{id}` - update cantitate
- DELETE `/cart/items/{id}` - ștergere articol
- POST `/cart/coupon` - aplicare cupon (validare tip, expires_at, max_uses, min_order_value)
- DELETE `/cart/coupon` - elimină cupon

### 3. Checkout
- `/checkout` - formular customer details + metodă plată
- POST `/checkout` - procesare comandă:
  - Total = 0 → free order (status: paid)
  - Transfer → pending order
  - Card → Stripe session redirect

### 4. Stripe Integration
- **Session create**: line_items cu preț snapshot, metadata cu customer info & coupon
- **Webhook** (`/webhook/stripe`): checkout.session.completed → creează order, incrementă coupon usage, șterge cart
- **Success**: `/checkout/success?session_id=...` sau `?order=...`

### 5. Admin Panel
- `/admin/products` - CRUD produse
- `/admin/categories` - CRUD categorii
- `/admin/coupons` - CRUD cupoane
- `/admin/orders` - listare comenzi, update status

---

## 🎨 Customizare frontend

Layout principal: `resources/views/layouts/shop.blade.php`
- Header cu nav (Shop, Coș, Admin, Login/Logout)
- Main content area cu @yield('content')
- Footer

**Stiluri inline** în layout pentru MVP; pentru producție recomand:
- Tailwind CSS (deja inclus în Breeze) sau
- CSS custom în `resources/css/app.css`

---

## 🔧 Configurare Stripe

1. Obține Stripe keys de la [dashboard.stripe.com](https://dashboard.stripe.com/test/apikeys)
2. Adaugă în `.env`:
```env
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```
3. Configurează webhook endpoint în Stripe dashboard: `https://your-domain.com/webhook/stripe`
4. Event: `checkout.session.completed`

---

## 📝 TODO pentru producție

- [ ] Email notifications (comanda plasată, status update) via Laravel Mail
- [ ] File uploads pentru product images (Storage + symbolic link)
- [ ] Filtre avansate shop (category, price range, search)
- [ ] Paginare & sorting în admin
- [ ] User dashboard (istoric comenzi, date cont)
- [ ] Traduceri (Laravel localization)
- [ ] Rate limiting pentru API
- [ ] HTTPS & deployment (Laravel Forge, Vapor, sau VPS)
- [ ] Testing (PHPUnit, Feature tests pentru checkout flow)
- [ ] Cache (Redis pentru session & query cache)

---

## 🐛 Debugging

**Verifică logs:**
```powershell
cat storage/logs/laravel.log
```

**Dacă apar erori de permisiuni:**
```powershell
# Windows (Git Bash sau WSL)
chmod -R 775 storage bootstrap/cache
```

**Clear cache:**
```powershell
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

**Regenerează autoload:**
```powershell
composer dump-autoload
```

---

## 📚 Resurse

- [Laravel Documentation](https://laravel.com/docs/9.x)
- [Laravel Breeze](https://laravel.com/docs/9.x/starter-kits#laravel-breeze)
- [Stripe PHP SDK](https://stripe.com/docs/api)
- [Eloquent ORM](https://laravel.com/docs/9.x/eloquent)

---

## 🎉 Status migrare

**Finalizat:** Database, Models, Controllers, Routes, Blade templates, Breeze auth, Stripe integration, Admin CRUD.

**Aplicația este funcțională** și poate fi testată local la `http://127.0.0.1:8000`.

Pentru întrebări sau îmbunătățiri, contactează echipa de dezvoltare.

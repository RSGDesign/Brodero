# 🔐 Gestionare Secret Keys - Brodero

## 📋 Prezentare generală

Acest sistem asigură gestionarea securizată a cheilor secrete (API keys, tokens, parole) fără a le include în Git.

## 🏗️ Structură

```
Brodero/
├── includes/
│   ├── config.example.php    ✅ Template (în Git)
│   └── config.local.php       🔒 Secrete (NU în Git)
├── config/
│   └── config.php             ✅ Încarcă config.local.php
└── .gitignore                 ✅ Exclude config.local.php
```

## 🚀 Setup inițial

### 1. Pe mediul local (development)

```bash
cd includes/
cp config.example.php config.local.php
```

Editează `config.local.php` cu valorile reale:

```php
return [
    'database' => [
        'host'     => 'localhost',
        'user'     => 'your_user',
        'password' => 'your_password',
        'name'     => 'your_database',
    ],
    'stripe' => [
        'secret_key'      => 'sk_test_YOUR_REAL_KEY',
        'publishable_key' => 'pk_test_YOUR_REAL_KEY',
    ],
    'analytics' => [
        'ga4_measurement_id' => 'G-YOUR_ID',
    ],
];
```

### 2. Pe server (Hostinger)

**Prin SSH:**

```bash
cd /home/u107933880/domains/brodero.online/public_html/includes/
cp config.example.php config.local.php
nano config.local.php  # Editează cu valorile reale
```

**Prin File Manager (Hostinger):**

1. Accesează File Manager
2. Navighează la `public_html/includes/`
3. Copiază `config.example.php` → `config.local.php`
4. Editează `config.local.php` cu valorile reale

## ✅ Verificare funcționare

După setup, verifică:

```php
// config.local.php este încărcat corect
var_dump(DB_HOST);           // localhost
var_dump(STRIPE_SECRET_KEY); // sk_test_...
var_dump(GA4_MEASUREMENT_ID); // G-...
```

## 🔄 Auto Deploy Hostinger

### Workflow automat:

1. **Modifici codul local** → commit → push la GitHub
2. **Hostinger** detectează push-ul
3. **Auto deploy** face `git pull`
4. **config.local.php** rămâne neatins (nu e în Git)
5. **Site funcționează** cu secretele salvate pe server

### ⚠️ IMPORTANT:

- `config.local.php` trebuie creat **MANUAL** pe server **O SINGURĂ DATĂ**
- Nu va fi suprascris de auto deploy (e în `.gitignore`)
- Dacă ștergi accidental, recreează-l din `config.example.php`

## 📝 Adăugare secret nou

### 1. Actualizează template-ul (versionat):

```php
// includes/config.example.php
'new_service' => [
    'api_key' => 'YOUR_API_KEY_HERE',
],
```

### 2. Actualizează config.local.php (local + server):

```php
// includes/config.local.php
'new_service' => [
    'api_key' => 'actual_key_value_12345',
],
```

### 3. Definește constanta în config.php:

```php
// config/config.php
define('NEW_SERVICE_API_KEY', $localConfig['new_service']['api_key'] ?? '');
```

### 4. Folosește în aplicație:

```php
if (!empty(NEW_SERVICE_API_KEY)) {
    // Folosește serviciul
} else {
    // Fallback sau eroare
}
```

## 🛡️ Securitate

### Ce E în Git (public):
- ✅ `config.example.php` - template cu placeholder-uri
- ✅ `config.php` - logica de încărcare
- ✅ `.gitignore` - exclude secretele

### Ce NU E în Git (privat):
- 🔒 `config.local.php` - valorile reale
- 🔒 Parole, API keys, tokens

## 🔍 Debugging

Dacă aplicația nu găsește `config.local.php`:

```php
// În config.php:
define('DEBUG_MODE', true); // temporar

// Eroare afișată:
// "ERROR: config.local.php missing. Copy config.example.php..."
```

**Rezolvare:**
```bash
cp includes/config.example.php includes/config.local.php
# Editează cu valorile corecte
```

## 📚 Alte medii

### Staging/Testing:

Creează `config.local.php` cu credențiale de test:

```php
'database' => [
    'host' => 'localhost',
    'user' => 'staging_user',
    'name' => 'staging_db',
],
'stripe' => [
    'secret_key' => 'sk_test_...', // Test mode
],
```

### Producție:

Folosește credențiale de producție:

```php
'stripe' => [
    'secret_key' => 'sk_live_...', // Live mode
],
'environment' => [
    'debug_mode' => false,
    'display_errors' => false,
],
```

## ⚙️ Variabile disponibile

| Constantă | Descriere | Fallback |
|-----------|-----------|----------|
| `DB_HOST` | Host bază de date | - |
| `DB_USER` | User bază de date | - |
| `DB_PASS` | Parolă bază de date | - |
| `DB_NAME` | Nume bază de date | - |
| `STRIPE_SECRET_KEY` | Stripe secret key | `''` |
| `STRIPE_PUBLISHABLE_KEY` | Stripe publishable key | `''` |
| `GA4_MEASUREMENT_ID` | Google Analytics 4 ID | `''` |
| `DEBUG_MODE` | Mod debugging | `false` |

## 🆘 Suport

Dacă întâmpini probleme:

1. Verifică că `config.local.php` există
2. Verifică permisiunile fișierului (644)
3. Verifică sintaxa PHP (`php -l config.local.php`)
4. Activează debug mode temporar

---

**Creat:** 2026-01-07  
**Ultima actualizare:** 2026-01-07

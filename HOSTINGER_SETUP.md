# 🚀 Setup rapid config.local.php pe Hostinger

## Via SSH (Recomandat)

```bash
# Conectează-te la SSH
ssh u107933880@brodero.online

# Navighează la directorul includes
cd /home/u107933880/domains/brodero.online/public_html/includes/

# Creează config.local.php din template
cp config.example.php config.local.php

# Editează fișierul
nano config.local.php
```

### Editează valorile:

```php
return [
    'database' => [
        'host'     => 'localhost',
        'user'     => 'u107933880_brodero',
        'password' => 'Grasul1500!',
        'name'     => 'u107933880_brodero',
    ],
    
    'stripe' => [
        'secret_key'      => 'sk_live_YOUR_REAL_KEY',  // Completează cu cheia ta
        'publishable_key' => 'pk_live_YOUR_REAL_KEY',
    ],
    
    'analytics' => [
        'ga4_measurement_id' => 'G-YOUR_MEASUREMENT_ID',
    ],
    
    'environment' => [
        'debug_mode'     => false,  // false pe producție!
        'display_errors' => false,  // false pe producție!
    ],
];
```

**Salvează:** Ctrl+O → Enter → Ctrl+X

## Via File Manager (Alternativ)

1. Login la Hostinger → hPanel
2. File Manager
3. Navighează: `public_html/includes/`
4. Click dreapta pe `config.example.php` → Copy
5. Redenumește copia în `config.local.php`
6. Click dreapta pe `config.local.php` → Edit
7. Completează valorile reale
8. Save

## ✅ Verificare

Accesează: `https://brodero.online`

Dacă funcționează → Config OK! ✅

Dacă vezi eroare → Verifică:
- Sintaxa PHP în config.local.php
- Permisiuni (644)
- Credențiale DB corecte

## 🔄 După fiecare deploy automat

**NU trebuie să faci nimic!**

- config.local.php NU e suprascris (e în .gitignore)
- Secretele rămân pe server
- Auto deploy actualizează doar codul din Git

## 🔑 Unde găsești cheile

**Stripe Keys:**
- Login: https://dashboard.stripe.com
- Developers → API keys
- Copiază Secret key (sk_live_...)

**GA4 Measurement ID:**
- Login: https://analytics.google.com
- Admin → Data Streams
- Web stream → Measurement ID (G-...)

---

**ATENȚIE:** NU adăuga config.local.php în Git!

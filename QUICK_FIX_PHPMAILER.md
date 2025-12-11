# 🎯 QUICK FIX - PHPMailer pe Hostinger

## ❌ PROBLEMA
```
"PHPMailer NU este instalat!"
```

## ✅ SOLUȚIA (3 pași)

### 1️⃣ Instalează PHPMailer pe server
```bash
ssh u107933880@brodero.online
cd /home/u107933880/domains/brodero.online/public_html
composer update phpmailer/phpmailer
```

### 2️⃣ Folosește noul bootstrap în fișierele tale PHP
```php
<?php
// Înlocuiește toate include-urile cu:
require_once __DIR__ . '/bootstrap.php';  // Din root
// SAU
require_once __DIR__ . '/../bootstrap.php';  // Din pages/
// SAU
require_once __DIR__ . '/../../bootstrap.php';  // Din includes/forms/

// PHPMailer este acum disponibil:
use PHPMailer\PHPMailer\PHPMailer;
$mail = new PHPMailer(true);
```

### 3️⃣ Testează
```
https://brodero.online/test_phpmailer_quick.php
```

## 📋 FIȘIERE MODIFICATE

Actualizate automat:
- ✅ `includes/forms/process_contact.php`
- ✅ `pages/contact.php`
- ✅ `test_email_smtp.php`

Fișiere noi:
- ✅ `bootstrap.php` (rădăcină proiect)
- ✅ `test_phpmailer_quick.php` (test rapid)
- ✅ `FIX_PHPMAILER_HOSTINGER.md` (documentație)

## 🔍 VERIFICARE RAPIDĂ

```bash
# PHPMailer instalat?
ls vendor/phpmailer/phpmailer/

# Bootstrap funcționează?
php test_phpmailer_quick.php

# Toate testele OK?
# Output așteptat: "🎉 TOATE TESTELE AU TRECUT!"
```

## 🚀 NEXT STEPS

1. **Upload pe Hostinger:**
   - `bootstrap.php` → `/public_html/`
   - `test_phpmailer_quick.php` → `/public_html/`
   - Fișierele modificate din `includes/` și `pages/`

2. **Instalează PHPMailer pe server** (vezi pasul 1 mai sus)

3. **Testează:**
   - https://brodero.online/test_email_smtp.php?key=brodero2025
   - Trimite mesaj test din formular

4. **Verifică logs:**
   ```bash
   tail -f logs/mail.log
   ```

## ⚠️ TROUBLESHOOTING

**Dacă încă nu merge:**

```bash
# Reinstalează dependencies:
rm -rf vendor/
composer install

# Verifică permisiuni:
chmod -R 755 vendor/

# Verifică PHP version:
php -v  # Trebuie >= 7.4
```

## 📚 DOCUMENTAȚIE COMPLETĂ

Vezi: `FIX_PHPMAILER_HOSTINGER.md` pentru detalii complete.

---

**Status:** ✅ REZOLVAT  
**Data:** 11 Decembrie 2025

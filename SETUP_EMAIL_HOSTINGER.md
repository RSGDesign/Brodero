# 📧 Configurare Email SMTP Hostinger - Brodero Contact Form

## 📋 Cuprins
1. [Pregătire Sistem](#1-pregătire-sistem)
2. [Configurare SMTP în cPanel](#2-configurare-smtp-în-cpanel)
3. [Instalare PHPMailer](#3-instalare-phpmailer)
4. [Configurare Parola SMTP](#4-configurare-parola-smtp)
5. [Testare Sistem](#5-testare-sistem)
6. [Depanare Probleme](#6-depanare-probleme)
7. [Monitorizare și Întreținere](#7-monitorizare-și-întreținere)

---

## 1. Pregătire Sistem

### 1.1 Verificare Cerințe

Asigură-te că ai:
- ✅ PHP >= 7.4
- ✅ Composer instalat
- ✅ Extensii PHP: `openssl`, `mbstring`, `mysqli`
- ✅ Access SSH la server (opțional, dar recomandat)
- ✅ Access cPanel Hostinger

### 1.2 Structura Fișierelor

```
/home/u107933880/domains/brodero.online/public_html/
├── config/
│   ├── config.php
│   ├── database.php
│   └── smtp_config.php          ← Configurare SMTP
├── includes/
│   └── forms/
│       └── process_contact.php   ← Engine PHPMailer
├── pages/
│   └── contact.php               ← Formular contact
├── logs/
│   └── mail.log                  ← Log-uri email (creat automat)
├── vendor/                       ← Composer dependencies (incluzând PHPMailer)
├── composer.json
├── test_email_smtp.php           ← Script testare (șterge după test!)
└── SETUP_EMAIL_HOSTINGER.md      ← Acest document
```

---

## 2. Configurare SMTP în cPanel

### 2.1 Accesare cPanel Hostinger

1. Accesează: https://hpanel.hostinger.com/
2. Login cu contul tău Hostinger
3. Selectează domeniul **brodero.online**

### 2.2 Creare/Verificare Cont Email

**Pasul 1:** În cPanel, găsește secțiunea **Email Accounts**

**Pasul 2:** Verifică dacă există emailul `contact@brodero.online`
- Dacă **NU există**, click pe **"Create Email Account"**
  - Email: `contact`
  - Domeniu: `brodero.online`
  - Parolă: **creează o parolă PUTERNICĂ** (minim 12 caractere, litere+cifre+simboluri)
  - Quota: **Unlimited** sau minim 500 MB
  - Click **"Create"**

**Pasul 3:** Notează parola (o vei folosi în configurare!)

### 2.3 Obținere Detalii SMTP

Hostinger folosește următoarele setări SMTP standard:

```
SMTP Host:     smtp.hostinger.com
SMTP Port:     465 (SSL) sau 587 (TLS)
SMTP Secure:   ssl (pentru 465) sau tls (pentru 587)
SMTP Username: contact@brodero.online (EMAIL COMPLET)
SMTP Password: [parola creată la pasul 2.2]
```

**⚠️ IMPORTANT:** 
- Username-ul SMTP TREBUIE să fie **emailul complet**, NU doar `contact`
- Recomandare: Folosește portul **465 cu SSL** (mai stabil)

### 2.4 Configurare SPF și DKIM (Opțional dar Recomandat)

Pentru a evita ca emailurile să ajungă în spam:

**SPF Record:**
1. În cPanel → **Zone Editor**
2. Găsește `brodero.online`
3. Adaugă TXT record:
   ```
   v=spf1 include:_spf.hostinger.com ~all
   ```

**DKIM:**
1. În cPanel → **Email Deliverability**
2. Click pe `brodero.online`
3. Verifică DKIM status
4. Dacă nu este activat, click **"Install DKIM Keys"**

---

## 3. Instalare PHPMailer

### 3.1 Via SSH (Recomandat)

```bash
# 1. Conectare SSH
ssh u107933880@brodero.online

# 2. Navigare la directorul site-ului
cd /home/u107933880/domains/brodero.online/public_html

# 3. Instalare/Update PHPMailer
composer update

# 4. Verificare instalare
ls -la vendor/phpmailer/phpmailer/
```

### 3.2 Via File Manager (Alternativă)

Dacă nu ai acces SSH:

1. Accesează **File Manager** în cPanel
2. Navighează la `/home/u107933880/domains/brodero.online/public_html`
3. Click dreapta pe `composer.json` → **Edit**
4. Verifică că există linia:
   ```json
   "phpmailer/phpmailer": "^6.9"
   ```
5. Rulează Composer prin **Terminal** în cPanel:
   ```bash
   cd domains/brodero.online/public_html && composer update
   ```

### 3.3 Verificare Instalare

```bash
php -r "require 'vendor/autoload.php'; echo class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? 'PHPMailer OK' : 'PHPMailer missing'; echo PHP_EOL;"
```

Rezultat așteptat: `PHPMailer OK`

---

## 4. Configurare Parola SMTP

### 4.1 Editare Fișier Configurare

**Via SSH:**
```bash
nano config/smtp_config.php
```

**Via File Manager:**
1. Navighează la `config/smtp_config.php`
2. Click dreapta → **Edit**

### 4.2 Setare Parolă

Găsește linia:
```php
define('SMTP_PASSWORD', 'PUNE_PAROLA_AICI');
```

Înlocuiește cu parola reală:
```php
define('SMTP_PASSWORD', 'parolaTA_secretă_2025');
```

**⚠️ ATENȚIE:** 
- NU adăuga spații înainte/după parolă
- Păstrează ghilimelele `'...'`
- Nu publica acest fișier pe GitHub! (va fi adăugat în `.gitignore`)

### 4.3 (Opțional) Modificare Port/Secure

Dacă portul 465 (SSL) NU funcționează, schimbă cu 587 (TLS):

```php
// Schimbă de la:
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl');

// La:
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
```

### 4.4 Setare Permisiuni

```bash
chmod 644 config/smtp_config.php
```

---

## 5. Testare Sistem

### 5.1 Testare Automată

**Pasul 1:** Accesează scriptul de test:
```
https://brodero.online/test_email_smtp.php?key=brodero2025
```

**Pasul 2:** Verifică rezultatele:
- ✅ **Test 1:** Configurare SMTP - trebuie să fie verde
- ✅ **Test 2:** PHPMailer - trebuie să fie instalat
- ✅ **Test 3:** Extensii PHP - toate verzi
- ✅ **Test 4:** Director Logs - writable

**Pasul 3:** Click pe **"📧 Trimite Email de Test"**

**Pasul 4:** Verifică inbox-ul la `contact@brodero.online`

### 5.2 Testare Formular Contact

1. Accesează: https://brodero.online/pages/contact.php
2. Completează formularul cu date reale
3. Trimite mesajul
4. Verifică:
   - Mesaj de succes: **"Mesajul tău a fost trimis cu succes!"**
   - Email primit în inbox
   - Log în `logs/mail.log`

### 5.3 Verificare Logs

```bash
tail -f logs/mail.log
```

Exemplu output corect:
```
[2025-01-15 14:32:15] [INFO] [178.45.23.109] Începe trimiterea email pentru: contact@brodero.online
[2025-01-15 14:32:17] [SUCCESS] [178.45.23.109] Email trimis cu succes prin SMTP către: contact@brodero.online
[2025-01-15 14:32:17] [INFO] [178.45.23.109] Mesaj salvat în DB cu ID: 42
```

### 5.4 Testare Rate Limiting

Trimite **6 mesaje rapid** (sub 1 oră):
- Primele 5 → Succes
- Al 6-lea → Eroare: **"Ai depășit limita de 5 mesaje pe oră"**

### 5.5 Testare Protecție Anti-Spam

**Test Honeypot:**
1. Deschide Developer Tools (F12)
2. Găsește câmpul ascuns:
   ```html
   <input type="text" name="website" value="" style="display:none">
   ```
3. Schimbă `style="display:block"` și completează câmpul
4. Trimite formularul
5. Verifică că:
   - Apare mesaj de "succes" (fals)
   - În `logs/mail.log` apare: `[WARNING] Honeypot triggered`
   - Email NU este trimis

**Test CSRF:**
1. Deschide formularul în 2 tab-uri
2. În tab 1: trimite formular (token valid)
3. În tab 2: încearcă să trimiți (token expirat)
4. Tab 2 → Eroare: **"Token de securitate invalid"**

---

## 6. Depanare Probleme

### 6.1 Eroare: "SMTP connect() failed"

**Cauze posibile:**
1. Parolă SMTP incorectă
2. Port/secure greșit
3. Firewall blochează portul

**Soluții:**

**A. Verifică parola:**
```bash
nano config/smtp_config.php
# Asigură-te că SMTP_PASSWORD este corectă
```

**B. Încearcă portul alternativ:**
```php
// Dacă folosești 465 (SSL), schimbă la 587 (TLS):
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
```

**C. Test manual SMTP:**
```bash
telnet smtp.hostinger.com 465
# Sau:
telnet smtp.hostinger.com 587
```

Dacă nu se conectează → contactează suportul Hostinger.

### 6.2 Eroare: "Authentication failed"

**Cauză:** Username sau parolă greșită.

**Soluții:**

1. **Verifică username:**
   ```php
   // TREBUIE să fie email COMPLET:
   define('SMTP_USERNAME', 'contact@brodero.online'); // CORECT
   // NU doar:
   define('SMTP_USERNAME', 'contact'); // GREȘIT!
   ```

2. **Resetează parola email:**
   - cPanel → Email Accounts
   - Găsește `contact@brodero.online`
   - Click **"Change Password"**
   - Setează parolă nouă
   - Actualizează în `smtp_config.php`

### 6.3 Emailurile Ajung în Spam

**Soluții:**

1. **Configurează SPF/DKIM** (vezi secțiunea 2.4)

2. **Verifică scoring spam:**
   - Trimite email de test la: https://www.mail-tester.com/
   - Urmează recomandările

3. **Evită cuvinte spam:**
   - NU folosi: "URGENT", "CLICK HERE", prea multe CAPS
   - Menține un raport text/HTML echilibrat

4. **Folosește "Reply-To" corect:**
   Deja configurat în `smtp_config.php`:
   ```php
   define('SMTP_REPLY_TO', 'contact@brodero.online');
   ```

### 6.4 Eroare: "Could not open socket"

**Cauză:** Extensia OpenSSL lipsește sau firewall.

**Soluții:**

1. **Verifică OpenSSL:**
   ```bash
   php -m | grep openssl
   ```
   Dacă nu apare → contactează Hostinger support pentru activare.

2. **Verifică firewall:**
   ```bash
   # Test port 465:
   nc -zv smtp.hostinger.com 465
   # Test port 587:
   nc -zv smtp.hostinger.com 587
   ```

### 6.5 Eroare: "Class PHPMailer not found"

**Cauză:** PHPMailer nu este instalat sau autoload nu funcționează.

**Soluții:**

1. **Reinstalare PHPMailer:**
   ```bash
   cd /home/u107933880/domains/brodero.online/public_html
   rm -rf vendor/
   composer install
   ```

2. **Verifică autoload:**
   ```bash
   ls -la vendor/autoload.php
   ```

3. **Verifică require în cod:**
   ```php
   require_once __DIR__ . '/../vendor/autoload.php';
   ```

### 6.6 Logs Nu Se Creează

**Cauză:** Permisiuni directoare greșite.

**Soluții:**

```bash
# Creează director logs:
mkdir -p logs

# Setează permisiuni:
chmod 755 logs/

# Testează scriere:
echo "Test" > logs/test.txt
cat logs/test.txt
rm logs/test.txt
```

### 6.7 Fallback la Database Funcționează, dar SMTP Nu

**Cauză:** SMTP failure, dar fallback salvează în DB.

**Cum identifici:**
- Mesaj utilizator: **"Mesajul tău a fost salvat! Te vom contacta în curând."** (galben)
- În `logs/mail.log`:
  ```
  [ERROR] [IP] SMTP Error: Could not connect to SMTP host.
  [INFO] [IP] Fallback: mesaj salvat în DB cu ID: 123
  ```

**Soluții:**
1. Verifică toate soluțiile de la 6.1 - 6.5
2. Contactează Hostinger support pentru verificare SMTP server
3. Între timp, mesajele sunt salvate în DB - poți verifica în phpMyAdmin:
   ```sql
   SELECT * FROM contact_messages WHERE status = 'pending_email';
   ```

---

## 7. Monitorizare și Întreținere

### 7.1 Verificare Logs Regulată

**Rulează zilnic:**
```bash
tail -n 100 logs/mail.log | grep ERROR
```

**Rotire logs (lunar):**
```bash
mv logs/mail.log logs/mail_$(date +%Y-%m).log
touch logs/mail.log
chmod 644 logs/mail.log
```

### 7.2 Backup Configurare

```bash
# Backup lunar:
tar -czf backup_config_$(date +%Y-%m-%d).tar.gz config/ includes/forms/
```

### 7.3 Verificare Mesaje Pending

**Verifică mesajele care au eșuat SMTP:**
```sql
SELECT id, name, email, subject, created_at 
FROM contact_messages 
WHERE status = 'pending_email' 
ORDER BY created_at DESC;
```

**Retrimitere manuală:**
1. Accesează phpMyAdmin
2. Copiază detaliile mesajului (id, name, email, subject, message)
3. Trimite manual email din cPanel Webmail
4. Marchează ca procesat:
   ```sql
   UPDATE contact_messages SET status = 'replied' WHERE id = 123;
   ```

### 7.4 Actualizare PHPMailer

**Verificare versiune curentă:**
```bash
composer show phpmailer/phpmailer
```

**Update la versiune nouă:**
```bash
composer update phpmailer/phpmailer
```

### 7.5 Monitorizare Rate Limiting

**Verifică statistici:**
```sql
SELECT 
    DATE(created_at) as data,
    COUNT(*) as total_mesaje,
    COUNT(DISTINCT email) as expeditori_unici
FROM contact_messages 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at);
```

**Identificare abuzatori:**
```sql
SELECT 
    email,
    COUNT(*) as total_incercari,
    MAX(created_at) as ultima_incercare
FROM contact_messages 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY email
HAVING COUNT(*) > 10
ORDER BY total_incercari DESC;
```

### 7.6 Securitate

**Verificare regulată:**

1. **Asigură-te că `smtp_config.php` NU este în Git:**
   ```bash
   cat .gitignore | grep smtp_config.php
   ```
   Dacă nu apare, adaugă:
   ```bash
   echo "config/smtp_config.php" >> .gitignore
   ```

2. **Verifică permisiuni:**
   ```bash
   ls -la config/smtp_config.php
   # Trebuie: -rw-r--r-- (644)
   ```

3. **Șterge fișiere test:**
   ```bash
   rm test_email_smtp.php
   ```

---

## 📞 Suport

### Hostinger Support
- **Email:** support@hostinger.com
- **Live Chat:** hpanel.hostinger.com (după login)
- **Knowledge Base:** https://support.hostinger.com/

### Verificare Status Server
- https://www.hostingerstatus.com/

### Documentație PHPMailer
- GitHub: https://github.com/PHPMailer/PHPMailer
- Wiki: https://github.com/PHPMailer/PHPMailer/wiki

---

## ✅ Checklist Final

Înainte de a considera sistemul **production-ready**:

- [ ] PHPMailer instalat (`composer update` rulat)
- [ ] Parola SMTP setată în `config/smtp_config.php`
- [ ] Test email trimis cu succes
- [ ] Formular contact funcționează
- [ ] Logs se creează în `logs/mail.log`
- [ ] Rate limiting funcționează (test 6 mesaje)
- [ ] Honeypot funcționează (test câmp ascuns)
- [ ] CSRF protection funcționează (test token expirat)
- [ ] SPF/DKIM configurate (opțional dar recomandat)
- [ ] `test_email_smtp.php` ȘTERS
- [ ] `smtp_config.php` adăugat în `.gitignore`
- [ ] Backup configurare realizat

---

## 📄 Fișiere Generate de Acest Setup

```
✅ config/smtp_config.php              - Configurare SMTP Hostinger
✅ includes/forms/process_contact.php  - Engine PHPMailer (rescris complet)
✅ pages/contact.php                   - Formular cu CSRF/honeypot (modificat)
✅ test_email_smtp.php                 - Script testare (ȘTERGE DUPĂ TEST!)
✅ SETUP_EMAIL_HOSTINGER.md            - Această documentație
✅ composer.json                       - Actualizat cu PHPMailer dependency
```

---

## 🎉 Finalizare

Dacă toate testele au trecut și checklist-ul este completat:

🚀 **Sistemul tău de contact este GATA pentru producție!**

Emailurile vor fi trimise prin:
- **Metoda primară:** SMTP Hostinger (fiabil, rapid)
- **Metoda secundară:** Salvare în database (fallback automat)
- **Protecție spam:** Rate limiting, CSRF, honeypot
- **Logging:** Toate operațiile înregistrate pentru debugging

**Bucură-te de formularul tău optimizat! 🎊**

---

**Autor:** GitHub Copilot (Claude Sonnet 4.5)  
**Data:** Ianuarie 2025  
**Versiune:** 1.0

# 🧪 Testare Rapidă Upload Limits

## Cum să Verifici Dacă Funcționează

### 1. Verificare Limite PHP (1 minut)

Creați fișier `check_limits.php` în folder `admin/`:

```php
<?php
// check_limits.php - Verificare limite upload
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verificare Limite PHP</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .setting { margin: 15px 0; padding: 10px; background: #f8f9fa; border-left: 4px solid #007bff; }
        .setting strong { color: #007bff; }
        .value { font-size: 1.2em; color: #28a745; font-weight: bold; }
        .warning { color: #dc3545; font-size: 0.9em; margin-top: 5px; }
        .success { color: #28a745; font-size: 0.9em; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔧 Verificare Limite Upload PHP</h1>
        
        <div class="setting">
            <strong>upload_max_filesize:</strong>
            <div class="value"><?php echo ini_get('upload_max_filesize'); ?></div>
            <?php if (ini_get('upload_max_filesize') === '100M'): ?>
                <div class="success">✅ Corect! Setat la 100MB</div>
            <?php else: ?>
                <div class="warning">⚠️ Nu e setat la 100MB. Verificați .htaccess</div>
            <?php endif; ?>
        </div>
        
        <div class="setting">
            <strong>post_max_size:</strong>
            <div class="value"><?php echo ini_get('post_max_size'); ?></div>
            <?php if (ini_get('post_max_size') === '105M'): ?>
                <div class="success">✅ Corect! Setat la 105MB</div>
            <?php else: ?>
                <div class="warning">⚠️ Nu e setat la 105MB. Verificați .htaccess</div>
            <?php endif; ?>
        </div>
        
        <div class="setting">
            <strong>max_execution_time:</strong>
            <div class="value"><?php echo ini_get('max_execution_time'); ?> secunde</div>
            <?php if (ini_get('max_execution_time') >= 300): ?>
                <div class="success">✅ Suficient pentru upload-uri mari</div>
            <?php else: ?>
                <div class="warning">⚠️ Prea mic. Recomandare: 300 secunde</div>
            <?php endif; ?>
        </div>
        
        <div class="setting">
            <strong>memory_limit:</strong>
            <div class="value"><?php echo ini_get('memory_limit'); ?></div>
            <?php
            $memLimit = ini_get('memory_limit');
            $memValue = (int)$memLimit;
            if ($memValue >= 256 || $memLimit === '-1'): ?>
                <div class="success">✅ Suficient</div>
            <?php else: ?>
                <div class="warning">⚠️ Recomandare: minim 256M</div>
            <?php endif; ?>
        </div>
        
        <div class="setting">
            <strong>max_input_time:</strong>
            <div class="value"><?php echo ini_get('max_input_time'); ?> secunde</div>
        </div>
        
        <hr style="margin: 20px 0;">
        
        <div style="background: #e7f3ff; padding: 15px; border-radius: 5px;">
            <h3 style="margin-top: 0; color: #004085;">📋 Rezumat</h3>
            <?php
            $allGood = (
                ini_get('upload_max_filesize') === '100M' &&
                ini_get('post_max_size') === '105M' &&
                ini_get('max_execution_time') >= 300
            );
            ?>
            <?php if ($allGood): ?>
                <p style="color: #28a745; font-weight: bold;">✅ Toate setările sunt corecte! Puteți încărca fișiere până la 100MB.</p>
            <?php else: ?>
                <p style="color: #dc3545; font-weight: bold;">⚠️ Unele setări trebuie ajustate. Verificați .htaccess sau php.ini</p>
                <p><strong>Pași:</strong></p>
                <ol style="margin: 10px 0; padding-left: 20px;">
                    <li>Verificați că .htaccess conține directivele php_value</li>
                    <li>Dacă sunteți pe Hostinger, verificați PHP Configuration în panel</li>
                    <li>Poate fi nevoie de restart Apache (contactați hostingul)</li>
                </ol>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="add_product.php" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">
                ← Înapoi la Adăugare Produs
            </a>
        </div>
    </div>
</body>
</html>
```

**Accesați:** `https://brodero.online/admin/check_limits.php`

---

## 2. Test Upload Practic (3 minute)

### Pas 1: Pregătiți Fișiere Test

Creați fișiere de diferite dimensiuni pentru testare:

- **Fișier Mic:** 2MB (imagine JPG)
- **Fișier Mediu:** 30MB (ZIP sau PDF)
- **Fișier Mare:** 90MB (video sau arhivă)

### Pas 2: Adăugați Produs Nou

1. Mergeți la `Admin → Produse → Adaugă Produs`
2. Completați câmpurile obligatorii:
   - Denumire: "Test Upload 100MB"
   - Preț: 99
   - Descriere: "Test pentru verificarea limitelor de upload"
   - Categorie: Orice

3. **Testați fiecare dimensiune de fișier:**

#### Test 1: Fișier Mic (2MB)
- Încărcați imaginea mică ca "Imagine Principală"
- Dați Save
- **Rezultat așteptat:** ✅ Produs salvat cu succes

#### Test 2: Fișier Mediu (30MB)
- Încărcați fișierul de 30MB la "Fișiere Descărcabile"
- Dați Save
- **Rezultat așteptat:** 
  - ✅ DUPĂ FIX: Produs salvat cu succes, fișier încărcat
  - ❌ ÎNAINTE: "Warning: Undefined array key 'name'"

#### Test 3: Fișier Mare (90MB)
- Încărcați fișierul de 90MB
- Dați Save
- **Rezultat așteptat:** ✅ Produs salvat cu succes

#### Test 4: Fișier Prea Mare (> 100MB)
- Încărcați fișier de 110MB
- Dați Save
- **Rezultat așteptat:** 
  - ⚠️ Mesaj clar: "Fișierele încărcate depășesc limita serverului (upload_max_filesize: 100M, post_max_size: 105M)"
  - ❌ NU trebuie: "Undefined array key"

---

## 3. Verificare Browser Console (1 minut)

### Chrome DevTools:

1. Deschideți pagina `add_product.php`
2. Apăsați `F12` (Developer Tools)
3. Mergeți la tab-ul **Console**
4. Încărcați un fișier mare și dați Save
5. **Nu trebuie să vedeți:**
   - "Undefined array key"
   - "trim(): Passing null is deprecated"

---

## 🎯 Rezultate Finale

| Test | Înainte de Fix | După Fix |
|------|----------------|----------|
| Upload 2MB | ✅ Funcționa | ✅ Funcționează |
| Upload 30MB | ❌ Erori "Undefined key" | ✅ Funcționează |
| Upload 90MB | ❌ Erori | ✅ Funcționează |
| Upload 110MB | ❌ Erori confuze | ⚠️ Mesaj clar despre limită |
| cleanInput(null) | ❌ Deprecated warning | ✅ Nicio eroare |

---

## ⚠️ IMPORTANT: Ștergeți După Testare

După verificare, **ștergeți fișierul `check_limits.php`** pentru securitate:

```bash
rm admin/check_limits.php
```

Sau prin FTP/cPanel File Manager.

---

## 🐛 Dacă Ceva Nu Funcționează

### Problema: Limitele nu se schimbă după modificarea .htaccess

**Soluții:**

1. **Restart Apache** (dacă aveți acces):
   ```bash
   sudo systemctl restart apache2
   ```

2. **Contactați Hostinger Support:**
   - "Vă rog să activați php_value în .htaccess pentru a modifica upload_max_filesize"
   - Unele configurări Hostinger blochează directivele php_value

3. **Alternative: PHP Configuration în Hostinger Panel:**
   - Hosting → Advanced → PHP Configuration
   - Modificați manual:
     - upload_max_filesize: 100M
     - post_max_size: 105M
     - max_execution_time: 300

### Problema: În continuare primesc "Undefined array key"

**Verificați:**

1. Fișierul `add_product.php` conține verificările `isset()`
2. Funcția `cleanInput()` din `config.php` are verificarea `if ($data === null)`
3. Clear cache browser (Ctrl + F5)

---

**📅 Creat:** <?php echo date('Y-m-d'); ?>  
**✅ Status:** Ready for Testing  

# 🚀 SISTEM REFERRAL MVP - GHID RAPID INSTALARE

## ⚡ Instalare în 3 Pași

### STEP 1: Rulează Migrările Database (2 minute)

```bash
# Conectare la MySQL
mysql -u u107933880_brodero -p u107933880_brodero

# Rulează scriptul
SOURCE /path/to/database_referral_system.sql;

# Verifică
SHOW TABLES LIKE '%referral%';
```

**Rezultat așteptat:**
- ✅ Tabel `referrals` creat
- ✅ Tabel `withdrawal_requests` creat
- ✅ Tabel `referral_settings` creat
- ✅ Coloană `users.referral_code` adăugată
- ✅ Coloană `users.credit_balance` adăugată

### STEP 2: Testează Sistemul (1 minut)

Deschide în browser:
```
https://brodero.online/test_referral_system.php
```

Verifică că toate testele sunt ✅ VERDE.

### STEP 3: Activează în Producție (1 minut)

**Adaugă link în meniul utilizatorilor** (header.php sau cont.php):

```php
<li class="nav-item">
    <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/referral.php">
        <i class="bi bi-people-fill me-1"></i>Referral
    </a>
</li>
```

**Adaugă în admin navigation:**

```php
<li class="nav-item">
    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/admin_referrals.php">
        <i class="bi bi-people-fill me-1"></i>Referrals
    </a>
</li>
```

---

## 📖 Documentație Completă

Pentru flow complet, testare, configurare și troubleshooting, vezi:

📄 **[REFERRAL_SYSTEM_COMPLETE.md](REFERRAL_SYSTEM_COMPLETE.md)** - Documentație tehnică completă (200+ linii)

---

## 🎯 Ce Face Sistemul?

| Utilizatori | Administratori |
|-------------|----------------|
| ✅ Primesc link referral unic | ✅ Monitorizează toate referrals |
| ✅ Invită prieteni | ✅ Procesează cereri retragere |
| ✅ Câștigă 50 RON per referral | ✅ Aprobă/Respinge cereri |
| ✅ Folosesc creditul la checkout | ✅ Statistici detaliate |
| ✅ Solicită retragere bancară | ✅ Configurează reward amounts |

---

## 🔥 Test Rapid Manual

### Test 1: Link Referral

1. Login ca utilizator → [/pages/referral.php](https://brodero.online/pages/referral.php)
2. Verifică că link-ul se afișează: `https://brodero.online/?ref=REF...`
3. Click "Copiază Link" → verifică clipboard

### Test 2: Tracking Referral

1. Deschide Incognito browser
2. Accesează link-ul: `https://brodero.online/?ref=REF...`
3. Verifică cookie în DevTools: `Application → Cookies → referral_code`
4. Creează cont nou
5. Verifică în database:
   ```sql
   SELECT * FROM referrals ORDER BY id DESC LIMIT 1;
   -- Ar trebui să vezi referral nou cu status='pending'
   ```

### Test 3: Recompensă

1. Login ca admin
2. [/admin/admin_orders.php](https://brodero.online/admin/admin_orders.php)
3. Marchează o comandă a user-ului invitat ca "Plătit"
4. Verifică în [/admin/admin_referrals.php](https://brodero.online/admin/admin_referrals.php)
   - Status: `pending` → `completed`
   - Referrer credit_balance: `0.00` → `50.00 RON`

---

## ⚙️ Configurare Rapidă

### Schimbă Suma Recompensei

```sql
UPDATE referral_settings 
SET setting_value = '75.00'  -- Schimbă din 50 în 75 RON
WHERE setting_key = 'reward_amount';
```

### Schimbă Minim Retragere

```sql
UPDATE referral_settings 
SET setting_value = '50.00'  -- Schimbă din 100 în 50 RON
WHERE setting_key = 'min_withdrawal_amount';
```

---

## 📂 Fișiere Create

| Fișier | Descriere | Linii |
|--------|-----------|-------|
| `database_referral_system.sql` | Migrări database | ~200 |
| `includes/functions_referral.php` | Funcții core | ~500 |
| `pages/referral.php` | Dashboard utilizator | ~400 |
| `admin/admin_referrals.php` | Admin panel | ~450 |
| `test_referral_system.php` | Script testare | ~200 |
| `REFERRAL_SYSTEM_COMPLETE.md` | Documentație | ~800 |

**Total:** ~2,550 linii de cod + documentație

---

## ✅ Checklist Go-Live

- [ ] Database migrată cu succes
- [ ] Test script verde (toate ✅)
- [ ] Link "Referral" adăugat în navigare
- [ ] Test manual: signup prin link → funcționează
- [ ] Test manual: prima plată → recompensă acordată
- [ ] Test manual: cerere retragere → admin procesează
- [ ] Configurat reward_amount final
- [ ] Comunicat utilizatorilor noua funcționalitate

---

## 🆘 Support

**Probleme?** Verifică:
1. [test_referral_system.php](test_referral_system.php) - Identifică erori
2. [REFERRAL_SYSTEM_COMPLETE.md](REFERRAL_SYSTEM_COMPLETE.md) - Secțiunea Troubleshooting
3. MySQL error logs - Pentru erori database
4. PHP error logs - Pentru erori runtime

---

**Sistem gata în < 5 minute!** 🚀

---

**Data:** 7 ianuarie 2026  
**Versiune:** MVP 1.0  
**Status:** ✅ Production Ready

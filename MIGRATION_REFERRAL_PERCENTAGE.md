# Migrare Sistem Referral: De la Reward Fix la Comision Procentual
**Data:** 7 ianuarie 2026  
**Versiune:** 2.0

---

## 📋 Sumar Modificări

Sistemul de referral a fost actualizat de la un model **reward fix la prima comandă** la un model **comision procentual la fiecare comandă plătită**.

### Înainte vs. Acum

| Aspect | **Versiunea 1.0 (Veche)** | **Versiunea 2.0 (Nouă)** |
|--------|---------------------------|--------------------------|
| **Recompensă** | Suma fixă (ex: 50 RON) | Procent din comandă (ex: 10%) |
| **Frecvență** | Doar la prima comandă | La **fiecare** comandă plătită |
| **Status Referral** | pending → completed | Relație permanentă (fără status) |
| **Tracking** | 1 reward per user referit | Multiple earnings per user referit |
| **Tabel Date** | referrals.reward_amount | referral_earnings |

---

## 🗄️ Modificări Bază de Date

### 1. Tabel `referrals` (Modificat)

**Coloane Adăugate:**
```sql
commission_percentage DECIMAL(5,2) NOT NULL DEFAULT 10.00
```

**Coloane Șterse:**
```sql
status (ENUM 'pending', 'completed')
reward_amount (DECIMAL)
completed_at (TIMESTAMP)
```

**Rezultat:** Relația devine permanentă. Fără status → utilizatorii referați rămân legați permanent de referrer.

---

### 2. Tabel `referral_earnings` (NOU)

Tracking comisioane per comandă plătită.

```sql
CREATE TABLE referral_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referral_id INT NOT NULL,              -- Link către referrals.id
    order_id INT NOT NULL,                 -- Link către orders.id
    order_total DECIMAL(10,2) NOT NULL,    -- Valoarea comenzii
    commission_amount DECIMAL(10,2) NOT NULL, -- Comision acordat
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_order_commission (order_id), -- O comandă = 1 comision
    FOREIGN KEY (referral_id) REFERENCES referrals(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);
```

**Constraint Important:** `UNIQUE(order_id)` → previne dublarea comisionului pentru aceeași comandă.

---

### 3. Tabel `referral_settings` (Modificat)

**Setare Nouă:**
```sql
INSERT INTO referral_settings (setting_key, setting_value)
VALUES ('commission_percentage', '10.00');
```

**Setare Ștearsă:**
```sql
DELETE FROM referral_settings WHERE setting_key = 'reward_amount';
```

---

## 🔧 Modificări Backend (PHP)

### `includes/functions_referral.php`

#### Funcții Modificate

**1. `createReferral()` - Nouă Parametrizare**
```php
// VECHI
createReferral($referrerId, $referredId)
INSERT INTO referrals (referrer_user_id, referred_user_id, status) 
VALUES (?, ?, 'pending')

// NOU
createReferral($referrerId, $referredId, $commissionPercentage = null)
INSERT INTO referrals (referrer_user_id, referred_user_id, commission_percentage) 
VALUES (?, ?, ?)
```

**2. `activateReferralReward()` → `calculateAndAwardCommission()`**

| Aspect | Vechi | Nou |
|--------|-------|-----|
| **Trigger** | User referit face prima comandă | **Fiecare** comandă plătită |
| **Input** | `$referredUserId` | `$orderId` |
| **Logică** | Verifică status pending, acordă suma fixă | Calculează % din order_total |
| **Output** | UPDATE referrals SET status='completed' | INSERT INTO referral_earnings |

**Implementare Nouă:**
```php
function calculateAndAwardCommission($orderId) {
    // 1. Obține detalii comandă
    SELECT user_id, total FROM orders WHERE id = ? AND payment_status = 'paid'
    
    // 2. Verifică dacă user are referrer
    SELECT id, referrer_user_id, commission_percentage 
    FROM referrals WHERE referred_user_id = ?
    
    // 3. Verifică dacă comisionul a fost deja acordat
    SELECT COUNT(*) FROM referral_earnings WHERE order_id = ?
    
    // 4. Calculează comision
    $commissionAmount = ($orderTotal * $commissionPercentage) / 100
    
    // 5. Salvează în DB (tranzacție)
    BEGIN TRANSACTION;
    INSERT INTO referral_earnings (referral_id, order_id, order_total, commission_amount)
    UPDATE users SET credit_balance = credit_balance + $commissionAmount
    COMMIT;
}
```

**3. `getUserReferralStats()` - Noi Metrici**

```php
// VECHI - returna:
[
    'total_referrals' => 5,
    'completed_referrals' => 3,
    'pending_referrals' => 2,
    'total_earned' => 150.00
]

// NOU - returnează:
[
    'total_referrals' => 5,
    'commission_percentage' => 10.00,
    'total_earned' => 245.50,
    'orders_with_commission' => 12,  // NOU
    'current_balance' => 100.00
]
```

#### Funcții Noi

**`getUserReferralEarnings($userId)`**
```php
// Returnează lista detaliată de comisioane
SELECT 
    re.*,
    o.order_number,
    u.first_name, u.last_name
FROM referral_earnings re
JOIN orders o ON re.order_id = o.id
WHERE referral_id IN (SELECT id FROM referrals WHERE referrer_user_id = ?)
```

**`getCommissionPercentage()`**
```php
// Înlocuiește getReferralRewardAmount()
SELECT setting_value FROM referral_settings 
WHERE setting_key = 'commission_percentage'
```

---

## 🔄 Modificări Integrări Plată

### `admin/admin_orders.php`

**VECHI:**
```php
if ($newPaymentStatus === 'paid') {
    enableOrderDownloads($orderId);
    
    // Verifică dacă e PRIMA comandă plătită
    $paidOrdersCount = ...;
    if ($paidOrdersCount == 1) {
        activateReferralReward($orderUserId);
    }
}
```

**NOU:**
```php
if ($newPaymentStatus === 'paid') {
    enableOrderDownloads($orderId);
    
    // Acordă comision la FIECARE comandă plătită
    calculateAndAwardCommission($orderId);
}
```

### `pages/payment_success.php`

**VECHI:**
```php
if ($order && $order['user_id']) {
    activateReferralReward($order['user_id']);
}
```

**NOU:**
```php
if ($order && $order['id']) {
    calculateAndAwardCommission($order['id']);
}
```

---

## 🎨 Modificări Frontend

### `pages/referral.php` (Dashboard User)

#### Statistici Cards

| Card | Vechi | Nou |
|------|-------|-----|
| 1 | Sold Disponibil | Sold Disponibil (neschimbat) |
| 2 | Total Câștigat | Total Câștigat (neschimbat) |
| 3 | **Referrals Reușite** | **Utilizatori Referați** |
| 4 | **În Așteptare (pending)** | **Comenzi cu Comision** |

#### Tabel Utilizatori Invitați

**VECHI:**
| Nume | Email | Data | **Status** | **Recompensă** |
|------|-------|------|----------|--------------|
| Ion Popescu | i***@... | 01.01.2026 | Completat | 50.00 RON |

**NOU:**
| Nume | Email | Data | **Comenzi** | **Total Comision** |
|------|-------|------|------------|-------------------|
| Ion Popescu | i***@... | 01.01.2026 | 3 comenzi | 75.50 RON |

#### Secțiune Nouă: Istoric Comisioane

Tabel detaliat cu toate comisioanele primite:

```php
foreach ($earningsList as $earning):
    Data: 05.01.2026 14:30
    Comandă: #ORD-12345
    De la: Ion Popescu
    Valoare Comandă: 250.00 RON
    Comision (10%): +25.00 RON
```

---

### `admin/admin_referrals.php` (Dashboard Admin)

#### Statistici Cards

| Card | Vechi | Nou |
|------|-------|-----|
| 1 | **Referrals Completate** | **Total Referrals** |
| 2 | **Referrals Pending** | **Comenzi cu Comision** |
| 3 | **Total Recompense** | **Total Comisioane Plătite** |
| 4 | Total Retras | Total Retras (neschimbat) |

#### Tab 1: Toate Referrals

**VECHI:**
| Referrer | Referred | Data | **Status** | **Recompensă** | **Data Completare** |

**NOU:**
| Referrer | Referred | Data | **Comision %** | **Comenzi** | **Total Comision** |

#### Tab 2: Comisioane (NOU)

Tabel dedicat pentru vizualizare comisioane:

| ID | Data | Comandă | Referrer | Referred | Valoare Comandă | Comision Acordat |
|----|------|---------|----------|----------|-----------------|------------------|
| 1 | 05.01 | #ORD-123 | Ana M. | Ion P. | 250.00 RON | +25.00 RON |

#### Tab 3: Setări

**VECHI:**
- Recompensă per Referral Reușit: 50.00 RON

**NOU:**
- Comision Procentual: 10%

---

## 📊 Exemplu Flux Complet

### Scenariu: Ana invită pe Ion

**1. Ion accesează link-ul Anei**
```
https://brodero.online/?ref=REFABC1234567
→ Cookie salvat (30 zile)
```

**2. Ion se înregistrează**
```php
// În pages/login.php
$newUserId = 42; // Ion
$referralCode = 'REFABC1234567';
$referrerId = getUserIdFromReferralCode($referralCode); // Ana = ID 10

createReferral(10, 42, 10.00); // Ana referă pe Ion cu 10% comision
```

**Rezultat DB:**
```sql
INSERT INTO referrals 
VALUES (1, 10, 42, 10.00, NOW());
```

**3. Ion face prima comandă (100 RON)**
```php
// După plată în admin_orders.php sau payment_success.php
calculateAndAwardCommission(orderId: 55);
```

**Rezultat DB:**
```sql
INSERT INTO referral_earnings 
VALUES (NULL, 1, 55, 100.00, 10.00, NOW());

UPDATE users 
SET credit_balance = credit_balance + 10.00 
WHERE id = 10; -- Ana primește 10 RON
```

**4. Ion face a doua comandă (200 RON)**
```php
calculateAndAwardCommission(orderId: 67);
```

**Rezultat DB:**
```sql
INSERT INTO referral_earnings 
VALUES (NULL, 1, 67, 200.00, 20.00, NOW());

UPDATE users 
SET credit_balance = credit_balance + 20.00 
WHERE id = 10; -- Ana primește încă 20 RON (total: 30 RON)
```

**5. Dashboard Ana**

Statistici:
- Sold Disponibil: **30.00 RON**
- Total Câștigat: **30.00 RON**
- Utilizatori Referați: **1**
- Comenzi cu Comision: **2**

Istoric Comisioane:
| Data | Comandă | De la | Valoare | Comision (10%) |
|------|---------|-------|---------|----------------|
| 06.01 14:30 | #ORD-67 | Ion Popescu | 200.00 | +20.00 RON |
| 05.01 10:15 | #ORD-55 | Ion Popescu | 100.00 | +10.00 RON |

---

## ✅ Checklist Migrare

### Pre-Migrare

- [ ] **BACKUP COMPLET** bază de date
- [ ] **BACKUP FIȘIERE** PHP modificate
- [ ] Verificare sistem vechi funcționează corect
- [ ] Notificare utilizatori despre schimbare (opțional)

### Executare Migrare

1. [ ] Conectare SSH/phpMyAdmin
2. [ ] Rulare `database_referral_percentage_migration.sql`
3. [ ] Verificare tabele create:
   ```sql
   SHOW TABLES LIKE '%referral%';
   SHOW COLUMNS FROM referrals;
   SHOW COLUMNS FROM referral_earnings;
   ```
4. [ ] Upload fișiere PHP modificate:
   - `includes/functions_referral.php`
   - `admin/admin_orders.php`
   - `pages/payment_success.php`
   - `pages/referral.php`
   - `admin/admin_referrals.php`

### Post-Migrare

- [ ] Test creare utilizator nou cu referral code
- [ ] Test plasare comandă de către user referit
- [ ] Test aprobare plată de admin → verificare comision acordat
- [ ] Test dashboard user → vizualizare earnings
- [ ] Test dashboard admin → vizualizare toate comisioanele
- [ ] Verificare logs pentru erori: `grep "COMMISSION" error.log`

---

## 🔍 Verificări & Teste

### 1. Test Database Structure
```sql
-- Verifică coloana commission_percentage
SELECT column_name, column_type 
FROM information_schema.columns 
WHERE table_name = 'referrals' AND column_name = 'commission_percentage';

-- Verifică tabel referral_earnings există
SHOW TABLES LIKE 'referral_earnings';

-- Verifică constraint UNIQUE pe order_id
SHOW INDEXES FROM referral_earnings WHERE Key_name = 'unique_order_commission';
```

### 2. Test Calcul Comision
```php
// Plasează o comandă test de 100 RON
// Verifică în DB:
SELECT * FROM referral_earnings WHERE order_id = ?;
-- Așteptat: commission_amount = 10.00 (pentru 10%)

// Verifică credit_balance
SELECT credit_balance FROM users WHERE id = [referrer_id];
-- Ar trebui să fie mărit cu 10.00
```

### 3. Test Anti-Duplicare
```php
// Încearcă să marchezi aceeași comandă ca "paid" de 2 ori
// Al doilea apel la calculateAndAwardCommission() trebuie să returneze false
// Verifică că nu există 2 records în referral_earnings cu același order_id
```

---

## ⚙️ Configurare

### Modificare Procent Comision Global

```sql
UPDATE referral_settings 
SET setting_value = '15.00' 
WHERE setting_key = 'commission_percentage';
```

### Modificare Comision pentru Referral Specific

```sql
UPDATE referrals 
SET commission_percentage = 15.00 
WHERE id = 5; -- Referral-ul cu ID 5 va avea 15% în loc de 10%
```

---

## 📈 Statistici & Rapoarte

### Total Comisioane Plătite (Toate Timpurile)
```sql
SELECT SUM(commission_amount) as total_commission
FROM referral_earnings;
```

### Top 10 Referrers (Cei mai activi)
```sql
SELECT 
    u.first_name, u.last_name,
    COUNT(DISTINCT re.order_id) as total_orders,
    SUM(re.commission_amount) as total_earned
FROM users u
JOIN referrals r ON u.id = r.referrer_user_id
JOIN referral_earnings re ON r.id = re.referral_id
GROUP BY u.id
ORDER BY total_earned DESC
LIMIT 10;
```

### Comisioane pe Lună
```sql
SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as total_commissions,
    SUM(commission_amount) as total_amount
FROM referral_earnings
GROUP BY month
ORDER BY month DESC;
```

---

## 🚨 Troubleshooting

### Problema: Comisionul nu se acordă

**Verificări:**
1. Comanda are `payment_status = 'paid'`?
   ```sql
   SELECT payment_status FROM orders WHERE id = ?;
   ```

2. Utilizatorul are referrer?
   ```sql
   SELECT * FROM referrals WHERE referred_user_id = ?;
   ```

3. Comisionul a fost deja acordat?
   ```sql
   SELECT * FROM referral_earnings WHERE order_id = ?;
   ```

4. Logs PHP:
   ```bash
   grep "COMMISSION" /path/to/error.log
   ```

### Problema: Comision acordat de 2 ori

**Cauză:** UNIQUE constraint nu funcționează sau tranzacția a fost rulată manual.

**Soluție:**
```sql
-- Identifică duplicatele
SELECT order_id, COUNT(*) 
FROM referral_earnings 
GROUP BY order_id 
HAVING COUNT(*) > 1;

-- Șterge duplicatele (păstrează cel mai vechi)
DELETE e1 FROM referral_earnings e1
INNER JOIN referral_earnings e2 
WHERE e1.id > e2.id AND e1.order_id = e2.order_id;

-- Recalculează credit_balance
-- (necesită script custom pentru fiecare user afectat)
```

---

## 📝 Note Finale

### Limitări MVP

- Nu se acordă comisioane pentru comenzi istorice (anterioare migrării)
- Procentul comision este fix (nu diferențiat pe categorii produse)
- Nu există system de niveluri (ex: 10% pentru primele 5 comenzi, apoi 5%)

### Extensii Viitoare Posibile

1. **Comisioane Tiered:**
   ```sql
   ALTER TABLE referrals 
   ADD COLUMN tier_level INT DEFAULT 1;
   
   -- Tier 1: 10%, Tier 2: 12%, Tier 3: 15%
   ```

2. **Expirare Referral:**
   ```sql
   ALTER TABLE referrals 
   ADD COLUMN expires_at TIMESTAMP NULL;
   
   -- Acordă comision doar dacă NOW() < expires_at
   ```

3. **Comisioane per Categorie:**
   ```sql
   CREATE TABLE category_commission_rates (
       category_id INT,
       commission_percentage DECIMAL(5,2)
   );
   ```

---

**Documentat de:** AI Assistant  
**Ultima actualizare:** 7 ianuarie 2026  
**Versiune document:** 1.0

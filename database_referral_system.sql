-- ═══════════════════════════════════════════════════════════════════════════
-- REFERRAL SYSTEM MVP - Database Migration
-- Data: 7 ianuarie 2026
-- Descriere: Sistem complet de referral cu credit și retrageri bancare
-- ═══════════════════════════════════════════════════════════════════════════

-- ───────────────────────────────────────────────────────────────────────────
-- STEP 1: Modifică tabelul users - Adaugă câmpuri pentru referral
-- ───────────────────────────────────────────────────────────────────────────

-- Adaugă codul unic de referral (generat automat la înregistrare)
ALTER TABLE users 
ADD COLUMN referral_code VARCHAR(20) UNIQUE NULL 
COMMENT 'Cod unic pentru link referral (ex: REF123ABC)';

-- Adaugă soldul de credit (bani câștigați din referrals)
ALTER TABLE users 
ADD COLUMN credit_balance DECIMAL(10,2) DEFAULT 0.00 
COMMENT 'Sold disponibil din referrals (lei)';

-- Index pentru căutare rapidă după referral_code
CREATE INDEX idx_users_referral_code ON users(referral_code);

-- ───────────────────────────────────────────────────────────────────────────
-- STEP 2: Tabel REFERRALS - Relația referrer ↔ referred
-- ───────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Utilizatorul care a trimis invitația (cel care primește recompensa)
    referrer_user_id INT NOT NULL,
    
    -- Utilizatorul care s-a înregistrat prin link (cel invitat)
    referred_user_id INT NOT NULL,
    
    -- Status referral
    -- pending: user s-a înregistrat, dar nu a făcut prima comandă plătită
    -- completed: user a făcut prima comandă plătită, recompensa a fost acordată
    status ENUM('pending', 'completed') DEFAULT 'pending',
    
    -- Suma recompensei acordate (se completează la activare)
    reward_amount DECIMAL(10,2) DEFAULT 0.00 
    COMMENT 'Suma acordată referrer-ului (lei)',
    
    -- Timestamp când referral-ul a fost creat (la signup)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Timestamp când referral-ul a fost completat (la prima plată)
    completed_at TIMESTAMP NULL,
    
    -- Foreign keys
    FOREIGN KEY (referrer_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Constraints
    -- Un user poate fi referit o singură dată
    UNIQUE KEY unique_referred_user (referred_user_id),
    
    -- Nu poți face self-referral (verificare la nivel aplicație)
    CHECK (referrer_user_id != referred_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Relații referral între utilizatori';

-- Indexuri pentru performance
CREATE INDEX idx_referrals_referrer ON referrals(referrer_user_id);
CREATE INDEX idx_referrals_status ON referrals(status);

-- ───────────────────────────────────────────────────────────────────────────
-- STEP 3: Tabel WITHDRAWAL_REQUESTS - Cereri retragere bancară
-- ───────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS withdrawal_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Utilizatorul care solicită retragerea
    user_id INT NOT NULL,
    
    -- Suma solicitată pentru retragere
    amount DECIMAL(10,2) NOT NULL 
    COMMENT 'Suma solicitată (lei)',
    
    -- Detalii cont bancar
    bank_account_iban VARCHAR(50) NOT NULL 
    COMMENT 'IBAN cont beneficiar',
    
    bank_account_name VARCHAR(255) NOT NULL 
    COMMENT 'Nume titular cont bancar',
    
    -- Status cerere
    -- pending: cerere nouă, neprocessată
    -- approved: cerere aprobată, transfer efectuat
    -- rejected: cerere respinsă
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    
    -- Notă admin (motiv respingere sau confirmare transfer)
    admin_note TEXT NULL 
    COMMENT 'Notă admin: nr. tranzacție sau motiv respingere',
    
    -- ID admin care a procesat cererea
    processed_by_admin_id INT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by_admin_id) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Constraints
    CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Cereri de retragere bancară a creditului';

-- Indexuri pentru performance
CREATE INDEX idx_withdrawal_user ON withdrawal_requests(user_id);
CREATE INDEX idx_withdrawal_status ON withdrawal_requests(status);
CREATE INDEX idx_withdrawal_created ON withdrawal_requests(created_at DESC);

-- ───────────────────────────────────────────────────────────────────────────
-- STEP 4: Populare coduri referral pentru utilizatori existenți
-- ───────────────────────────────────────────────────────────────────────────

-- Generează coduri unice pentru utilizatorii care nu au încă
-- Format: REF + timestamp + random
UPDATE users 
SET referral_code = CONCAT('REF', UPPER(SUBSTRING(MD5(CONCAT(id, email, NOW())), 1, 10)))
WHERE referral_code IS NULL;

-- ───────────────────────────────────────────────────────────────────────────
-- STEP 5: Configurări sistem (valori implicite)
-- ───────────────────────────────────────────────────────────────────────────

-- Opțional: Tabel de configurare pentru reward amounts
CREATE TABLE IF NOT EXISTS referral_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Setări configurabile sistem referral';

-- Inserare setări implicite
INSERT INTO referral_settings (setting_key, setting_value, description) VALUES
('reward_amount', '50.00', 'Suma recompensă per referral reușit (lei)'),
('min_withdrawal_amount', '100.00', 'Suma minimă pentru retragere bancară (lei)'),
('referral_enabled', '1', 'Sistemul de referral este activ (1=DA, 0=NU)')
ON DUPLICATE KEY UPDATE 
    setting_value = VALUES(setting_value);

-- ═══════════════════════════════════════════════════════════════════════════
-- VERIFICARE FINALĂ
-- ═══════════════════════════════════════════════════════════════════════════

-- Verifică structura tabelelor
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME,
    TABLE_COMMENT
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('users', 'referrals', 'withdrawal_requests', 'referral_settings');

-- Verifică câmpurile noi în users
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME IN ('referral_code', 'credit_balance');

-- ═══════════════════════════════════════════════════════════════════════════
-- ROLLBACK (Dacă este nevoie)
-- ═══════════════════════════════════════════════════════════════════════════

/*
-- ATENȚIE: Rulează doar dacă vrei să ștergi tot sistemul de referral!

DROP TABLE IF EXISTS withdrawal_requests;
DROP TABLE IF EXISTS referrals;
DROP TABLE IF EXISTS referral_settings;

ALTER TABLE users DROP COLUMN IF EXISTS referral_code;
ALTER TABLE users DROP COLUMN IF EXISTS credit_balance;
*/

-- ═══════════════════════════════════════════════════════════════════════════
-- INSTRUCȚIUNI DE INSTALARE
-- ═══════════════════════════════════════════════════════════════════════════

/*
1. Conectează-te la MySQL:
   mysql -u u107933880_brodero -p u107933880_brodero

2. Rulează acest script:
   SOURCE database_referral_system.sql;

3. Verifică că totul a fost creat:
   SHOW TABLES;
   DESCRIBE users;
   DESCRIBE referrals;
   DESCRIBE withdrawal_requests;

4. Verifică setările:
   SELECT * FROM referral_settings;

DONE! ✅
*/

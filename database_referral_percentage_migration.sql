-- ============================================================
-- MIGRARE SISTEM REFERRAL: De la reward fix la comision procentual
-- Data: 2026-01-07
-- Descriere: Modifică structura pentru acordare comision la fiecare comandă
-- ============================================================

-- 1. Modificare tabel referrals
-- ============================================================
-- Adăugare coloană commission_percentage
ALTER TABLE referrals 
ADD COLUMN commission_percentage DECIMAL(5,2) NOT NULL DEFAULT 10.00 
COMMENT 'Procent comision (ex: 10.00 = 10%)';

-- Ștergere coloane status, reward_amount, completed_at (relația devine permanentă)
ALTER TABLE referrals 
DROP COLUMN status,
DROP COLUMN reward_amount,
DROP COLUMN completed_at;

-- Adăugare index pentru performanță
ALTER TABLE referrals 
ADD INDEX idx_referred_user (referred_user_id);

-- 2. Creare tabel referral_earnings (tracking comisioane per comandă)
-- ============================================================
CREATE TABLE IF NOT EXISTS referral_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referral_id INT NOT NULL COMMENT 'ID-ul relației din tabelul referrals',
    order_id INT NOT NULL COMMENT 'ID-ul comenzii care a generat comisionul',
    order_total DECIMAL(10,2) NOT NULL COMMENT 'Valoarea totală a comenzii',
    commission_amount DECIMAL(10,2) NOT NULL COMMENT 'Suma comisionului acordat',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data acordării comisionului',
    
    -- Foreign Keys
    FOREIGN KEY (referral_id) REFERENCES referrals(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    
    -- Constraint: o comandă generează comision o singură dată
    UNIQUE KEY unique_order_commission (order_id),
    
    -- Indexes pentru performanță
    INDEX idx_referral (referral_id),
    INDEX idx_order (order_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracking comisioane referral per comandă';

-- 3. Actualizare setări în referral_settings
-- ============================================================
-- Adăugare setare commission_percentage (înlocuiește reward_amount)
INSERT INTO referral_settings (setting_key, setting_value)
VALUES ('commission_percentage', '10.00')
ON DUPLICATE KEY UPDATE setting_value = '10.00';

-- Ștergere setare veche reward_amount (nu mai e relevantă)
DELETE FROM referral_settings WHERE setting_key = 'reward_amount';

-- ============================================================
-- VERIFICĂRI POST-MIGRARE
-- ============================================================

-- Verificare structură referrals
SELECT 'Verificare tabel referrals' as check_name;
DESCRIBE referrals;

-- Verificare tabel referral_earnings
SELECT 'Verificare tabel referral_earnings' as check_name;
DESCRIBE referral_earnings;

-- Verificare setări
SELECT 'Verificare setări referral' as check_name;
SELECT * FROM referral_settings WHERE setting_key LIKE '%commission%' OR setting_key LIKE '%referral%';

-- Statistici curente
SELECT 
    'Statistici referrals' as info,
    COUNT(*) as total_referrals,
    COUNT(DISTINCT referrer_user_id) as total_referrers,
    COUNT(DISTINCT referred_user_id) as total_referred
FROM referrals;

-- ============================================================
-- NOTE IMPORTANTE
-- ============================================================
-- 1. Backup-ul bazei de date este OBLIGATORIU înainte de rulare
-- 2. După migrare, relațiile existente rămân valide și permanente
-- 3. Comisionul se va aplica DOAR la comenzile viitoare (plătite după migrare)
-- 4. Pentru comenzi istorice, nu se vor genera automat comisioane retroactive
-- 5. Procent default: 10% (poate fi modificat în referral_settings)
-- ============================================================

-- ================================================================
-- SCRIPT ACTUALIZARE BD - Sistem Descărcări Fișiere
-- Data: 11 Decembrie 2025
-- ================================================================

-- 1. Verifică și adaugă coloana downloads_enabled dacă nu există
-- ================================================================
SET @dbname = DATABASE();
SET @tablename = 'order_items';
SET @columnname = 'downloads_enabled';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TINYINT(1) NOT NULL DEFAULT 0')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. Activează descărcările pentru toate comenzile plătite (migrare date existente)
-- ================================================================
UPDATE order_items oi
JOIN orders o ON o.id = oi.order_id
SET oi.downloads_enabled = 1
WHERE o.payment_status = 'paid'
AND (oi.downloads_enabled IS NULL OR oi.downloads_enabled = 0);

-- 3. Raport comenzi actualizate
-- ================================================================
SELECT 
    'Comenzi actualizate' as Status,
    COUNT(DISTINCT oi.order_id) as NumarComenzi,
    COUNT(oi.id) as NumarItems
FROM order_items oi
JOIN orders o ON o.id = oi.order_id
WHERE o.payment_status = 'paid'
AND oi.downloads_enabled = 1;

-- 4. Verificare integritate
-- ================================================================
-- Comenzi plătite cu descărcări blocate (nu ar trebui să existe după rulare)
SELECT 
    o.id,
    o.order_number,
    o.payment_status,
    COUNT(oi.id) as total_items,
    SUM(CASE WHEN oi.downloads_enabled = 1 THEN 1 ELSE 0 END) as enabled_items,
    SUM(CASE WHEN oi.downloads_enabled = 0 THEN 1 ELSE 0 END) as blocked_items
FROM orders o
JOIN order_items oi ON oi.order_id = o.id
WHERE o.payment_status = 'paid'
GROUP BY o.id
HAVING blocked_items > 0;

-- 5. Index pentru performanță (opțional)
-- ================================================================
-- Adaugă index pe downloads_enabled pentru căutări rapide
ALTER TABLE order_items ADD INDEX idx_downloads_enabled (downloads_enabled);

-- 6. Statistici finale
-- ================================================================
SELECT 
    'Statistici Finale' as Tip,
    (SELECT COUNT(*) FROM orders WHERE payment_status = 'paid') as ComenziPlatite,
    (SELECT COUNT(DISTINCT oi.order_id) 
     FROM order_items oi 
     JOIN orders o ON o.id = oi.order_id 
     WHERE o.payment_status = 'paid' AND oi.downloads_enabled = 1) as ComenziCuDescarcariActive,
    (SELECT COUNT(DISTINCT oi.order_id) 
     FROM order_items oi 
     JOIN orders o ON o.id = oi.order_id 
     WHERE o.payment_status = 'paid' AND oi.downloads_enabled = 0) as ComenziCuDescarcariBlacate;

-- ================================================================
-- ROLLBACK (în caz de probleme - rulează manual)
-- ================================================================
-- Pentru a reveni la statusul anterior:
-- UPDATE order_items SET downloads_enabled = 0 WHERE downloads_enabled = 1;

-- ================================================================
-- NOTES
-- ================================================================
-- Acest script:
-- 1. Adaugă coloana downloads_enabled dacă nu există
-- 2. Activează descărcările pentru toate comenzile plătite
-- 3. Verifică integritatea datelor
-- 4. Adaugă index pentru performanță
-- 
-- Rulează înainte de deployment pentru a migra datele existente.
-- ================================================================

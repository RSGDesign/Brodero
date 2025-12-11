-- ================================================================
-- VERIFICARE ȘI MIGRARE TABEL contact_messages
-- Data: 11 Decembrie 2025
-- ================================================================

-- 1. Creare tabel dacă nu există
-- ================================================================
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    attachments TEXT COMMENT 'JSON array cu nume fișiere',
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Adăugare coloană updated_at dacă nu există
-- ================================================================
SET @dbname = DATABASE();
SET @tablename = 'contact_messages';
SET @columnname = 'updated_at';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 3. Verificare structură
-- ================================================================
SHOW CREATE TABLE contact_messages;

-- 4. Statistici
-- ================================================================
SELECT 
    'Total Mesaje' as Tip,
    COUNT(*) as Numar
FROM contact_messages
UNION ALL
SELECT 
    'Mesaje Noi (nelecite)' as Tip,
    COUNT(*) as Numar
FROM contact_messages
WHERE status = 'new'
UNION ALL
SELECT 
    'Mesaje Cu Atașamente' as Tip,
    COUNT(*) as Numar
FROM contact_messages
WHERE attachments IS NOT NULL;

-- 5. Ultimele 5 mesaje
-- ================================================================
SELECT 
    id,
    name,
    email,
    subject,
    status,
    created_at
FROM contact_messages
ORDER BY created_at DESC
LIMIT 5;

-- ================================================================
-- INTEROGĂRI UTILE
-- ================================================================

-- Toate mesajele necitite:
-- SELECT * FROM contact_messages WHERE status = 'new' ORDER BY created_at DESC;

-- Marchează mesaj ca citit:
-- UPDATE contact_messages SET status = 'read' WHERE id = 1;

-- Marchează mesaj ca răspuns:
-- UPDATE contact_messages SET status = 'replied' WHERE id = 1;

-- Șterge mesaje mai vechi de 6 luni:
-- DELETE FROM contact_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);

-- Export date mesaj specific:
-- SELECT * FROM contact_messages WHERE id = 1\G

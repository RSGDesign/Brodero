-- ============================================================================
-- SEO Pages Management System - Database Migration
-- ============================================================================
-- Tabelă pentru gestionarea SEO-ului per pagină
-- MVP: Title, Description, Keywords per page_slug
-- ============================================================================

CREATE TABLE IF NOT EXISTS `seo_pages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_slug` VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL-ul paginii (ex: home, magazin, product:slug)',
    `title` VARCHAR(255) NOT NULL COMMENT 'Meta Title (max 60 caractere recomandat)',
    `description` TEXT NULL COMMENT 'Meta Description (max 160 caractere recomandat)',
    `keywords` TEXT NULL COMMENT 'Meta Keywords - CSV (ex: produse digitale, grafică)',
    `og_image` VARCHAR(500) NULL COMMENT 'URL imagine Open Graph (opțional)',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '1 = activ, 0 = dezactivat',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_page_slug` (`page_slug`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Inserare date SEO default pentru paginile principale
-- ============================================================================

INSERT INTO `seo_pages` (`page_slug`, `title`, `description`, `keywords`, `is_active`) VALUES
('home', 
 'Brodero - Produse Digitale Premium pentru Creativi', 
 'Descoperă produse digitale de calitate: șabloane grafice, fonturi, mockup-uri și resurse premium pentru designeri și creatori de conținut.', 
 'produse digitale, șabloane grafice, fonturi, mockup-uri, resurse premium, design grafic',
 1),

('magazin', 
 'Magazin - Produse Digitale Brodero', 
 'Explorează magazinul nostru cu produse digitale premium: șabloane, fonturi, texture și multe altele pentru proiectele tale creative.', 
 'magazin online, produse digitale, șabloane premium, resurse grafice, descărcări digitale',
 1),

('contact', 
 'Contact - Brodero', 
 'Contactează-ne pentru orice întrebări sau suport. Echipa Brodero este aici să te ajute cu produsele tale digitale.', 
 'contact, suport clienți, asistență, mesaj',
 1),

('program-referral', 
 'Program Referral - Câștigă Comision cu Brodero', 
 'Alătură-te programului nostru de afiliere și câștigă comision din fiecare vânzare. Promovează produse digitale de calitate și obține venituri pasive.', 
 'program afiliere, referral, comision, marketing afiliat, venit pasiv',
 1),

('cart', 
 'Coș de Cumpărături - Brodero', 
 'Revizuiește produsele selectate și finalizează comanda. Plată securizată și descărcare instantanee.', 
 'coș cumpărături, checkout, finalizare comandă',
 1),

('modele-la-comanda', 
 'Modele la Comandă - Design Personalizat Brodero', 
 'Comandă modele de broderie personalizate adaptate nevoilor tale. Trimite cererea cu detalii și fișiere atașate, primești răspuns în 24h.', 
 'modele la comandă, design personalizat, comenzi custom, broderie personalizată, design unic',
 1)

ON DUPLICATE KEY UPDATE 
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `keywords` = VALUES(`keywords`),
    `updated_at` = CURRENT_TIMESTAMP;

-- ============================================================================
-- Template pentru produse (opțional - se poate suprascrie individual)
-- ============================================================================

INSERT INTO `seo_pages` (`page_slug`, `title`, `description`, `keywords`, `is_active`) VALUES
('product:default', 
 '{product_name} - Brodero', 
 'Descarcă {product_name} - produs digital premium. {product_description}', 
 'produs digital, descărcare instant, {product_category}',
 1)
ON DUPLICATE KEY UPDATE 
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `updated_at` = CURRENT_TIMESTAMP;

-- ============================================================================
-- Verificare
-- ============================================================================
SELECT 'SEO Pages table created successfully!' AS status;
SELECT COUNT(*) AS total_seo_pages FROM seo_pages;

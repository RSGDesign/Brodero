-- ============================================================================
-- Custom Orders System - Database Migration
-- ============================================================================
-- Sistem pentru comenzi personalizate / modele la comandă
-- MVP: Formular public + Dashboard admin
-- ============================================================================

CREATE TABLE IF NOT EXISTS `custom_orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL COMMENT 'Nume client',
    `email` VARCHAR(255) NOT NULL COMMENT 'Email contact',
    `phone` VARCHAR(50) NULL COMMENT 'Telefon opțional',
    `description` TEXT NOT NULL COMMENT 'Descriere comandă personalizată',
    `budget` DECIMAL(10,2) NULL COMMENT 'Buget estimativ (opțional)',
    `file_path` VARCHAR(500) NULL COMMENT 'Calea către fișierul atașat',
    `file_original_name` VARCHAR(255) NULL COMMENT 'Numele original al fișierului',
    `status` ENUM('new', 'in_progress', 'completed', 'cancelled') DEFAULT 'new' COMMENT 'Status comandă',
    `admin_notes` TEXT NULL COMMENT 'Note admin (interne)',
    `ip_address` VARCHAR(45) NULL COMMENT 'IP client (tracking)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Verificare
-- ============================================================================
SELECT 'Custom Orders table created successfully!' AS status;
SELECT COUNT(*) AS total_orders FROM custom_orders;

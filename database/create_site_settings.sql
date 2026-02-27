-- ===================================================
-- Migration: Create site_settings table
-- Run this SQL directly in phpMyAdmin or MySQL console
-- ===================================================

CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(255) NOT NULL,
    `value` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `site_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default watermark settings
INSERT IGNORE INTO `site_settings` (`key`, `value`, `created_at`, `updated_at`) VALUES
('image_watermark_enabled', '0', NOW(), NOW()),
('image_watermark_type', 'text', NOW(), NOW()),
('image_watermark_text', '© منتدى أرثوذكس', NOW(), NOW()),
('image_watermark_image_path', '', NOW(), NOW()),
('image_watermark_position', 'bottom-right', NOW(), NOW()),
('image_watermark_opacity', '50', NOW(), NOW()),
('image_watermark_font_size', '24', NOW(), NOW()),
('image_watermark_margin', '15', NOW(), NOW());

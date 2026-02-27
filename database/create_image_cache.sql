-- ==============================================
-- LIIMS: Legacy Intelligent Image Management System
-- Table: image_cache
-- Run this in phpMyAdmin
-- ==============================================

CREATE TABLE IF NOT EXISTS `image_cache` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `url_hash` VARCHAR(64) NOT NULL COMMENT 'SHA-256 hash of original URL',
    `original_url` TEXT NOT NULL,
    `status` ENUM('valid', 'broken', 'pending') NOT NULL DEFAULT 'pending',
    `response_code` SMALLINT NULL,
    `content_type` VARCHAR(100) NULL,
    `content_length` INT UNSIGNED NULL,
    `last_checked_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `image_cache_url_hash_unique` (`url_hash`),
    KEY `image_cache_status_index` (`status`),
    KEY `image_cache_last_checked_index` (`last_checked_at`),
    KEY `image_cache_status_checked_index` (`status`, `last_checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

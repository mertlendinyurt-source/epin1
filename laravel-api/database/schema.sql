-- PINLY UC Store - MySQL Database Schema
-- Laravel migration'ların SQL karşılığı
-- cPanel phpMyAdmin'de doğrudan çalıştırabilirsiniz

SET FOREIGN_KEY_CHECKS=0;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` CHAR(36) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `auth_provider` VARCHAR(20) DEFAULT 'email',
    `google_id` VARCHAR(100) DEFAULT NULL,
    `avatar_url` VARCHAR(500) DEFAULT NULL,
    `phone_verified` TINYINT(1) DEFAULT 0,
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    UNIQUE KEY `users_google_id_unique` (`google_id`),
    KEY `users_email_index` (`email`),
    KEY `users_google_id_index` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Users Table
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` CHAR(36) NOT NULL,
    `username` VARCHAR(100) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `admin_users_username_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products Table
CREATE TABLE IF NOT EXISTS `products` (
    `id` CHAR(36) NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `uc_amount` INT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `discount_price` DECIMAL(10,2) NOT NULL,
    `discount_percent` DECIMAL(5,2) DEFAULT 0,
    `active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `image_url` VARCHAR(500) DEFAULT NULL,
    `region_code` VARCHAR(10) DEFAULT 'TR',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `products_active_index` (`active`),
    KEY `products_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stocks Table
CREATE TABLE IF NOT EXISTS `stocks` (
    `id` CHAR(36) NOT NULL,
    `product_id` CHAR(36) NOT NULL,
    `value` VARCHAR(500) NOT NULL,
    `status` ENUM('available','assigned') DEFAULT 'available',
    `order_id` CHAR(36) DEFAULT NULL,
    `created_by` VARCHAR(100) DEFAULT NULL,
    `assigned_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `stocks_product_id_status_index` (`product_id`, `status`),
    KEY `stocks_order_id_index` (`order_id`),
    CONSTRAINT `stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
    `id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `product_id` CHAR(36) NOT NULL,
    `product_title` VARCHAR(200) NOT NULL,
    `uc_amount` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `player_id` VARCHAR(50) NOT NULL,
    `player_name` VARCHAR(100) NOT NULL,
    `status` ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    `customer` JSON DEFAULT NULL,
    `delivery` JSON DEFAULT NULL,
    `risk` JSON DEFAULT NULL,
    `meta` JSON DEFAULT NULL,
    `payment_url` VARCHAR(1000) DEFAULT NULL,
    `paid_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `orders_user_id_index` (`user_id`),
    KEY `orders_status_index` (`status`),
    KEY `orders_created_at_index` (`created_at`),
    CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` CHAR(36) NOT NULL,
    `order_id` CHAR(36) NOT NULL,
    `transaction_id` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('pending','success','failed') DEFAULT 'pending',
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'shopier',
    `hash_validated` TINYINT(1) DEFAULT 0,
    `raw_payload` JSON DEFAULT NULL,
    `verified_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `payments_transaction_id_unique` (`transaction_id`),
    KEY `payments_order_id_index` (`order_id`),
    CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tickets Table
CREATE TABLE IF NOT EXISTS `tickets` (
    `id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `category` ENUM('odeme','teslimat','hesap','diger') DEFAULT 'diger',
    `status` ENUM('waiting_admin','waiting_user','closed') DEFAULT 'waiting_admin',
    `user_can_reply` TINYINT(1) DEFAULT 0,
    `closed_by` VARCHAR(100) DEFAULT NULL,
    `closed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `tickets_user_id_index` (`user_id`),
    KEY `tickets_status_index` (`status`),
    CONSTRAINT `tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket Messages Table
CREATE TABLE IF NOT EXISTS `ticket_messages` (
    `id` CHAR(36) NOT NULL,
    `ticket_id` CHAR(36) NOT NULL,
    `sender` ENUM('user','admin') NOT NULL,
    `message` TEXT NOT NULL,
    `admin_username` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ticket_messages_ticket_id_index` (`ticket_id`),
    CONSTRAINT `ticket_messages_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site Settings Table
CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` CHAR(36) NOT NULL,
    `site_name` VARCHAR(100) DEFAULT 'PINLY',
    `meta_title` VARCHAR(200) DEFAULT NULL,
    `meta_description` TEXT DEFAULT NULL,
    `contact_email` VARCHAR(191) DEFAULT NULL,
    `contact_phone` VARCHAR(30) DEFAULT NULL,
    `logo` VARCHAR(500) DEFAULT NULL,
    `favicon` VARCHAR(500) DEFAULT NULL,
    `hero_image` VARCHAR(500) DEFAULT NULL,
    `category_icon` VARCHAR(500) DEFAULT NULL,
    `daily_banner_enabled` TINYINT(1) DEFAULT 1,
    `daily_banner_title` VARCHAR(100) DEFAULT 'Bugüne Özel Fiyatlar',
    `daily_banner_subtitle` VARCHAR(200) DEFAULT NULL,
    `daily_banner_icon` VARCHAR(50) DEFAULT 'fire',
    `daily_countdown_enabled` TINYINT(1) DEFAULT 1,
    `daily_countdown_label` VARCHAR(100) DEFAULT 'Kampanya bitimine',
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shopier Settings Table
CREATE TABLE IF NOT EXISTS `shopier_settings` (
    `id` CHAR(36) NOT NULL,
    `api_key` TEXT DEFAULT NULL,
    `api_secret` TEXT DEFAULT NULL,
    `mode` VARCHAR(20) DEFAULT 'production',
    `is_active` TINYINT(1) DEFAULT 1,
    `updated_by` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OAuth Settings Table
CREATE TABLE IF NOT EXISTS `oauth_settings` (
    `id` CHAR(36) NOT NULL,
    `provider` VARCHAR(50) NOT NULL,
    `enabled` TINYINT(1) DEFAULT 0,
    `client_id` TEXT DEFAULT NULL,
    `client_secret` TEXT DEFAULT NULL,
    `updated_by` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `oauth_settings_provider_unique` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Settings Table
CREATE TABLE IF NOT EXISTS `email_settings` (
    `id` VARCHAR(50) NOT NULL DEFAULT 'main',
    `enable_email` TINYINT(1) DEFAULT 0,
    `from_name` VARCHAR(100) DEFAULT NULL,
    `from_email` VARCHAR(191) DEFAULT NULL,
    `smtp_host` VARCHAR(255) DEFAULT NULL,
    `smtp_port` VARCHAR(10) DEFAULT '587',
    `smtp_secure` TINYINT(1) DEFAULT 0,
    `smtp_user` VARCHAR(255) DEFAULT NULL,
    `smtp_pass` TEXT DEFAULT NULL,
    `test_recipient_email` VARCHAR(191) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Logs Table
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` CHAR(36) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `user_id` CHAR(36) DEFAULT NULL,
    `order_id` CHAR(36) DEFAULT NULL,
    `ticket_id` CHAR(36) DEFAULT NULL,
    `to` VARCHAR(191) NOT NULL,
    `status` ENUM('sent','failed') DEFAULT 'sent',
    `error` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `email_logs_type_user_id_index` (`type`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Legal Pages Table
CREATE TABLE IF NOT EXISTS `legal_pages` (
    `id` CHAR(36) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `content` LONGTEXT NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `legal_pages_slug_unique` (`slug`),
    KEY `legal_pages_slug_index` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Regions Table
CREATE TABLE IF NOT EXISTS `regions` (
    `id` CHAR(36) NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `enabled` TINYINT(1) DEFAULT 1,
    `flag_image_url` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `regions_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews Table
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` CHAR(36) NOT NULL,
    `game` VARCHAR(50) DEFAULT 'pubg',
    `user_name` VARCHAR(100) NOT NULL,
    `rating` TINYINT DEFAULT 5,
    `comment` TEXT DEFAULT NULL,
    `approved` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `reviews_game_approved_index` (`game`, `approved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Game Content Table
CREATE TABLE IF NOT EXISTS `game_content` (
    `game` VARCHAR(50) NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `description` LONGTEXT DEFAULT NULL,
    `default_rating` DECIMAL(3,2) DEFAULT 5.00,
    `default_review_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`game`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Footer Settings Table
CREATE TABLE IF NOT EXISTS `footer_settings` (
    `id` CHAR(36) NOT NULL,
    `quick_links` JSON DEFAULT NULL,
    `categories` JSON DEFAULT NULL,
    `corporate_links` JSON DEFAULT NULL,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SEO Settings Table
CREATE TABLE IF NOT EXISTS `seo_settings` (
    `id` CHAR(36) NOT NULL,
    `ga4_measurement_id` VARCHAR(50) DEFAULT NULL,
    `gsc_verification_code` VARCHAR(100) DEFAULT NULL,
    `enable_analytics` TINYINT(1) DEFAULT 0,
    `enable_search_console` TINYINT(1) DEFAULT 0,
    `active` TINYINT(1) DEFAULT 1,
    `updated_by` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Logs Table
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` CHAR(36) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `actor_id` VARCHAR(100) DEFAULT NULL,
    `entity_type` VARCHAR(50) DEFAULT NULL,
    `entity_id` VARCHAR(100) DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `meta` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `audit_logs_action_index` (`action`),
    KEY `audit_logs_entity_type_index` (`entity_type`),
    KEY `audit_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate Limits Table
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(191) NOT NULL,
    `count` INT DEFAULT 0,
    `window_start` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rate_limits_key_unique` (`key`),
    KEY `rate_limits_key_index` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Security Logs Table
CREATE TABLE IF NOT EXISTS `security_logs` (
    `id` CHAR(36) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `order_id` VARCHAR(100) DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `details` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `security_logs_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Requests Table
CREATE TABLE IF NOT EXISTS `payment_requests` (
    `id` CHAR(36) NOT NULL,
    `order_id` CHAR(36) NOT NULL,
    `api_key_masked` VARCHAR(100) DEFAULT NULL,
    `payment_url` VARCHAR(1000) DEFAULT NULL,
    `request_data` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `payment_requests_order_id_index` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- VARSAYILAN VERİLER
-- =====================================================

-- Varsayılan Admin Kullanıcısı (Sifre: admin123)
INSERT IGNORE INTO `admin_users` (`id`, `username`, `password_hash`, `created_at`, `updated_at`) VALUES
(UUID(), 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW());

-- Varsayılan Bölgeler
INSERT IGNORE INTO `regions` (`id`, `code`, `name`, `enabled`, `sort_order`, `created_at`, `updated_at`) VALUES
(UUID(), 'TR', 'Türkiye', 1, 1, NOW(), NOW()),
(UUID(), 'GLOBAL', 'Küresel', 1, 2, NOW(), NOW()),
(UUID(), 'DE', 'Almanya', 1, 3, NOW(), NOW()),
(UUID(), 'FR', 'Fransa', 1, 4, NOW(), NOW()),
(UUID(), 'JP', 'Japonya', 1, 5, NOW(), NOW());

-- Varsayılan Site Ayarları
INSERT IGNORE INTO `site_settings` (`id`, `site_name`, `meta_title`, `meta_description`, `daily_banner_enabled`, `daily_banner_title`, `daily_banner_icon`, `daily_countdown_enabled`, `daily_countdown_label`, `active`, `created_at`, `updated_at`) VALUES
(UUID(), 'PINLY', 'PINLY – Dijital Kod ve Oyun Satış Platformu', 'PUBG Mobile UC satın al. Güvenilir, hızlı ve uygun fiyatlı UC satış platformu.', 1, 'Bugüne Özel Fiyatlar', 'fire', 1, 'Kampanya bitimine', 1, NOW(), NOW());

-- Varsayılan Oyun İçeriği
INSERT IGNORE INTO `game_content` (`game`, `title`, `description`, `default_rating`, `default_review_count`, `created_at`, `updated_at`) VALUES
('pubg', 'PUBG Mobile', '# PUBG Mobile UC Satın Al\n\nPUBG Mobile, dünyanın en popüler battle royale oyunlarından biridir.', 5.00, 2008, NOW(), NOW());

-- Varsayılan Ürünler
INSERT IGNORE INTO `products` (`id`, `title`, `uc_amount`, `price`, `discount_price`, `discount_percent`, `active`, `sort_order`, `region_code`, `created_at`, `updated_at`) VALUES
(UUID(), '60 UC', 60, 25.00, 19.99, 20.04, 1, 1, 'TR', NOW(), NOW()),
(UUID(), '325 UC', 325, 100.00, 89.99, 10.01, 1, 2, 'TR', NOW(), NOW()),
(UUID(), '660 UC', 660, 200.00, 179.99, 10.01, 1, 3, 'TR', NOW(), NOW()),
(UUID(), '1800 UC', 1800, 500.00, 449.99, 10.00, 1, 4, 'TR', NOW(), NOW()),
(UUID(), '3850 UC', 3850, 1000.00, 899.99, 10.00, 1, 5, 'TR', NOW(), NOW());

-- Varsayılan Yasal Sayfalar
INSERT IGNORE INTO `legal_pages` (`id`, `title`, `slug`, `content`, `is_active`, `order`, `created_at`, `updated_at`) VALUES
(UUID(), 'Hizmet Şartları', 'terms', '# Hizmet Şartları\n\nBu sayfa Hizmet Şartlarını içermektedir.', 1, 1, NOW(), NOW()),
(UUID(), 'Gizlilik Politikası', 'privacy', '# Gizlilik Politikası\n\nBu sayfa Gizlilik Politikasını içermektedir.', 1, 2, NOW(), NOW()),
(UUID(), 'KVKK', 'kvkk', '# KVKK Aydınlatma Metni\n\nBu sayfa KVKK Aydınlatma Metnini içermektedir.', 1, 3, NOW(), NOW()),
(UUID(), 'İade Politikası', 'refund', '# İade Politikası\n\nBu sayfa İade Politikasını içermektedir.', 1, 4, NOW(), NOW());

-- Varsayılan Değerlendirmeler
INSERT IGNORE INTO `reviews` (`id`, `game`, `user_name`, `rating`, `comment`, `approved`, `created_at`, `updated_at`) VALUES
(UUID(), 'pubg', 'Ahmet Yılmaz', 5, 'Harika bir site! UC\'ler anında geldi.', 1, NOW(), NOW()),
(UUID(), 'pubg', 'Mehmet Demir', 5, 'Güvenilir ve hızlı. Teşekkürler!', 1, NOW(), NOW()),
(UUID(), 'pubg', 'Ayşe Kaya', 5, 'En uygun fiyatlar burada. Tavsiye ederim.', 1, NOW(), NOW());
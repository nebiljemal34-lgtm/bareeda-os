-- ============================================================
-- Bareeda Injera E-Commerce Database Schema
-- MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ------------------------------------------------------------
-- Users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `role` ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `name_am` VARCHAR(150) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_categories_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Products
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `name_am` VARCHAR(255) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `description_am` TEXT DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `weight_kg` DECIMAL(8,3) DEFAULT NULL,
    `pieces` INT UNSIGNED NOT NULL DEFAULT 1,
    `image_url` VARCHAR(500) DEFAULT NULL,
    `stock_quantity` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_products_active` (`is_active`),
    KEY `idx_products_price` (`price`),
    KEY `idx_products_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Product ↔ Categories (many-to-many)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_categories` (
    `product_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`product_id`, `category_id`),
    KEY `idx_pc_category` (`category_id`),
    CONSTRAINT `fk_pc_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pc_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Orders
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `guest_name` VARCHAR(150) DEFAULT NULL,
    `guest_phone` VARCHAR(30) DEFAULT NULL,
    `guest_email` VARCHAR(190) DEFAULT NULL,
    `order_number` VARCHAR(30) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending','confirmed','preparing','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
    `payment_method` ENUM('cod','bank_transfer','mobile_money','ebirr') NOT NULL DEFAULT 'cod',
    `payment_status` ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending',
    `delivery_address` TEXT NOT NULL,
    `delivery_notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_orders_number` (`order_number`),
    KEY `idx_orders_user` (`user_id`),
    KEY `idx_orders_status` (`status`),
    KEY `idx_orders_created` (`created_at`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Order Items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity` INT UNSIGNED NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_oi_order` (`order_id`),
    KEY `idx_oi_product` (`product_id`),
    CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Payment Transactions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `public_id` CHAR(36) NOT NULL,
    `order_id` INT UNSIGNED NOT NULL,
    `provider` VARCHAR(40) NOT NULL,
    `channel` ENUM('bank','wallet') NOT NULL,
    `mode` ENUM('simulation','sandbox','live') NOT NULL DEFAULT 'simulation',
    `status` ENUM('pending','paid','failed','expired') NOT NULL DEFAULT 'pending',
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'ETB',
    `provider_reference` VARCHAR(120) DEFAULT NULL,
    `failure_reason` VARCHAR(255) DEFAULT NULL,
    `verified_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payment_public_id` (`public_id`),
    UNIQUE KEY `uq_payment_provider_reference` (`provider`, `provider_reference`),
    KEY `idx_payment_order_status` (`order_id`, `status`),
    CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_id` VARCHAR(120) NOT NULL,
    `transaction_id` BIGINT UNSIGNED DEFAULT NULL,
    `provider` VARCHAR(40) NOT NULL,
    `event_type` VARCHAR(40) NOT NULL,
    `payload_hash` CHAR(64) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `signature_valid` TINYINT(1) NOT NULL DEFAULT 0,
    `processing_result` VARCHAR(80) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payment_event_id` (`event_id`),
    KEY `idx_payment_event_transaction` (`transaction_id`),
    CONSTRAINT `fk_payment_event_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `payment_transactions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Subscriptions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `frequency` ENUM('weekly','biweekly','monthly') NOT NULL DEFAULT 'weekly',
    `next_delivery_date` DATE NOT NULL,
    `status` ENUM('active','paused','cancelled') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sub_user` (`user_id`),
    KEY `idx_sub_next_delivery` (`next_delivery_date`, `status`),
    CONSTRAINT `fk_sub_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sub_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Subscription Deliveries
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscription_deliveries` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `subscription_id` INT UNSIGNED NOT NULL,
    `scheduled_date` DATE NOT NULL,
    `status` ENUM('pending','delivered','skipped') NOT NULL DEFAULT 'pending',
    `delivered_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sd_sub` (`subscription_id`),
    KEY `idx_sd_scheduled` (`scheduled_date`, `status`),
    CONSTRAINT `fk_sd_sub` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Cart (logged-in users)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cart` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cart_user_product` (`user_id`, `product_id`),
    KEY `idx_cart_user` (`user_id`),
    CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Cart Sessions (guest users)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cart_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(128) NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cs_session_product` (`session_id`, `product_id`),
    KEY `idx_cs_session` (`session_id`),
    CONSTRAINT `fk_cs_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Coupons
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coupons` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `discount_type` ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    `discount_value` DECIMAL(10,2) NOT NULL,
    `min_order_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `max_uses` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = unlimited',
    `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `expires_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_coupons_code` (`code`),
    KEY `idx_coupons_active` (`is_active`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Delivery Zones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `delivery_zones` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `zone_name` VARCHAR(150) NOT NULL,
    `fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_dz_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Website Settings and Order Audit Trail
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_settings` (
    `setting_key` VARCHAR(80) NOT NULL,
    `setting_value` LONGTEXT DEFAULT NULL,
    `updated_by` INT UNSIGNED DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`),
    CONSTRAINT `fk_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `actor_user_id` INT UNSIGNED DEFAULT NULL,
    `event_type` VARCHAR(40) NOT NULL,
    `from_status` VARCHAR(40) DEFAULT NULL,
    `to_status` VARCHAR(40) DEFAULT NULL,
    `note` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_events_order` (`order_id`, `created_at`),
    CONSTRAINT `fk_order_events_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_events_user` FOREIGN KEY (`actor_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `analytics_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_type` ENUM('page_view','qr_scan') NOT NULL,
    `visitor_token` CHAR(32) NOT NULL,
    `path` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_analytics_type_date` (`event_type`, `created_at`),
    KEY `idx_analytics_visitor_date` (`visitor_token`, `created_at`),
    KEY `idx_analytics_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `telegram_outbox` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_type` VARCHAR(40) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('simulated','pending','sent','failed') NOT NULL DEFAULT 'simulated',
    `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_error` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `sent_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_telegram_status` (`status`, `created_at`),
    KEY `idx_telegram_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin user (DEFAULT PASSWORD — change immediately after first login!)
INSERT INTO `users` (`email`, `password_hash`, `first_name`, `last_name`, `phone`, `role`) VALUES
('admin@bareeda-injera.com', '$2y$12$8HPLu2dR50mu3e//te6wx.R/Sp.qoyiV787cEf8soiiQcXKRB2FdW', 'Bareeda', 'Admin', '+251940434748', 'admin');

-- Categories
INSERT INTO `categories` (`name`, `name_am`, `sort_order`) VALUES
('Injera', 'እንጀራ', 1),
('Beverages', 'መጠጦች', 2);

-- Products
INSERT INTO `products` (`name`, `name_am`, `description`, `description_am`, `price`, `weight_kg`, `pieces`, `image_url`, `stock_quantity`, `is_active`) VALUES
('Bareeda Super', 'በሬዳ ሱፐር', 'Pack of 4 injera at 42 ETB each. Our premium single-serve injera. Perfect for individual meals. Light, spongy, and freshly baked from 100% teff flour.', 'የ4 እንጀራ ጥቅል፣ እያንዳንዱ 42 ብር። ፕሪሚየም ነጠላ አገልግሎት እንጀራ። ለግለሰብ ምግቦች ተስማሚ። ቀላል፣ ስፖንጅ እና ከ100% የጤፍ ዱቄት ትኩስ የተጋገረ።', 168.00, 0.500, 4, '/assets/images/bareeda-label-sticker.png', 200, 1),
('Bareeda Family', 'በሬዳ ፋሚሊ', 'Pack of 12 injera at 42 ETB each. Our best-selling family size injera. Feeds 4-6 people. Perfect for family meals and gatherings.', 'የ12 እንጀራ ጥቅል፣ እያንዳንዱ 42 ብር። በጣም የሚሸጥ የቤተሰብ መጠን እንጀራ። 4-6 ሰዎችን ያክብራል። ለቤተሰብ ምግቦች እና ስብሰባዎች ተስማሚ።', 504.00, 1.500, 12, '/assets/images/bareeda-label-sticker.png', 150, 1),
('Bareeda Horca', 'በሬዳ ሆርቃ', 'Pack of 50 injera at 42 ETB each. Our large party-size injera. Feeds 8-12 people. Ideal for celebrations, events, and large gatherings.', 'የ50 እንጀራ ጥቅል፣ እያንዳንዱ 42 ብር። ትልቅ የድግስ መጠን እንጀራ። 8-12 ሰዎችን ያክብራል። ለበዓላት፣ ዝግጅቶች እና ትላልቅ ስብሰባዎች ተስማሚ።', 2100.00, 3.000, 50, '/assets/images/bareeda-label-sticker.png', 80, 1);

-- Product ↔ Category mapping
INSERT INTO `product_categories` (`product_id`, `category_id`) VALUES
(1, 1),
(2, 1),
(3, 1);

-- Delivery Zones
INSERT INTO `delivery_zones` (`zone_name`, `fee`, `is_active`) VALUES
('Dire Dawa - Jerba Aliyi', 50.00, 1),
('Dire Dawa - Kezira', 50.00, 1),
('Dire Dawa - Magala', 50.00, 1),
('Dire Dawa - Ashewa Meda', 60.00, 1),
('Dire Dawa - Outside City Center', 80.00, 1);

-- Sample Coupon
INSERT INTO `coupons` (`code`, `discount_type`, `discount_value`, `min_order_amount`, `max_uses`, `is_active`, `expires_at`) VALUES
('WELCOME10', 'percentage', 10.00, 100.00, 100, 1, '2027-12-31 23:59:59');

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Bareeda Injera'),
('contact_email', 'info@bareeda-injera.com'),
('contact_phone', '+251940434748'),
('business_city', 'Dire Dawa'),
('business_address', 'Dire Dawa, Ethiopia'),
('default_delivery_fee', '50'),
('free_delivery_threshold', '500'),
('free_delivery_min_pieces', '50'),
('min_order_amount', '100'),
('accepting_orders', '1'),
('announcement', ''),
('telegram_url', 'https://t.me/bareedainjera'),
('facebook_url', 'https://www.facebook.com/bareedainjera'),
('instagram_url', 'https://www.instagram.com/bareedainjera'),
('telegram_bot_token', ''),
('telegram_chat_id', ''),
('telegram_live_enabled', '0'),
('telegram_events', 'new_order,new_registration,contact_message,qr_scan'),
('telegram_digest_enabled', '1'),
('telegram_digest_hour', '8'),
('telegram_last_digest_date', '');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- IMPORTANT: After importing this schema, change the admin
-- password immediately via the admin panel or by generating
-- a new bcrypt hash. See DEPLOYMENT_CHECKLIST.md for details.
-- ============================================================

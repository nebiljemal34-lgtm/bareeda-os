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
    `user_id` INT UNSIGNED NOT NULL,
    `order_number` VARCHAR(30) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending','confirmed','preparing','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
    `payment_method` ENUM('cod','bank_transfer','mobile_money') NOT NULL DEFAULT 'cod',
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
INSERT INTO `products` (`name`, `name_am`, `description`, `description_am`, `price`, `weight_kg`, `image_url`, `stock_quantity`, `is_active`) VALUES
('Bareeda Super', 'ባሪዳ ሱፐር', 'Our premium single-serve injera. Perfect for individual meals. Light, spongy, and freshly baked from 100% teff flour.', 'ፕሪሚየም ነጠላ አገልግሎት እንጀራ። ለግለሰብ ምግቦች ተስማሚ። ቀላል፣ ስፖንጅ እና ከ100% የጤፍ ዱቄት ትኩስ የተጋገረ።', 80.00, 0.500, '/assets/images/products/bareeda-super.jpg', 200, 1),
('Bareeda Family', 'ባሪዳ ፋሚሊ', 'Our best-selling family size injera. Feeds 4-6 people. Perfect for family meals and gatherings.', 'በጣም የሚሸጥ የቤተሰብ መጠን እንጀራ። 4-6 ሰዎችን ያክብራል። ለቤተሰብ ምግቦች እና ስብሰባዎች ተስማሚ።', 200.00, 1.500, '/assets/images/products/bareeda-family.jpg', 150, 1),
('Bareeda Horca', 'ባሪዳ ሆርቃ', 'Our large party-size injera. Feeds 8-12 people. Ideal for celebrations, events, and large gatherings.', 'ትልቅ የድግስ መጠን እንጀራ። 8-12 ሰዎችን ያክብራል። ለበዓላት፣ ዝግጅቶች እና ትላልቅ ስብሰባዎች ተስማሚ።', 380.00, 3.000, '/assets/images/products/bareeda-horca.jpg', 80, 1);

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

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- IMPORTANT: After importing this schema, change the admin
-- password immediately via the admin panel or by generating
-- a new bcrypt hash. See DEPLOYMENT_CHECKLIST.md for details.
-- ============================================================

SET NAMES utf8mb4;

ALTER TABLE `orders`
    MODIFY `payment_method` ENUM('cod','bank_transfer','mobile_money','ebirr') NOT NULL DEFAULT 'cod';

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

INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Bareeda Injera'),
('contact_email', 'info@bareeda-injera.com'),
('contact_phone', '+251940434748'),
('business_city', 'Dire Dawa'),
('business_address', 'Dire Dawa, Ethiopia'),
('default_delivery_fee', '50'),
('free_delivery_threshold', '500'),
('min_order_amount', '100'),
('accepting_orders', '1'),
('announcement', ''),
('telegram_url', 'https://t.me/bareedainjera'),
('facebook_url', 'https://www.facebook.com/bareedainjera'),
('instagram_url', 'https://www.instagram.com/bareedainjera');

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

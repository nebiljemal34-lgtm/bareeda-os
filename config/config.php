<?php
/**
 * Bareeda Injera - Application Configuration
 *
 * Central configuration file. All constants are defined here.
 * For cPanel deployment, update DB credentials to match the
 * MySQL database created in the hosting control panel.
 */

// ─── Database ───────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'bareeda_injera');
define('DB_USER', 'bareeda_user');
define('DB_PASS', 'CHANGE_ME_IN_PRODUCTION');
define('DB_CHARSET', 'utf8mb4');

// ─── Application ────────────────────────────────────────────
define('APP_NAME', 'Bareeda Injera');
define('APP_URL', 'https://www.bareeda-injera.com');
define('APP_EMAIL', 'info@bareeda-injera.com');
define('APP_PHONE', '+251940434748');
define('APP_VERSION', '1.0.0');

// ─── Paths ──────────────────────────────────────────────────
define('BASE_PATH', dirname(__DIR__) . '/');
define('PUBLIC_PATH', BASE_PATH . 'public/');
define('UPLOAD_PATH', BASE_PATH . 'uploads/');
define('LOG_PATH', BASE_PATH . 'logs/');

// ─── Security ───────────────────────────────────────────────
define('SESSION_LIFETIME', 3600);           // seconds (1 hour)
define('CSRF_TOKEN_LIFETIME', 3600);        // seconds (1 hour)
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

// Cron endpoint secret key — CHANGE THIS to a random string in production!
// The cron URL must include ?key=<this value> to be accepted.
define('CRON_SECRET_KEY', 'CHANGE_ME_CRON_SECRET_KEY_IN_PRODUCTION');

// ─── Currency / Locale ─────────────────────────────────────
define('CURRENCY_SYMBOL', 'ETB');
define('CURRENCY_POSITION', 'after');       // "100 ETB"
define('LOCALE', 'en_US');

// ─── Orders & Delivery ─────────────────────────────────────
define('DEFAULT_DELIVERY_FEE', 50);         // ETB
define('MIN_ORDER_AMOUNT', 100);            // ETB
define('FREE_DELIVERY_THRESHOLD', 500);     // ETB – orders above this get free delivery

// ─── Pagination ─────────────────────────────────────────────
define('ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 25);

// ─── Upload ─────────────────────────────────────────────────
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// ─── Timezone ───────────────────────────────────────────────
date_default_timezone_set('Africa/Addis_Ababa');

// ─── Error Handling ─────────────────────────────────────────
// In production, set display_errors to 0 and log errors to file.
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_PATH . 'php_errors.log');
error_reporting(E_ALL);

// ─── Session Configuration ──────────────────────────────────
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_samesite', 'Strict');

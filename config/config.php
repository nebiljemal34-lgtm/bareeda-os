<?php
/**
 * Bareeda Injera - Application Configuration
 *
 * Central configuration file. All constants are defined here.
 * For cPanel deployment, update DB credentials to match the
 * MySQL database created in the hosting control panel.
 */

// ─── Database ───────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'bareeda_injera');
define('DB_USER', getenv('DB_USER') ?: 'bareeda_user');
define('DB_PASS', getenv('DB_PASS') ?: 'CHANGE_ME_IN_PRODUCTION');
define('DB_CHARSET', 'utf8mb4');

// ─── Application ────────────────────────────────────────────
define('APP_NAME', getenv('APP_NAME') ?: 'Bareeda Injera');
define('APP_URL', rtrim(getenv('APP_URL') ?: 'https://www.bareeda-injera.com', '/'));
define('APP_EMAIL', getenv('APP_EMAIL') ?: 'info@bareeda-injera.com');
define('APP_PHONE', getenv('APP_PHONE') ?: '+251940434748');
define('APP_VERSION', '1.1.0');

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
$_cron_raw = getenv('CRON_SECRET_KEY') ?: '';
define('CRON_SECRET_KEY', $_cron_raw);
define('CRON_SECRET_IS_PLACEHOLDER', empty($_cron_raw)
    || $_cron_raw === 'CHANGE_ME_CRON_SECRET_KEY_IN_PRODUCTION'
    || strlen($_cron_raw) < 16);
unset($_cron_raw);
define('PAYMENT_MODE', getenv('PAYMENT_MODE') ?: (defined('APP_ENV') && APP_ENV === 'production' ? 'disabled' : 'simulation'));

// BLOCKER 1 FIX: Fail-closed for PAYMENT_WEBHOOK_SECRET (same pattern as CRON_SECRET_KEY)
$_webhook_raw = getenv('PAYMENT_WEBHOOK_SECRET') ?: '';
define('PAYMENT_WEBHOOK_SECRET', $_webhook_raw);
define('PAYMENT_WEBHOOK_IS_PLACEHOLDER', empty($_webhook_raw)
    || $_webhook_raw === 'local-simulation-secret'
    || $_webhook_raw === 'CHANGE_ME_IN_PRODUCTION'
    || strlen($_webhook_raw) < 16);
unset($_webhook_raw);

// ─── Currency / Locale ─────────────────────────────────────
define('CURRENCY_CODE', 'ETB');
define('CURRENCY_POSITION', 'after');       // "100 ETB"
define('LOCALE', 'en_US');

// ─── Orders & Delivery ─────────────────────────────────────
define('DEFAULT_DELIVERY_FEE', 50);         // ETB
define('MIN_ORDER_AMOUNT', 100);            // ETB
define('FREE_DELIVERY_THRESHOLD', 500);     // ETB – legacy threshold (superseded by piece count)
define('FREE_DELIVERY_MIN_PIECES', 50);     // injera pieces – orders with MORE than this get free delivery

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
define('SESSION_SECURE_COOKIE', filter_var(
    getenv('SESSION_SECURE_COOKIE') ?: (defined('APP_ENV') && APP_ENV === 'production' ? '1' : '0'),
    FILTER_VALIDATE_BOOLEAN
));

// G-04 FIX: Use project-local session path outside web root
$_sessionPath = BASE_PATH . 'storage/sessions/';
if (!is_dir($_sessionPath)) {
    @mkdir($_sessionPath, 0700, true);
}
ini_set('session.save_path', $_sessionPath);
unset($_sessionPath);

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', SESSION_SECURE_COOKIE ? '1' : '0');
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_samesite', 'Strict');

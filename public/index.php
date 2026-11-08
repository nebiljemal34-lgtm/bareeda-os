<?php
/**
 * Bareeda Injera - Front Controller
 *
 * Entry point for all HTTP requests.
 * Located in /public/index.php — web server document root
 * should point to this /public directory.
 *
 * Responsibilities:
 *   1. Start session securely
 *   2. Load configuration
 *   3. Load database connection
 *   4. Load helper functions
 *   5. Define routes
 *   6. Dispatch the request
 */

// ─── 1. Bootstrap ───────────────────────────────────────────

// Define application environment (set 'production' in cPanel)
if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
}

// Ensure we're in the right directory
chdir(dirname(__DIR__));

// ─── 2. Load Configuration ─────────────────────────────────

require_once __DIR__ . '/../config/config.php';

// Ensure log directory exists
if (!is_dir(LOG_PATH)) {
    @mkdir(LOG_PATH, 0755, true);
}

// Ensure upload directory exists
if (!is_dir(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0755, true);
}

// ─── 3. Load Core Classes ───────────────────────────────────

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/router.php';
require_once __DIR__ . '/../classes/SiteSettings.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/PaymentGateway.php';
require_once __DIR__ . '/../classes/Analytics.php';
require_once __DIR__ . '/../classes/TelegramNotifier.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Subscription.php';

// ─── 4. Secure Session Start ────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    // Session cookie settings (already set in config, but enforce here)
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => SESSION_SECURE_COOKIE,
        'httponly'  => true,
        'samesite'  => 'Strict',
    ]);

    session_start();
}

// ─── 5. Initialize Core Services ────────────────────────────

$db   = Database::getInstance();
$auth = new Auth();
$cart = new Cart();

// Make services available globally (alternative: use a DI container)
$GLOBALS['db']   = $db;
$GLOBALS['auth'] = $auth;
$GLOBALS['cart'] = $cart;

// ─── 6. Define Routes ───────────────────────────────────────

$router = new Router(BASE_PATH . 'controllers/');

// --- Public Routes ---
$router->get('/', 'HomeController@index');
$router->get('/products', 'ProductController@index');
$router->get('/products/{id}', 'ProductController@show');
$router->get('/categories/{id}', 'CategoryController@show');
$router->get('/search', 'ProductController@search');
$router->get('/links', 'LinkController@index');
$router->get('/links/', 'LinkController@index');
$router->post('/analytics/qr-scan', 'AnalyticsController@qrScan');

// --- Cart Routes ---
$router->get('/cart', 'CartController@index');
$router->post('/cart/add', 'CartController@add', ['csrf']);
$router->post('/cart/update', 'CartController@update', ['csrf']);
$router->post('/cart/remove', 'CartController@remove', ['csrf']);
$router->post('/cart/clear', 'CartController@clear', ['csrf']);

// --- Auth Routes ---
$router->get('/login', 'AuthController@loginForm', ['guest']);
$router->post('/login', 'AuthController@login', ['csrf', 'guest']);
$router->get('/register', 'AuthController@registerForm', ['guest']);
$router->post('/register', 'AuthController@register', ['csrf', 'guest']);
$router->get('/logout', 'AuthController@logout');
$router->get('/forgot-password', 'AuthController@forgotPasswordForm');
$router->post('/forgot-password', 'AuthController@forgotPassword', ['csrf']);

// --- Checkout / Orders ---
$router->get('/checkout', 'CheckoutController@index');
$router->post('/checkout', 'CheckoutController@process', ['csrf']);
$router->get('/orders', 'OrderController@index', ['auth']);
$router->get('/orders/{id}', 'OrderController@show', ['auth']);
$router->get('/order/confirmation/{orderNumber}', 'OrderController@confirmation');

// Payment initialization, automatic verification, and signed provider callbacks
$router->get('/payment/{publicId}', 'PaymentController@show', ['auth']);
$router->get('/payment/{publicId}/status', 'PaymentController@status', ['auth']);
$router->post('/payment/{publicId}/simulate', 'PaymentController@simulate', ['auth', 'csrf']);
$router->post('/payment/{publicId}/retry', 'PaymentController@retry', ['auth', 'csrf']);
$router->post('/payment/callback/{provider}', 'PaymentController@callback');

// --- Subscriptions ---
$router->get('/subscriptions', 'SubscriptionController@index', ['auth']);
$router->post('/subscriptions/create', 'SubscriptionController@create', ['auth', 'csrf']);
$router->post('/subscriptions/{id}/pause', 'SubscriptionController@pause', ['auth', 'csrf']);
$router->post('/subscriptions/{id}/resume', 'SubscriptionController@resume', ['auth', 'csrf']);
$router->post('/subscriptions/{id}/cancel', 'SubscriptionController@cancel', ['auth', 'csrf']);

// --- Coupon ---
$router->post('/coupon/apply', 'CouponController@apply', ['csrf']);
$router->post('/coupon/remove', 'CouponController@remove', ['csrf']);

// --- Account ---
$router->get('/account', 'AccountController@index', ['auth']);
$router->post('/account/update', 'AccountController@update', ['auth', 'csrf']);
$router->post('/account/change-password', 'AccountController@changePassword', ['auth', 'csrf']);

// --- Admin Routes ---
$router->get('/admin', 'Admin\DashboardController@index', ['auth', 'admin']);
$router->get('/admin/dashboard', 'Admin\DashboardController@index', ['auth', 'admin']);
$router->get('/admin/analytics', 'Admin\AnalyticsController@index', ['auth', 'admin']);

// Admin Products
$router->get('/admin/products', 'Admin\ProductController@index', ['auth', 'admin']);
$router->get('/admin/products/create', 'Admin\ProductController@create', ['auth', 'admin']);
$router->post('/admin/products/store', 'Admin\ProductController@store', ['auth', 'admin', 'csrf']);
$router->get('/admin/products/{id}/edit', 'Admin\ProductController@edit', ['auth', 'admin']);
$router->post('/admin/products/{id}/update', 'Admin\ProductController@update', ['auth', 'admin', 'csrf']);
$router->post('/admin/products/{id}/delete', 'Admin\ProductController@delete', ['auth', 'admin', 'csrf']);

// Admin Orders
$router->get('/admin/orders', 'Admin\OrderController@index', ['auth', 'admin']);
$router->get('/admin/orders/{id}', 'Admin\OrderController@show', ['auth', 'admin']);
$router->post('/admin/orders/{id}/status', 'Admin\OrderController@updateStatus', ['auth', 'admin', 'csrf']);
$router->post('/admin/orders/{id}/payment', 'Admin\OrderController@updatePaymentStatus', ['auth', 'admin', 'csrf']);

// Admin Subscriptions
$router->get('/admin/subscriptions', 'Admin\SubscriptionController@index', ['auth', 'admin']);
$router->get('/admin/subscriptions/{id}', 'Admin\SubscriptionController@show', ['auth', 'admin']);
$router->post('/admin/subscriptions/{id}/status', 'Admin\SubscriptionController@updateStatus', ['auth', 'admin', 'csrf']);
$router->post('/admin/subscriptions/process', 'Admin\SubscriptionController@updateStatus', ['auth', 'admin', 'csrf']);

// Admin Users
$router->get('/admin/users', 'Admin\UserController@index', ['auth', 'admin']);
$router->get('/admin/users/{id}', 'Admin\UserController@show', ['auth', 'admin']);
$router->post('/admin/users/{id}/role', 'Admin\UserController@updateRole', ['auth', 'admin', 'csrf']);

// Admin Reports
$router->get('/admin/reports/sales', 'Admin\ReportController@sales', ['auth', 'admin']);
$router->get('/admin/reports/products', 'Admin\ReportController@products', ['auth', 'admin']);
$router->get('/admin/reports/customers', 'Admin\ReportController@customers', ['auth', 'admin']);

// Admin Website Settings
$router->get('/admin/settings', 'Admin\SettingController@index', ['auth', 'admin']);
$router->post('/admin/settings', 'Admin\SettingController@update', ['auth', 'admin', 'csrf']);
$router->post('/admin/settings/telegram-test', 'Admin\SettingController@telegramTest', ['auth', 'admin', 'csrf']);

// Admin Telegram Alerts
$router->get('/admin/telegram', 'Admin\TelegramController@index', ['auth', 'admin']);
$router->post('/admin/telegram/{id}/retry', 'Admin\TelegramController@retry', ['auth', 'admin', 'csrf']);

// Admin Categories
$router->get('/admin/categories', 'AdminCategoryController@index', ['auth', 'admin']);
$router->post('/admin/categories/store', 'AdminCategoryController@store', ['auth', 'admin', 'csrf']);
$router->post('/admin/categories/{id}/update', 'AdminCategoryController@update', ['auth', 'admin', 'csrf']);
$router->post('/admin/categories/{id}/delete', 'AdminCategoryController@delete', ['auth', 'admin', 'csrf']);

// Admin Coupons
$router->get('/admin/coupons', 'AdminCouponController@index', ['auth', 'admin']);
$router->post('/admin/coupons/store', 'AdminCouponController@store', ['auth', 'admin', 'csrf']);
$router->post('/admin/coupons/{id}/toggle', 'AdminCouponController@toggle', ['auth', 'admin', 'csrf']);

// Admin Delivery Zones
$router->get('/admin/delivery-zones', 'AdminZoneController@index', ['auth', 'admin']);
$router->post('/admin/delivery-zones/store', 'AdminZoneController@store', ['auth', 'admin', 'csrf']);
$router->post('/admin/delivery-zones/{id}/update', 'AdminZoneController@update', ['auth', 'admin', 'csrf']);
$router->post('/admin/delivery-zones/{id}/toggle', 'AdminZoneController@toggle', ['auth', 'admin', 'csrf']);

// --- API Routes (JSON) ---
$router->get('/api/products', 'ApiController@products');
$router->get('/api/products/{id}', 'ApiController@product');
$router->get('/api/cart', 'ApiController@cart');
$router->get('/api/cart/count', 'ApiController@cartCount');

// --- Static Pages ---
$router->get('/about', 'PageController@about');
$router->get('/contact', 'PageController@contact');
$router->post('/contact', 'PageController@submitContact', ['csrf']);
$router->get('/privacy', 'PageController@privacy');
$router->get('/terms', 'PageController@terms');

// --- Cron / Webhook ---
$router->get('/cron/process-subscriptions', 'CronController@processSubscriptions');
$router->get('/cron/telegram', 'CronController@telegram');

// ─── 7. Dispatch ────────────────────────────────────────────

Analytics::trackPageView();
$router->dispatch();

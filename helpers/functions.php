<?php
/**
 * Bareeda Injera - Helper Functions
 *
 * Global utility functions used throughout the application.
 */

function setting(string $key, string $default = ''): string
{
    return SiteSettings::get($key, $default);
}

/**
 * Minimum injera piece count for free delivery. Orders must exceed this
 * (strictly more than) to qualify; exactly this many pieces still pays the fee.
 */
function freeDeliveryMinPieces(): int
{
    return max(0, (int) setting('free_delivery_min_pieces', (string) FREE_DELIVERY_MIN_PIECES));
}

/**
 * Number of injera in one pack of a product.
 */
function packPieces(array $product): int
{
    return max(1, (int) ($product['pieces'] ?? 1));
}

/**
 * Price of a single injera, derived from pack price ÷ pack pieces.
 */
function perPiecePrice(array $product): float
{
    return round((float) $product['price'] / packPieces($product), 2);
}

/**
 * Customer-facing pack info: how many injera per pack and the single-injera price.
 * $detail renders larger stat tiles for the product page; default is a compact card row.
 */
function productPackMeta(array $product, bool $detail = false): string
{
    $pieces = packPieces($product);
    $each   = perPiecePrice($product);

    if ($detail) {
        return '<div class="pack-meta pack-meta-detail">'
            . '<div class="pack-tile"><span class="pack-tile-value">' . $pieces . '</span> <span class="pack-tile-label">injera per pack</span></div>'
            . '<div class="pack-tile"><span class="pack-tile-value">' . formatPrice($each) . '</span> <span class="pack-tile-label">per single injera</span></div>'
            . '</div>';
    }

    return '<div class="pack-meta">'
        . '<span class="pack-chip">' . icon('package', 14) . '<strong>' . $pieces . '</strong>&nbsp;injera / pack</span>'
        . '<span class="pack-each"><strong>' . formatPrice($each) . '</strong> per injera</span>'
        . '</div>';
}

function icon(string $name, int $size = 24): string
{
    $paths = [
        'bag' => '<path d="M4 8h16l-1 12H5L4 8Z"/><path d="M8 8a4 4 0 0 1 8 0"/>',
        'leaf' => '<path d="M20 4C12 4 5 8 5 15c0 3 2 5 5 5 7 0 10-8 10-16Z"/><path d="M4 21c3-6 7-9 13-12"/>',
        'flame' => '<path d="M12 22c4 0 7-3 7-7 0-5-4-8-6-13-1 4-3 6-5 8-2 2-3 4-3 6 0 3 3 6 7 6Z"/><path d="M9 18c0-2 2-3 3-5 2 2 3 3 3 5a3 3 0 0 1-6 0Z"/>',
        'truck' => '<path d="M3 6h11v11H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'landmark' => '<path d="m3 10 9-6 9 6M5 10v8M9 10v8M15 10v8M19 10v8M3 21h18"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'map-pin' => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        'package' => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 8 9 5 9-5M3 8v9l9 5 9-5V8M12 13v9"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'cart' => '<circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M3 4h2l2.5 11h11l2-7H6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'send' => '<path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/>',
        'facebook' => '<path d="M14 8h4V3h-4c-3 0-5 2-5 5v3H6v5h3v5h5v-5h4l1-5h-5V8Z"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".5" fill="currentColor"/>',
        'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z" fill="currentColor"/>',
    ];
    $size = max(8, min(128, $size));
    $content = $paths[$name] ?? $paths['bag'];
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $content . '</svg>';
}

// ─── Sanitization ───────────────────────────────────────────

/**
 * Sanitize user input (HTML context).
 *
 * @param  mixed $input
 * @return string
 */
function sanitize($input): string
{
    if (is_array($input)) {
        return '';
    }
    return htmlspecialchars(trim((string) $input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize a string for use in a database LIKE clause.
 *
 * @param  string $input
 * @return string
 */
function sanitize_like(string $input): string
{
    return addcslashes(sanitize($input), '%_');
}

/**
 * Sanitize email address.
 *
 * @param  string $email
 * @return string
 */
function sanitize_email(string $email): string
{
    return filter_var(trim(strtolower($email)), FILTER_SANITIZE_EMAIL);
}

/**
 * Validate email format.
 *
 * @param  string $email
 * @return bool
 */
function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ─── Redirect ───────────────────────────────────────────────

/**
 * Redirect to a URL and exit.
 *
 * @param  string $url   Relative or absolute URL
 * @param  int    $code  HTTP status code (default 302)
 */
function redirect(string $url, int $code = 302): void
{
    // If relative URL, prepend APP_URL
    if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
        $url = rtrim(APP_URL, '/') . $url;
    }

    http_response_code($code);
    header('Location: ' . $url);
    exit;
}

/**
 * Redirect back to the previous page.
 * Only allows same-origin relative URLs to prevent open redirect attacks.
 */
function redirect_back(): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '/';

    // Parse the referer to ensure it's the same origin
    $refererHost = parse_url($referer, PHP_URL_HOST);
    $appHost     = parse_url(APP_URL, PHP_URL_HOST);

    // Only follow the referer if it's the same host, otherwise fall back to /
    if ($refererHost && $refererHost !== $appHost) {
        $referer = '/';
    } elseif (strpos($referer, '/') === 0 && strpos($referer, '//') !== 0) {
        // Relative URL — safe to use
    } else {
        // Extract just the path + query from the referer
        $path = parse_url($referer, PHP_URL_PATH) ?? '/';
        $query = parse_url($referer, PHP_URL_QUERY);
        $referer = $query ? "$path?$query" : $path;
    }

    redirect($referer);
}

// ─── Flash Messages ─────────────────────────────────────────

/**
 * Set or get a flash message.
 *
 * Set:  flash('success', 'Order placed!')
 * Get:  flash('success')  → returns and clears the message
 *
 * @param  string      $type     success | error | warning | info
 * @param  string|null $message  If provided, sets the flash; if null, gets it
 * @return string|null           The message (when getting)
 */
function flash(string $type, ?string $message = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $key = 'flash_' . $type;

    if ($message !== null) {
        // Set
        $_SESSION[$key] = $message;
        return null;
    }

    // Get and clear
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }

    return null;
}

/**
 * Display all flash messages as HTML.
 *
 * @return string
 */
function display_flash_messages(): string
{
    $types  = ['success', 'error', 'warning', 'info'];
    $output = '';

    foreach ($types as $type) {
        $message = flash($type);
        if ($message !== null) {
            $cssClass = [
                'success' => 'alert-success',
                'error'   => 'alert-danger',
                'warning' => 'alert-warning',
                'info'    => 'alert-info',
            ][$type] ?? 'alert-info';

            $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            $output .= '<div class="alert ' . $cssClass . ' alert-dismissible fade show" role="alert">';
            $output .= $safeMessage;
            $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            $output .= '</div>';
        }
    }

    return $output;
}

// ─── Old Input ──────────────────────────────────────────────

/**
 * Retrieve old form input value from session (for repopulating forms after validation error).
 *
 * @param  string $key
 * @param  string $default
 * @return string
 */
function old(string $key, string $default = ''): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['_old_input'][$key])) {
        $value = $_SESSION['_old_input'][$key];
        unset($_SESSION['_old_input'][$key]);
        return $value;
    }

    return $default;
}

/**
 * Store current POST data in session for retrieval via old().
 */
function store_old_input(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['_old_input'] = [];
    foreach ($_POST as $key => $value) {
        if (is_string($value)) {
            $_SESSION['_old_input'][$key] = $value;
        }
    }
}

// ─── Formatting ─────────────────────────────────────────────

/**
 * Format a price amount in ETB.
 *
 * @param  float $amount
 * @return string  e.g., "150.00 ETB"
 */
function formatPrice(float $amount): string
{
    $formatted = number_format($amount, 2, '.', ',');
    $symbol    = defined('CURRENCY_CODE') ? CURRENCY_CODE : 'ETB';
    $position  = defined('CURRENCY_POSITION') ? CURRENCY_POSITION : 'after';

    if ($position === 'before') {
        return $symbol . ' ' . $formatted;
    }
    return $formatted . ' ' . $symbol;
}

/**
 * Format a date string.
 *
 * @param  string $date    Y-m-d or datetime
 * @param  string $format  PHP date format
 * @return string
 */
function formatDate(string $date, string $format = 'M j, Y'): string
{
    if (empty($date)) {
        return '';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : $date;
}

/**
 * Format a datetime string.
 */
function formatDateTime(string $datetime, string $format = 'M j, Y g:i A'): string
{
    return formatDate($datetime, $format);
}

/**
 * Truncate a string to a maximum length.
 *
 * @param  string $text
 * @param  int    $length
 * @param  string $suffix
 * @return string
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

// ─── Order Number ───────────────────────────────────────────

/**
 * Generate a unique order number.
 * Format: BI-YYYYMMDD-XXXXXX
 *
 * @return string
 */
function generateOrderNumber(): string
{
    $date   = date('Ymd');
    $random = strtoupper(bin2hex(random_bytes(3)));
    return "BI-{$date}-{$random}";
}

// ─── Email (Stub) ───────────────────────────────────────────

/**
 * Send an email (stub — replace with actual mailer in production).
 *
 * @param  string $to
 * @param  string $subject
 * @param  string $body
 * @param  array  $headers  Additional headers
 * @return bool
 */
function sendEmail(string $to, string $subject, string $body, array $headers = []): bool
{
    // Log the email attempt
    $logDir = defined('LOG_PATH') ? LOG_PATH : dirname(__DIR__) . '/logs/';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logEntry  = "[{$timestamp}] To: {$to} | Subject: {$subject}\n";
    @file_put_contents($logDir . 'email.log', $logEntry, FILE_APPEND | LOCK_EX);

    // Default headers
    $defaultHeaders = [
        'From: ' . (defined('APP_EMAIL') ? APP_EMAIL : 'noreply@bareeda-injera.com'),
        'Reply-To: ' . (defined('APP_EMAIL') ? APP_EMAIL : 'noreply@bareeda-injera.com'),
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
    ];

    $allHeaders = array_merge($defaultHeaders, $headers);
    $headerString = implode("\r\n", $allHeaders);

    // Attempt to send via PHP mail()
    // In production, consider using PHPMailer or SwiftMailer with SMTP
    $result = @mail($to, $subject, $body, $headerString);

    if (!$result) {
        error_log("[Email] Failed to send to {$to}: {$subject}");
    }

    return $result;
}

/**
 * Send order confirmation email.
 */
function sendOrderConfirmation(string $to, string $orderNumber, float $total): bool
{
    $appName = defined('APP_NAME') ? APP_NAME : 'Bareeda Injera';
    $subject = "Order Confirmation - {$orderNumber}";
    $body    = "
        <h2>Thank you for your order!</h2>
        <p>Your order <strong>{$orderNumber}</strong> has been received.</p>
        <p><strong>Total:</strong> " . formatPrice($total) . "</p>
        <p>We will notify you when your order is confirmed and on its way.</p>
        <p>— The {$appName} Team</p>
    ";
    return sendEmail($to, $subject, $body);
}

// ─── Auth Helpers ───────────────────────────────────────────

/**
 * Check if the current user is logged in.
 *
 * @return bool
 */
function isLoggedIn(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Check if the current user is an admin.
 *
 * @return bool
 */
function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

/**
 * Get the current user's ID.
 *
 * @return int|null
 */
function currentUserId(): ?int
{
    return isLoggedIn() ? (int) $_SESSION['user']['id'] : null;
}

/**
 * Get the current user's data.
 *
 * @return array|null
 */
function currentUser(): ?array
{
    return isLoggedIn() ? $_SESSION['user'] : null;
}

// ─── CSRF Helpers ───────────────────────────────────────────

/**
 * Generate a CSRF token and store in session.
 *
 * @return string
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token']) || is_csrf_token_expired()) {
        $_SESSION['csrf_token']      = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    return $_SESSION['csrf_token'];
}

/**
 * Return an HTML hidden input with the CSRF token.
 *
 * @return string
 */
function csrf_field(): string
{
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate a CSRF token from a request.
 *
 * @param  string|null $token
 * @return bool
 */
function validate_csrf(?string $token = null): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $token = $token ?? ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    if (is_csrf_token_expired()) {
        return false;
    }

    $valid = hash_equals($_SESSION['csrf_token'], $token);

    // Regenerate token after validation to prevent replay attacks
    if ($valid) {
        $_SESSION['csrf_token']      = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    return $valid;
}

/**
 * Require a valid CSRF token or abort with 403.
 */
function require_csrf(): void
{
    if (!validate_csrf()) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body>';
        echo '<h1>403 Forbidden</h1><p>Invalid or expired security token. Please go back and try again.</p>';
        echo '</body></html>';
        exit;
    }
}

/**
 * Check if the CSRF token has expired.
 */
function is_csrf_token_expired(): bool
{
    $lifetime = defined('CSRF_TOKEN_LIFETIME') ? CSRF_TOKEN_LIFETIME : 3600;
    return !isset($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time']) > $lifetime;
}

// ─── URL Helpers ────────────────────────────────────────────

/**
 * Generate a full URL from a path.
 *
 * @param  string $path
 * @return string
 */
function url(string $path = '/'): string
{
    $base = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
    return $base . '/' . ltrim($path, '/');
}

/**
 * Generate an asset URL.
 *
 * @param  string $path
 * @return string
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Get the current request URI without query string.
 *
 * @return string
 */
function current_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    return $path ?: '/';
}

/**
 * Check if the current path matches a given pattern.
 *
 * @param  string $pattern  e.g., '/admin/*'
 * @return bool
 */
function is_current_path(string $pattern): bool
{
    $current = current_path();
    $regex   = str_replace(['*', '/'], ['[^/]+', '\\/'], $pattern);
    return (bool) preg_match('/^' . $regex . '$/', $current);
}

// ─── Array / Data Helpers ───────────────────────────────────

/**
 * Get a value from an array with a default fallback.
 *
 * @param  array  $array
 * @param  string $key
 * @param  mixed  $default
 * @return mixed
 */
function array_get(array $array, string $key, $default = null)
{
    return $array[$key] ?? $default;
}

/**
 * Check if a request method matches.
 *
 * @param  string $method  GET | POST | PUT | DELETE
 * @return bool
 */
function is_method(string $method): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
}

/**
 * Get JSON input from a request body.
 *
 * @return array
 */
function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Send a JSON response.
 *
 * @param  mixed $data
 * @param  int   $statusCode
 */
function json_response($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ─── Debug Helpers ──────────────────────────────────────────

/**
 * Dump a variable and die (development only).
 */
function dd($var): void
{
    if (getenv('APP_ENV') !== 'production') {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
        exit;
    }
    exit;
}

/**
 * Log a message to the application log file.
 *
 * @param  string $message
 * @param  string $level  info | warning | error
 */
function app_log(string $message, string $level = 'info'): void
{
    $logDir = defined('LOG_PATH') ? LOG_PATH : dirname(__DIR__) . '/logs/';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $line      = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    @file_put_contents($logDir . 'app.log', $line, FILE_APPEND | LOCK_EX);
}

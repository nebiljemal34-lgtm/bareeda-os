<?php
/**
 * Bareeda Injera - Helper Functions
 *
 * Global utility functions used throughout the application.
 */

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
    $symbol    = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : 'ETB';
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

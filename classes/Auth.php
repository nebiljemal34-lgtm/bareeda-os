<?php
/**
 * Bareeda Injera - Authentication Class
 *
 * Handles user registration, login/logout, session management,
 * CSRF protection, and role-based access control.
 */

require_once __DIR__ . '/../config/database.php';

class Auth
{
    /** @var Database */
    private $db;

    /** @var array|null Cached user data */
    private $currentUser = null;

    public function __construct()
    {
        $this->db = Database::getInstance();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // ─── Registration ───────────────────────────────────────

    /**
     * Register a new customer account.
     *
     * @param  string $email
     * @param  string $password
     * @param  string $firstName
     * @param  string $lastName
     * @param  string $phone
     * @return array  ['success' => bool, 'message' => string, 'user_id' => int|null]
     */
    public function register(string $email, string $password, string $firstName, string $lastName, string $phone = ''): array
    {
        // Validate email
        $email = trim(strtolower($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.', 'user_id' => null];
        }

        // Validate password
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.', 'user_id' => null];
        }

        // Check if email already exists
        $existing = $this->db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            return ['success' => false, 'message' => 'An account with this email already exists.', 'user_id' => null];
        }

        // Validate names
        $firstName = trim($firstName);
        $lastName  = trim($lastName);
        if ($firstName === '' || $lastName === '') {
            return ['success' => false, 'message' => 'First name and last name are required.', 'user_id' => null];
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Insert user
        $userId = $this->db->insert('users', [
            'email'         => $email,
            'password_hash' => $passwordHash,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'phone'         => $phone,
            'role'          => 'customer',
        ]);

        return [
            'success' => true,
            'message' => 'Registration successful. You can now log in.',
            'user_id' => (int) $userId,
        ];
    }

    // ─── Login ──────────────────────────────────────────────

    /**
     * Authenticate a user with email and password.
     *
     * @param  string $email
     * @param  string $password
     * @return array  ['success' => bool, 'message' => string]
     */
    public function login(string $email, string $password): array
    {
        $email = trim(strtolower($email));

        // Check login attempts
        if ($this->isLockedOut($email)) {
            return ['success' => false, 'message' => 'Too many failed attempts. Please try again later.'];
        }

        // Fetch user
        $user = $this->db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        // Clear failed attempts on successful login
        $this->clearFailedAttempts($email);

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        // Store user data in session (exclude password hash)
        unset($user['password_hash']);
        $_SESSION['user']      = $user;
        $_SESSION['login_time'] = time();
        $_SESSION['ip']         = $_SERVER['REMOTE_ADDR'] ?? '';

        $this->currentUser = $user;

        return ['success' => true, 'message' => 'Login successful.'];
    }

    // ─── Logout ─────────────────────────────────────────────

    /**
     * Destroy the session and log the user out.
     */
    public function logout(): void
    {
        $this->currentUser = null;
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    // ─── Session Checks ─────────────────────────────────────

    /**
     * Check if a user is currently logged in.
     */
    public function check(): bool
    {
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            // Verify session hasn't expired
            if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
                $this->logout();
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return array|null
     */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        if ($this->currentUser === null) {
            // Re-fetch from DB to get latest data
            $user = $this->db->fetchOne(
                "SELECT id, email, first_name, last_name, phone, address, city, role, created_at, updated_at FROM users WHERE id = ?",
                [$_SESSION['user']['id']]
            );
            if ($user) {
                $_SESSION['user'] = $user;
                $this->currentUser = $user;
            }
        }

        return $this->currentUser;
    }

    /**
     * Check if the current user is an admin.
     */
    public function isAdmin(): bool
    {
        $user = $this->user();
        return $user !== null && $user['role'] === 'admin';
    }

    /**
     * Require authentication — redirect to login if not authenticated.
     */
    public function requireLogin(string $redirectUrl = '/login'): void
    {
        if (!$this->check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * Require admin role — redirect if not admin.
     */
    public function requireAdmin(string $redirectUrl = '/'): void
    {
        $this->requireLogin($redirectUrl);
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo '403 Forbidden – Admin access required.';
            exit;
        }
    }

    // ─── CSRF Protection ────────────────────────────────────

    /**
     * Generate a new CSRF token and store it in the session.
     *
     * @return string The token
     */
    public function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token']      = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }

    /**
     * Get the current CSRF token (generate one if none exists).
     *
     * @return string
     */
    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token']) || $this->isCsrfTokenExpired()) {
            return $this->generateCsrfToken();
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate a submitted CSRF token against the session.
     *
     * @param  string $token
     * @return bool
     */
    public function validateCsrfToken(string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        if ($this->isCsrfTokenExpired()) {
            return false;
        }

        // Use hash_equals to prevent timing attacks
        $valid = hash_equals($_SESSION['csrf_token'], $token);

        // Regenerate token after validation to prevent replay attacks
        if ($valid) {
            $this->generateCsrfToken();
        }

        return $valid;
    }

    /**
     * Validate CSRF from the current POST request.
     *
     * @return bool
     */
    public function validateCsrfFromRequest(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return $this->validateCsrfToken($token);
    }

    private function isCsrfTokenExpired(): bool
    {
        if (!isset($_SESSION['csrf_token_time'])) {
            return true;
        }
        return (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME;
    }

    // ─── Password Reset (utility) ───────────────────────────

    /**
     * Update a user's password.
     *
     * @param  int    $userId
     * @param  string $newPassword
     * @return bool
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            return false;
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $affected = $this->db->update('users', ['password_hash' => $hash], 'id = ?', [$userId]);
        return $affected > 0;
    }

    /**
     * Verify a password against a stored hash.
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if a hash needs rehashing (e.g., after cost factor change).
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    // ─── Login Attempt Tracking ─────────────────────────────

    private function getAttemptKey(string $email): string
    {
        return 'login_attempts_' . md5($email);
    }

    private function isLockedOut(string $email): bool
    {
        $key = $this->getAttemptKey($email);
        if (!isset($_SESSION[$key])) {
            return false;
        }
        $attempts = $_SESSION[$key];
        if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
            $elapsed = time() - $attempts['last_attempt'];
            if ($elapsed < (LOGIN_LOCKOUT_MINUTES * 60)) {
                return true;
            }
            // Lockout expired — clear
            unset($_SESSION[$key]);
        }
        return false;
    }

    private function recordFailedAttempt(string $email): void
    {
        $key = $this->getAttemptKey($email);
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
        }
        $_SESSION[$key]['count']++;
        $_SESSION[$key]['last_attempt'] = time();
    }

    private function clearFailedAttempts(string $email): void
    {
        $key = $this->getAttemptKey($email);
        unset($_SESSION[$key]);
    }
}

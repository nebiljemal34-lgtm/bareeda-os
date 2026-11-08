<?php
/**
 * Bareeda Injera - Auth Controller
 *
 * Handles login, registration, and logout.
 */

require_once __DIR__ . '/BaseController.php';

class AuthController extends BaseController
{
    /**
     * GET /login — Show login form.
     */
    public function loginForm(): void
    {
        $cart = $GLOBALS['cart'];
        $this->render('auth/login', [
            'page_title' => 'Login',
            'cart_count' => $cart->getItemCount(),
        ]);
    }

    /**
     * POST /login — Process login.
     */
    public function login(): void
    {
        $auth     = $GLOBALS['auth'];
        $email    = $this->input('email', '');
        $password = $this->rawInput('password', '');

        if (empty($email) || empty($password)) {
            flash('error', 'Please enter your email and password.');
            redirect('/login');
            return;
        }

        $result = $auth->login($email, $password);

        if ($result['success']) {
            flash('success', 'Welcome back!');

            // Merge guest cart into user cart
            if (isset($_SESSION['guest_session_id'])) {
                $cart = $GLOBALS['cart'];
                $cart->mergeGuestCart($_SESSION['guest_session_id'], (int) $_SESSION['user']['id']);
                unset($_SESSION['guest_session_id']);
            }

            $redirectUrl = $_SESSION['redirect_after_login'] ?? '/account';
            unset($_SESSION['redirect_after_login']);
            redirect($redirectUrl);
        } else {
            flash('error', $result['message']);
            redirect('/login');
        }
    }

    /**
     * GET /register — Show registration form.
     */
    public function registerForm(): void
    {
        $cart = $GLOBALS['cart'];
        $this->render('auth/register', [
            'page_title' => 'Create Account',
            'cart_count' => $cart->getItemCount(),
        ]);
    }

    /**
     * POST /register — Process registration.
     */
    public function register(): void
    {
        $auth = $GLOBALS['auth'];

        $firstName       = $this->input('first_name', '');
        $lastName        = $this->input('last_name', '');
        $email           = $this->input('email', '');
        $phone           = $this->input('phone', '');
        $password        = $this->rawInput('password', '');
        $passwordConfirm = $this->rawInput('password_confirm', '');

        // Validation
        if (empty($firstName) || empty($lastName) || empty($email)) {
            flash('error', 'All fields are required.');
            redirect('/register');
            return;
        }

        if (!is_valid_email($email)) {
            flash('error', 'Please enter a valid email address.');
            redirect('/register');
            return;
        }

        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            flash('error', 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.');
            redirect('/register');
            return;
        }

        if ($password !== $passwordConfirm) {
            flash('error', 'Passwords do not match.');
            redirect('/register');
            return;
        }

        $result = $auth->register($email, $password, $firstName, $lastName, $phone);

        if ($result['success']) {
            TelegramNotifier::notify('new_registration', [
                'name'  => trim($firstName . ' ' . $lastName),
                'email' => $email,
                'phone' => $phone,
            ]);
            flash('success', 'Account created! Please log in.');
            redirect('/login');
        } else {
            flash('error', $result['message']);
            redirect('/register');
        }
    }

    /**
     * GET /logout — Log out the user.
     */
    public function logout(): void
    {
        $auth = $GLOBALS['auth'];
        $auth->logout();
        flash('success', 'You have been logged out.');
        redirect('/');
    }

    /**
     * GET /forgot-password — Show forgot password form.
     */
    public function forgotPasswordForm(): void
    {
        $cart = $GLOBALS['cart'];
        $this->render('auth/login', [
            'page_title'    => 'Forgot Password',
            'cart_count'    => $cart->getItemCount(),
            'show_forgot'   => true,
        ]);
    }

    /**
     * POST /forgot-password — Handle forgot password.
     */
    public function forgotPassword(): void
    {
        $email = $this->input('email', '');

        if (empty($email) || !is_valid_email($email)) {
            flash('error', 'Please enter a valid email address.');
            redirect('/forgot-password');
            return;
        }

        // Always show success to prevent email enumeration
        flash('success', 'If an account exists with that email, a password reset link has been sent.');
        redirect('/login');
    }
}

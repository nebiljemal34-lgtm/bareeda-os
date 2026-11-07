<?php
/**
 * Bareeda Injera - Base Controller
 *
 * Provides shared rendering logic for all controllers.
 */

class BaseController
{
    /**
     * Render a view inside the master layout.
     *
     * @param string $view  Path relative to views/ (e.g. 'home/index')
     * @param array  $data  Variables to extract into the view scope
     */
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = BASE_PATH . 'views/' . $view . '.php';
        require BASE_PATH . 'views/layout.php';
    }

    /**
     * Return JSON response.
     */
    protected function json($data, int $code = 200): void
    {
        json_response($data, $code);
    }

    /**
     * Get POST input, sanitized.
     */
    protected function input(string $key, $default = null)
    {
        return isset($_POST[$key]) ? sanitize($_POST[$key]) : $default;
    }

    /**
     * Get raw POST input.
     */
    protected function rawInput(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Require the user to be authenticated.
     */
    protected function requireAuth(): void
    {
        if (!isLoggedIn()) {
            flash('error', 'Please log in to continue.');
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            redirect('/login');
        }
    }

    /**
     * Require admin role.
     */
    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!isAdmin()) {
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }
    }
}

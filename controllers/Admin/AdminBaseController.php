<?php
/**
 * Bareeda Injera - Admin Base Controller
 *
 * Shared utilities for all admin controllers.
 */

class AdminBaseController
{
    protected $db;
    protected $auth;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }

    /**
     * Render an admin view within the admin layout.
     *
     * @param string $view  Dot-notation path, e.g. 'admin/dashboard/index'
     * @param array  $data  Variables to extract into the view scope
     */
    protected function render(string $view, array $data = []): void
    {
        $pageTitle  = $data['pageTitle'] ?? 'Admin';
        $contentFile = BASE_PATH . 'views/' . str_replace('.', '/', $view) . '.php';
        extract($data);

        require BASE_PATH . 'views/admin/layout.php';
    }

    /**
     * Get a required POST parameter or return null.
     */
    protected function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get a query string parameter or return null.
     */
    protected function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get integer from query string.
     */
    protected function getInt(string $key, int $default = 0): int
    {
        return isset($_GET[$key]) ? (int) $_GET[$key] : $default;
    }
}

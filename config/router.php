<?php
/**
 * Bareeda Injera - Simple Router
 *
 * Maps URLs to controller actions. Supports GET and POST methods,
 * route parameters, middleware (auth/admin checks), and 404 handling.
 *
 * Usage:
 *   $router = new Router();
 *   $router->get('/', 'HomeController@index');
 *   $router->post('/cart/add', 'CartController@add');
 *   $router->get('/admin/dashboard', 'AdminController@dashboard', ['auth', 'admin']);
 *   $router->dispatch();
 */

class Router
{
    /** @var array Registered routes */
    private $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    /** @var string Base path for controller files */
    private $controllerPath;

    /** @var string Namespace prefix for controllers (if using) */
    private $controllerNamespace = '';

    public function __construct(string $controllerPath = '')
    {
        $this->controllerPath = $controllerPath ?: BASE_PATH . 'controllers/';
    }

    // ─── Route Registration ─────────────────────────────────

    /**
     * Register a GET route.
     *
     * @param string $path        URL pattern (e.g., '/products/{id}')
     * @param string $action      Controller@method
     * @param array  $middleware   ['auth', 'admin', 'guest']
     */
    public function get(string $path, string $action, array $middleware = []): self
    {
        $this->routes['GET'][] = [
            'path'       => $path,
            'action'     => $action,
            'middleware'  => $middleware,
        ];
        return $this;
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, string $action, array $middleware = []): self
    {
        $this->routes['POST'][] = [
            'path'       => $path,
            'action'     => $action,
            'middleware'  => $middleware,
        ];
        return $this;
    }

    /**
     * Register a route for both GET and POST.
     */
    public function any(string $path, string $action, array $middleware = []): self
    {
        $this->get($path, $action, $middleware);
        $this->post($path, $action, $middleware);
        return $this;
    }

    /**
     * Register routes with a common prefix.
     *
     * @param string   $prefix
     * @param callable $callback  Receives a RouteGroup instance
     */
    public function group(string $prefix, callable $callback): self
    {
        $group = new RouteGroup($this, $prefix);
        $callback($group);
        return $this;
    }

    // ─── Dispatch ───────────────────────────────────────────

    /**
     * Match the current request to a route and execute the controller action.
     */
    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri    = $this->parseUri();

        // Handle POST method override (_method field for PUT/DELETE)
        if ($method === 'POST' && !empty($_POST['_method'])) {
            $override = strtoupper($_POST['_method']);
            if (in_array($override, ['PUT', 'DELETE', 'PATCH'])) {
                $method = $override;
            }
        }

        // Find matching route
        $match = $this->findRoute($method, $uri);

        if ($match === null) {
            $this->handle404($uri);
            return;
        }

        [$route, $params] = $match;

        // Run middleware
        if (!$this->runMiddleware($route['middleware'])) {
            return; // Middleware blocked the request
        }

        // Execute controller action
        $this->executeAction($route['action'], $params);
    }

    // ─── Route Matching ─────────────────────────────────────

    /**
     * Find a matching route for the given method and URI.
     *
     * @param  string $method
     * @param  string $uri
     * @return array|null  [route, params] or null
     */
    private function findRoute(string $method, string $uri): ?array
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $route) {
            $params = $this->matchRoute($route['path'], $uri);
            if ($params !== false) {
                return [$route, $params];
            }
        }

        return null;
    }

    /**
     * Match a route pattern against a URI.
     *
     * @param  string $pattern  e.g., '/products/{id}'
     * @param  string $uri      e.g., '/products/42'
     * @return array|false      Extracted params or false if no match
     */
    private function matchRoute(string $pattern, string $uri)
    {
        // Exact match
        if ($pattern === $uri) {
            return [];
        }

        // Convert pattern to regex
        $regex = preg_replace_callback('/\{(\w+)(?::([^}]+))?\}/', function ($matches) {
            $name    = $matches[1];
            $pattern = $matches[2] ?? '[^/]+';
            return '(?P<' . $name . '>' . $pattern . ')';
        }, $pattern);

        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Extract only named groups
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return false;
    }

    // ─── Middleware ──────────────────────────────────────────

    /**
     * Run middleware checks.
     *
     * @param  array $middleware
     * @return bool  True if all pass, false if blocked
     */
    private function runMiddleware(array $middleware): bool
    {
        foreach ($middleware as $mw) {
            switch ($mw) {
                case 'auth':
                    if (!isLoggedIn()) {
                        flash('error', 'Please log in to continue.');
                        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
                        redirect('/login');
                        return false;
                    }
                    break;

                case 'admin':
                    if (!isAdmin()) {
                        http_response_code(403);
                        $this->renderErrorPage(403, 'Forbidden', 'You do not have permission to access this page.');
                        return false;
                    }
                    break;

                case 'guest':
                    if (isLoggedIn()) {
                        redirect('/');
                        return false;
                    }
                    break;

                case 'csrf':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf()) {
                        http_response_code(403);
                        $this->renderErrorPage(403, 'Invalid Request', 'Security token validation failed. Please try again.');
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    // ─── Controller Execution ───────────────────────────────

    /**
     * Execute a controller action.
     *
     * @param string $action  e.g., 'ProductController@show' or 'Admin\DashboardController@index'
     * @param array  $params  Route parameters
     */
    private function executeAction(string $action, array $params): void
    {
        [$controllerName, $methodName] = explode('@', $action);

        // Convert namespace separators to directory separators for file path
        $controllerPath = str_replace('\\', '/', $controllerName);
        $controllerFile = $this->controllerPath . $controllerPath . '.php';

        if (!file_exists($controllerFile)) {
            $this->handle404($this->parseUri(), "Controller file not found: {$controllerFile}");
            return;
        }

        require_once $controllerFile;

        // Determine the actual PHP class name to instantiate.
        // If the file defines the class inside a namespace, use the FQCN;
        // otherwise fall back to the short class name (last segment after \).
        $className = $controllerName;
        if (!class_exists($className)) {
            // Extract short name (e.g., "Admin\DashboardController" → "DashboardController")
            $parts     = explode('\\', $controllerName);
            $shortName = end($parts);
            if (class_exists($shortName)) {
                $className = $shortName;
            } else {
                $this->handle404($this->parseUri(), "Controller class not found: {$controllerName}");
                return;
            }
        }

        $controller = new $className();

        if (!method_exists($controller, $methodName)) {
            $this->handle404($this->parseUri(), "Method not found: {$controllerName}@{$methodName}");
            return;
        }

        // Call the method with route params
        call_user_func_array([$controller, $methodName], $params);
    }

    // ─── URI Parsing ────────────────────────────────────────

    /**
     * Parse the current request URI into a clean path.
     *
     * @return string
     */
    private function parseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $path = $path ?: '/';

        // Remove trailing slash (except for root)
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        // Remove base path if installed in subdirectory
        $basePath = parse_url(defined('APP_URL') ? APP_URL : '', PHP_URL_PATH);
        if ($basePath && $basePath !== '/' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        return $path ?: '/';
    }

    // ─── Error Handling ─────────────────────────────────────

    /**
     * Handle a 404 Not Found.
     */
    private function handle404(string $uri, string $detail = ''): void
    {
        http_response_code(404);

        // Check if there's a custom 404 controller
        $match = $this->findRoute('GET', '/404');
        if ($match) {
            [$route, $params] = $match;
            $this->executeAction($route['action'], $params);
            return;
        }

        // Default 404 page
        $this->renderErrorPage(404, 'Page Not Found', "The page <strong>{$uri}</strong> could not be found.");
    }

    /**
     * Render a simple error page.
     */
    private function renderErrorPage(int $code, string $title, string $message): void
    {
        $safeTitle   = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = $message;
        $appName     = defined('APP_NAME') ? htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') : 'Bareeda Injera';

        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$code} - {$safeTitle} | {$appName}</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f8f9fa; color: #333; }
                .error-container { text-align: center; padding: 2rem; }
                .error-code { font-size: 6rem; font-weight: 700; color: #c0392b; margin: 0; }
                .error-title { font-size: 1.5rem; margin: 0.5rem 0; }
                .error-message { color: #666; margin: 1rem 0; }
                .error-link { display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: #27ae60; color: #fff; text-decoration: none; border-radius: 4px; }
                .error-link:hover { background: #219a52; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <p class="error-code">{$code}</p>
                <h1 class="error-title">{$safeTitle}</h1>
                <p class="error-message">{$safeMessage}</p>
                <a href="/" class="error-link">Go to Homepage</a>
            </div>
        </body>
        </html>
        HTML;
        exit;
    }

    // ─── Utility ────────────────────────────────────────────

    /**
     * Get all registered routes (for debugging).
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}

/**
 * Route Group — for prefixing routes.
 */
class RouteGroup
{
    /** @var Router */
    private $router;

    /** @var string */
    private $prefix;

    public function __construct(Router $router, string $prefix)
    {
        $this->router = $router;
        $this->prefix = rtrim($prefix, '/');
    }

    public function get(string $path, string $action, array $middleware = []): Router
    {
        return $this->router->get($this->prefix . $path, $action, $middleware);
    }

    public function post(string $path, string $action, array $middleware = []): Router
    {
        return $this->router->post($this->prefix . $path, $action, $middleware);
    }

    public function any(string $path, string $action, array $middleware = []): Router
    {
        return $this->router->any($this->prefix . $path, $action, $middleware);
    }
}

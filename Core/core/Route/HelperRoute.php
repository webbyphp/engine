<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * HelperRoute - A supporting routing class for WebbyPHP Route
 * 
 * This class enhances routing 
 * entirely and handles requests directly
 * 
 * Built for serving simple views and JSON responses
 * 
 */

namespace Base\Route;

use ArgumentCountError;
use Closure;
use ReflectionFunction;

class HelperRoute
{

    protected static $routes = [];
    protected static $currentUri = '';

    /**
     * Initialize and check if we 
     * should handle this request
     */
    public static function init()
    {
        // Get the current URI
        self::$currentUri = self::getCurrentUri();

        // Check if any of our routes match
        foreach (self::$routes as $pattern => $handler) {
            $params = self::matchRoute($pattern, self::$currentUri);

            if ($params !== false) {
                // We have a match! Execute the handler
                self::executeHandler($handler, $params);
                exit; // Stop execution
            }
        }
    }

    /**
     * Register a view route
     * 
     * @param string $uri The URI pattern (e.g., 'about', 'profile/(:num)')
     * @param string $view The view file path
     * @param array $data Data to pass to the view
     */
    public static function view($uri, $view, $data = [])
    {
        self::$routes[$uri] = [
            'type' => 'view',
            'view' => $view,
            'data' => $data
        ];
    }

    /**
     * Handle a view or json to be used in a custom route
     * 
     * @param mixed $view
     * @param mixed $data
     * @return void
     */
    public static function with($view = '', $data = [], $status = 200, $json = false)
    {

        if ($json === true) {

            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');

            // Output JSON
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        HelperRoute::handleView($view, $data);
    }

    /**
     * Register a JSON route
     * 
     * @param string $uri The URI pattern
     * @param mixed $data Data to return (array or Closure)
     * @param int $status HTTP status code
     */
    public static function json($uri, $data = [], $status = 200)
    {
        self::$routes[$uri] = [
            'type' => 'json',
            'data' => $data,
            'status' => $status
        ];
    }

    /**
     * Register a custom closure route
     * 
     * @param string $uri The URI pattern
     * @param Closure $callback The callback to execute
     */
    public static function closure($uri, ?Closure $callback = null)
    {
        self::$routes[$uri] = [
            'type' => 'closure',
            'callback' => $callback
        ];
    }

    /**
     * Get the current URI from the request
     * 
     * @return string
     */
    protected static function getCurrentUri()
    {
        // Get REQUEST_URI and clean it
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Remove base path if CI is in a subdirectory
        $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
        $base_path = dirname($script_name);

        if ($base_path !== '/' && strpos($uri, $base_path) === 0) {
            $uri = substr($uri, strlen($base_path));
        }

        // Remove index.php if present
        $uri = str_replace('/index.php', '', $uri);

        // Clean up
        $uri = trim($uri, '/');

        return $uri;
    }

    /**
     * Match a route pattern against the current URI
     * 
     * @param string $pattern The route pattern
     * @param string $uri The current URI
     * @return array|false Array of parameters or false if no match
     */
    protected static function matchRoute($pattern, $uri)
    {
        // Trim slashes for comparison
        $pattern = trim($pattern, '/');
        $uri = trim($uri, '/');

        // Exact match first (fastest check)
        if ($pattern === $uri) {
            return [];
        }

        // Check if pattern is already a regex (starts with # or /)
        if (preg_match('/^[#\/]/', $pattern)) {
            // Already a regex pattern, use directly
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match
                return $matches;
            }
            return false;
        }

        // Convert Laravel-style {placeholders} to regex
        $pattern = preg_replace_callback('/\{(\w+)\}/', function ($matches) {
            $placeholder = $matches[1];

            // Map placeholder names to regex patterns
            switch (strtolower($placeholder)) {
                case 'id':
                case 'num':
                    return '([0-9]+)';

                case 'uuid':
                    // UUID format: 8-4-4-4-12 hex characters
                    return '([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})';

                case 'alpha':
                    return '([a-zA-Z]+)';

                case 'alphanum':
                    return '([a-zA-Z0-9]+)';

                case 'slug':
                    // Slug: lowercase alphanumeric with hyphens
                    return '([a-z0-9-]+)';

                case 'any':
                default:
                    // Default to matching anything except /
                    return '([^/]+)';
            }
        }, $pattern);

        // Convert CI-style (:wildcards) to regex
        $replacements = [
            '(:any)'      => '([^/]+)',
            '(:num)'      => '([0-9]+)',
            '(:alpha)'    => '([a-zA-Z]+)',
            '(:alphanum)' => '([a-zA-Z0-9]+)',
        ];

        $pattern = str_replace(array_keys($replacements), array_values($replacements), $pattern);

        // Add regex delimiters and anchors
        $pattern = '#^' . $pattern . '$#';

        // Try to match
        if (preg_match($pattern, $uri, $matches)) {
            // Remove the full match, keep only captured groups
            array_shift($matches);
            return $matches;
        }

        return false;
    }

    /**
     * Execute a route handler
     * 
     * @param array $handler The handler configuration
     * @param array $params Route parameters
     */
    protected static function executeHandler($handler, $params = [])
    {
        switch ($handler['type']) {
            case 'view':
                self::handleView($handler['view'], $handler['data']);
                break;

            case 'json':
                self::handleJson($handler['data'], $handler['status'], $params);
                break;

            case 'closure':
                self::handleClosure($handler['callback'], $params);
                break;
        }
    }

    /**
     * Handle a view route
     * 
     * @param string $view View file path
     * @param array $data Data for the view
     */
    protected static function handleView($view, $data = [])
    {
        // Build the full path to the view
        $viewpath = VIEWPATH . $view . '.php';

        if (!file_exists($viewpath)) {
            self::show404("View file not found: {$view}");
            return;
        }

        // Extract data to make 
        // variables available in view
        extract($data);

        // Output the view
        require $viewpath;
    }

    /**
     * Handle a JSON route
     * 
     * @param mixed $data Data to output (array or Closure)
     * @param int $status HTTP status code
     * @param array $params Route parameters
     */
    protected static function handleJson($data, $status, $params)
    {
        // If data is a closure, execute it with parameters
        if ($data instanceof Closure) {
            $data = call_user_func_array($data, $params);
        }

        // Set headers
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        // Output JSON
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Handle a closure route
     * 
     * @param Closure $callback The callback
     * @param array $params Route parameters
     */
    protected static function handleClosure($callback, $params = [])
    {
        if (! $callback instanceof Closure) {
            return call_user_func_array($callback, $params);
        }

        $reference = new ReflectionFunction($callback);
        $provided = count($params);
        $required = $reference->getNumberOfRequiredParameters();

        if ($required > $provided) {
            throw new ArgumentCountError(
                "Route::page(...) or Route::json(...) closure expects at least $required parameter(s), " .
                    "but only $provided value(s) were passed from the URL. " .
                    "Add more segments like {id} or (:num) to your route."
            );
        }

        return call_user_func_array($callback, $params);
    }

    /**
     * Show a 404 page
     * 
     * @param string $message Optional message
     */
    protected static function show404($message = 'Page Not Found')
    {
        http_response_code(404);
        header('Location: ' . '404_Overwride');
    }

    /**
     * Get all registered routes in 
     * CodeIgniter 3 native route format (for debugging)
     * Returns clean, unique, readable routes like:
     * 
     * [pages]              => prefix-route/portfolio
     * [api/user/(:num)]    => prefix-route/api/user/$1
     * [profile/(:any)]     => prefix-route/profile/$1
     * [custom]             => prefix-route/custom
     * 
     * @return array
     */
    public static function getRoutes()
    {
        $formatRoutes = [];
        $prefix = 'prefix-route';
        foreach (self::$routes as $uri => $handler) {
            $uri = $uri === '' ? '/' : $uri; // root fix

            switch ($handler['type']) {
                case 'view':
                    // For views: show the actual view name
                    $target = $prefix . '/' . $handler['view'];
                    break;

                case 'json':
                case 'closure':
                    // For json/closure: reflect the URI pattern with $1, $2, etc.
                    $target = $prefix .  '/' . $uri;

                    // Replace CI placeholders with $1, $2, etc. (like real CI routes)
                    $placeholders = [
                        '(:any)'     => '$1',
                        '(:num)'     => '$1',
                        '(:alpha)'   => '$1',
                        '(:alphanum)' => '$1',
                    ];

                    // Find all placeholders in order
                    preg_match_all('#\((:[^)]+)\)#', $uri, $matches);
                    $params = $matches[0]; // e.g. ['(:num)', '(:any)']

                    if (!empty($params)) {
                        $target = $prefix .  '/' . preg_replace(
                            array_keys($placeholders),
                            array_values($placeholders),
                            $uri
                        );

                        // Replace multiple placeholders: $1 → $2 → $3 etc.
                        for ($i = 0; $i < count($params); $i++) {
                            $target = str_replace('$1', '$' . ($i + 1), $target);
                        }
                    }
                    break;

                default:
                    $target = $prefix .  '/unknown';
            }

            // Final cleanup: ensure no duplicate $1 after replacement
            $formatRoutes[$uri] = $target;
        }

        return $formatRoutes;
    }
}

<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Base\Middleware;

/**
 * Middleware Runner
 * Handles middleware execution and registration
 */
class MiddlewareRunner
{
    private $namespace = "App\\Middleware\\";
    /**
     * Registered global middlewares
     *
     * @var array
     */
    protected static $globalMiddlewares = [];

    /**
     * Middleware aliases
     *
     * @var array
     */
    protected static $middlewareAliases = [
        'auth' => 'AuthMiddleware',
        'guest' => 'GuestMiddleware',
        'admin' => 'AdminMiddleware',
        'ratelimit' => 'RateLimitMiddleware',
        'cors' => 'CorsMiddleware',
    ];

    /**
     * Current controller instance
     *
     * @var object
     */
    protected $controller;

    /**
     * CodeIgniter instance
     *
     * @var object
     */
    protected $ci;

    /**
     * Executed middlewares
     *
     * @var array
     */
    protected $executedMiddlewares = [];

    /**
     * Constructor
     *
     * @param object $controller
     */
    public function __construct($controller = null)
    {
        $this->controller = $controller;
        $this->ci = get_instance();
    }

    /**
     * Register a middleware alias
     *
     * @param string $alias
     * @param string $className
     * @return void
     */
    public static function registerAlias($alias, $className)
    {
        static::$middlewareAliases[$alias] = $className;
    }

    /**
     * Register global middleware
     *
     * @param string|array $middleware
     * @return void
     */
    public static function registerGlobal($middleware)
    {
        if (is_array($middleware)) {
            static::$globalMiddlewares = array_merge(static::$globalMiddlewares, $middleware);
        } else {
            static::$globalMiddlewares[] = $middleware;
        }
    }

    /**
     * Run middlewares
     *
     * @param array $middlewares
     * @param string $method Current controller method
     * @return void
     */
    public function run($middlewares = [], $method = null)
    {

        // Merge global middlewares with controller-specific ones
        $allMiddlewares = array_merge(static::$globalMiddlewares, $middlewares);

        foreach ($allMiddlewares as $middleware) {
            $this->executeMiddleware($middleware, $method);
        }
    }

    /**
     * Execute a single middleware
     *
     * @param string $middleware
     * @param string $method
     * @return void
     */
    protected function executeMiddleware($middleware, $method = null)
    {
        // Parse middleware string (e.g., "auth|except:login,register")
        $parsed = $this->parseMiddleware($middleware);

        $middlewareName = $parsed['name'];
        $options = $parsed['options'];

        // Check if middleware should run for this method
        if (!$this->shouldRunMiddleware($options, $method)) {
            return;
        }

        // Resolve middleware class name
        $className = $this->resolveMiddlewareName($middlewareName);

        // The duplicated "Middleware".
        $className = preg_replace('/(Middleware)+/', '$1', $className);

        // Load and execute middleware
        $middlewareInstance = $this->loadMiddleware($className);

        if ($middlewareInstance) {
            // Execute middleware handle method
            $middlewareInstance->handle();

            // Execute always method if exists
            if (method_exists($middlewareInstance, 'always')) {
                $middlewareInstance->always();
            }

            $this->executedMiddlewares[] = $middlewareName;
        }
    }

    /**
     * Parse middleware string
     *
     * @param string $middleware
     * @return array
     */
    protected function parseMiddleware($middleware)
    {
        $parts = explode('|', str_replace(' ', '', $middleware));
        $name = $parts[0];
        $options = [];

        if (isset($parts[1])) {
            $optionParts = explode(':', $parts[1]);
            $type = $optionParts[0]; // 'only' or 'except'
            $methods = isset($optionParts[1]) ? explode(',', $optionParts[1]) : [];

            $options = [
                'type' => $type,
                'methods' => $methods
            ];
        }

        return [
            'name' => $name,
            'options' => $options
        ];
    }

    /**
     * Check if middleware should run
     *
     * @param array $options
     * @param string $method
     * @return bool
     */
    protected function shouldRunMiddleware($options, $method)
    {
        if (empty($options) || !$method) {
            return true;
        }

        $type = $options['type'];
        $methods = $options['methods'];

        if ($type === 'except') {
            return !in_array($method, $methods);
        }

        if ($type === 'only') {
            return in_array($method, $methods);
        }

        return true;
    }

    /**
     * Resolve middleware class name
     *
     * @param string $name
     * @return string
     */
    protected function resolveMiddlewareName($name)
    {

        // Check if it's an alias
        if (isset(static::$middlewareAliases[$name])) {
            return static::$middlewareAliases[$name];
        }

        // If it's already a class name, return it
        if (class_exists($name)) {
            return $name;
        }

        $this->ci->load->helper('inflector');

        return ucfirst(camelize($name)) . 'Middleware';
    }

    /**
     * Load middleware instance
     *
     * @param string $className
     * @return object|null
     */
    protected function loadMiddleware($className)
    {
        // Check if middleware file exists
        $filename = APPROOT . 'Middleware/' . $className . '.php';

        if (!file_exists($filename)) {
            if (ENVIRONMENT == 'development') {
                show_error('Unable to load middleware: ' . $className . '.php');
            } else {
                log_message('error', 'Middleware not found: ' . $className);
                show_error('Sorry, something went wrong.');
            }
            return null;
        }

        $className = $this->namespace . $className; // Prepend the namespace

        require_once $filename;

        if (!class_exists($className)) {
            show_error('Middleware class not found: ' . $className);
            return null;
        }

        return new $className($this->controller, $this->ci);
    }

    /**
     * Get executed middlewares
     *
     * @return array
     */
    public function getExecutedMiddlewares()
    {
        return $this->executedMiddlewares;
    }
}

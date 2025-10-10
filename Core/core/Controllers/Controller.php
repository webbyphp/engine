<?php 

namespace Base\Controllers;

use Base\Middleware\MiddlewareRunner;

class Controller extends \Base_Controller 
{
	/**
     * Middleware runner instance
     *
     * @var MiddlewareRunner
     */
    protected $middlewareRunner;

    /**
     * Registered middlewares for this controller
     *
     * @var array
     */
    protected $middlewares = [];

	/**
     * Flag to track if middlewares have been run
     *
     * @var bool
     */
    private $middlewaresExecuted = false;


    public function __construct()
    {
        parent::__construct();

		// Initialize middleware runner
        $this->middlewareRunner = new MiddlewareRunner($this);

        $this->maintenanceMode();
    }

    /**
	 * Set app maintenance mode
	 *
	 * @return void
	 */
	private function maintenanceMode()
	{
        if (is_cli()) {
			$this->config->set_item('maintenance_mode', true);
		}

        // Check for file-based maintenance mode first (more reliable)
        if ($this->isFileBasedMaintenanceActive()) {
            $this->handleMaintenanceMode();
            return;
        }

		if (
			$this->config->item('maintenance_mode') === "false" 
			|| $this->config->item('app_status') === false
		) {
            $this->handleMaintenanceMode();
		} 
	}

	/**
	 * Check if file-based maintenance mode is active
	 *
	 * @return bool
	 */
	private function isFileBasedMaintenanceActive(): bool
	{
		$maintenanceFile = ROOTPATH . 'writable/maintenance/maintenance.lock';
		return file_exists($maintenanceFile);
	}

	/**
	 * Handle maintenance mode display and logic
	 *
	 * @return void
	 */
	private function handleMaintenanceMode(): void
	{

		// Check for maintenance bypass before showing maintenance page
		if ($this->canBypassMaintenance()) {
			log_message('app', 'Maintenance bypass granted for IP: ' . $this->getVisitIP());
			return; // Allow access
		}

        $maintenance_view = $this->config->item('maintenance_view');

		log_message('app', 'Accessing maintenance mode from this ip address: ' . $this->getVisitIP());
		
		if (is_cli()) {
            // exit('In Maintenance Mode')
			echo \Base\Console\ConsoleColor::yellow("In Maintenance Mode \n"); PHP_EOL ;
            exit; 
		}

        // if (is_cli() 
        //     && in_array('maintenance/on/', $_SERVER['argv'])
        //     || in_array('maintenance/off/', $_SERVER['argv']) ) {
        //     // exit('In Maintenance Mode');
			
		// } else {
        //     echo ConsoleColor::yellow("In Maintenance Mode \n"); PHP_EOL ;
        //     exit; 
        // }

		http_response_code(503); // Set response code
		header('Retry-After: 3600'); // Set retry time

		if (file_exists(APP_MAINTENANCE_PATH . $maintenance_view)) {
			include_once(APP_MAINTENANCE_PATH . $maintenance_view);
		} else {
			show_error('Please make sure the maintenance view exists and that you have added a file extension e.g(.html,.php) to maintenance view', 500);
		}

		exit;
	}

	/**
	 * Check if maintenance mode can be bypassed
	 *
	 * @return bool
	 */
	private function canBypassMaintenance(): bool
	{
		// Check for bypass secret key in URL parameter
		if ($this->checkBypassKey()) {
			return true;
		}

		// Check for bypass secret key in headers
		if ($this->checkBypassHeader()) {
			return true;
		}

		// Check IP allowlist
		if ($this->checkBypassIP()) {
			return true;
		}

		// Check if user has admin session/role (if authentication is available)
		if ($this->checkAdminBypass()) {
			return true;
		}

		// Check developer mode bypass
		if ($this->checkDeveloperBypass()) {
			return true;
		}

		return false;
	}

	/**
	 * Check bypass via secret key in URL parameter
	 *
	 * @return bool
	 */
	private function checkBypassKey(): bool
	{
		$bypassKey = getenv('app.maintenance.bypass.key');
		$providedKey = $_GET['bypass'] ?? null;

		if ($providedKey && hash_equals($bypassKey, $providedKey)) {
			// Try to set session to remember bypass for this session (optional)
			$this->setBypassSession();
			return true;
		}

		// Check if bypass was already granted in this session (optional)
		if ($this->checkBypassSession()) {
			return true;
		}

		return false;
	}

	/**
	 * Check bypass via secret key in request header
	 *
	 * @return bool
	 */
	private function checkBypassHeader(): bool
	{
		$bypassKey = getenv('app.maintenance.bypass.key') ?: 'webby_maintenance_bypass';
		$providedKey = $_SERVER['HTTP_X_MAINTENANCE_BYPASS'] ?? null;

		return $providedKey && hash_equals($bypassKey, $providedKey);
	}

	/**
	 * Check bypass via IP allowlist
	 *
	 * @return bool
	 */
	private function checkBypassIP(): bool
	{
		$allowedIPs = getenv('app.maintenance.bypass.ips') ?: '';

		if (empty($allowedIPs)) {
			return false;
		}

		$allowedIPsList = array_map('trim', explode(',', $allowedIPs));
		$currentIP = $this->getVisitIP();

		return in_array($currentIP, $allowedIPsList, true);
	}

	/**
	 * Check bypass for admin users
	 *
	 * @return bool
	 */
	private function checkAdminBypass(): bool
	{
		// Check if admin bypass is enabled
		$adminBypassEnabled = getenv('app.maintenance.bypass.admin') === 'true';
		
		if (!$adminBypassEnabled) {
			return false;
		}

		// Only check session if it's already started (avoid session conflicts)
		if (session_status() !== PHP_SESSION_ACTIVE) {
			return false;
		}

		// Check various common admin session indicators
		$adminChecks = [
			isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin',
			isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true,
			isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin',
			isset($_SESSION['user_type']) && strtolower($_SESSION['user_type']) === 'admin',
			isset($_SESSION['permission_level']) && $_SESSION['permission_level'] === 'admin'
		];

		return in_array(true, $adminChecks, true);
	}

	/**
	 * Check developer mode bypass
	 *
	 * @return bool
	 */
	private function checkDeveloperBypass(): bool
	{
		$devBypassEnabled = getenv('app.maintenance.bypass.dev') === 'true';
		$appEnv = getenv('app.env') ?: 'production';

		// Allow bypass if explicitly enabled for development environment
		return $devBypassEnabled && $appEnv === 'development';
	}

	/**
	 * Try to set bypass in session (fails silently if session issues)
	 *
	 * @return void
	 */
	private function setBypassSession(): void
	{
		try {
			if (session_status() === PHP_SESSION_NONE) {
				session_start();
			}
			$_SESSION['maintenance_bypass'] = true;
		} catch (\Exception $e) {
			// Silently fail - bypass still works via URL parameter
		}
	}

	/**
	 * Check if bypass was set in session
	 *
	 * @return bool
	 */
	private function checkBypassSession(): bool
	{
		try {
			if (session_status() === PHP_SESSION_ACTIVE) {
				return isset($_SESSION['maintenance_bypass']) && $_SESSION['maintenance_bypass'] === true;
			}
		} catch (\Exception $e) {
			// Silently fail
		}
		return false;
	}

	/**
     * Define middlewares for this controller
     * 
     * Override this method in child controllers
     *
     * @return array
     */
    protected function middleware()
    {
        return [];
    }

    /**
     * Register middleware programmatically
     * 
     * Usage in controller constructor:
     * $this->middleware('auth', 'admin|except:login,register');
     *
     * @param mixed ...$middlewares
     * @return self
     */
    protected function registerMiddleware(...$middlewares)
    {
        foreach ($middlewares as $middleware) {
            $this->middlewares[] = $middleware;
        }

        return $this;
    }

    /**
     * Alias for registerMiddleware
     *
     * @param mixed ...$middlewares
     * @return self
     */
    protected function useMiddleware(...$middlewares)
    {
        return $this->registerMiddleware(...$middlewares);
    }

	public function _runMiddlewares()
	{
		$this->runMiddlewares();
		$this->middlewaresExecuted = true;
	}

	/**
     * Run middlewares defined in routes
     *
     * @return void
     */
    protected function runRouteMiddlewares()
    {
        $currentRoute = $this->router->class . '/' . $this->router->method;
        $routeMiddlewares = \Base\Route\Route::getRouteMiddlewares($currentRoute);

        if (!empty($routeMiddlewares)) {
            $this->middlewareRunner->run($routeMiddlewares, $this->router->method);
        }
    }
	
    /**
     * Run all middlewares
     *
     * @return void
     */
    protected function runMiddlewares()
    {
		// Get current route
        $currentRoute = $this->getCurrentRoute();

		// Get defined routes
		$definedRoutes = \Base\Route\Route::getRoutes();

		$routeWithMiddleware = array_keys_case_insensitive_value(
			$definedRoutes, $currentRoute
		);

        // Get route middlewares
		$route = $routeWithMiddleware[0] ?? '';
        $routeMiddlewares = \Base\Route\Route::getRouteMiddlewares($route);

		// dd($currentRoute, $routeMiddlewares, $definedRoutes);
		
        // Get middlewares from middleware() method
        $definedMiddlewares = $this->middleware();

        // Merge: Route middlewares → Controller middlewares → Registered middlewares
        $allMiddlewares = array_merge($routeMiddlewares, $definedMiddlewares, $this->middlewares);

        // Get current method being called
        $currentMethod = $this->router->method;

        // Run middlewares
        $this->middlewareRunner->run($allMiddlewares, $currentMethod);
    }

	/**
     * Get current route pattern
     *
     * @return string
     */
    protected function getCurrentRoute()
    {
        // Build the current route pattern
        $directory = $this->router->directory;
        $class = $this->router->class;
        $method = $this->router->method;

        // Clean up directory path
        $directory = str_replace(['\\', '../../'], ['/', ''], $directory);
        $directory = trim($directory, '/');

        // Build route string
        if (!empty($directory)) {
            $route = $directory . '/' . $class . '/' . $method;
        } else {
            $route = $class . '/' . $method;
        }

        return $route;
    }

    /**
     * Get executed middlewares
     *
     * @return array
     */
    protected function getExecutedMiddlewares()
    {
        return $this->middlewareRunner->getExecutedMiddlewares();
    }

	/**
	 * Get Client IP
	 *
	 * @return string
	 */
	private function getVisitIP(): string
	{
		$keys = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR'
		];

		foreach ($keys as $key) {
			if (!empty($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
				return $_SERVER[$key];
			}
		}

		if (is_cli()) {
			return "Which is from Webby Cli";
		}

		return "UNKNOWN";
	}

}

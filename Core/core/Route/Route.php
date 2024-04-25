<?php

/**
 * Intelligent, Elegant routing for Webby
 *
 * Inspired by Jamie Rumblelow's Pigeon Route and Bonfire Route
 * 
 * I decided to implement it and made
 * much modification to work with Webby
 * 
 * @author Kwame Oteng Appiah-Nti (Developer Kwame)
 * 
 */

namespace Base\Route;

use Closure;
use Base\Helpers\Inflector;

class Route
{

	/**
	 * Routes array
	 *
	 * @var array
	 */
	protected static $routes = [];

	/**
	 * Router array
	 *
	 * @var array
	 */
	protected static $router = [];

	/**
	 * Temporary routes array
	 *
	 * @var array
	 */
	protected static $temporaryRoutes = [];

	/**
	 * Default Routes array
	 *
	 * @var array
	 */
	protected static $defaultRoutes = [];


	/**
	 * Default Controller variable
	 *
	 * @var string
	 */
	protected static $defaultController = 'app';

	/**
	 * Defined Controller variable
	 *
	 * @var string
	 */
	protected static $definedController = 'app';


	/**
	 * Available Routes array
	 *
	 * @var array
	 */
	protected static $availableRoutes = [];

	/**
	 * Api Routes array
	 *
	 * @var array
	 */
	protected static $apiRoutes = [];

	/**
	 * Route Regex variable
	 *
	 * @var string
	 */
	protected static $routeRegex = '([a-zA-Z0-9\-_]+)';

	/**
	 * Route Namespace variable
	 *
	 * @var string
	 */
	protected static $namespace = '';

	/**
	 * Route Subdomain variable
	 *
	 * @var string
	 */
	protected static $subdomain = '';

	/**
	 * Route Prefix variable
	 *
	 * @var mixed
	 */
	protected static $prefix = null;

	/**
	 * Route Group variable
	 *
	 * @var mixed
	 */
	protected static $group = null;

	/**
	 * Named routes
	 *
	 * @var array
	 */
	protected static $namedRoutes  = [];

	/**
	 * From variable
	 *
	 * @var array
	 */
	protected static $from;

	/**
	 * Options variable
	 *
	 * @var array
	 */
	protected static $options  = [];

	/**
	 * Nested Group variable
	 *
	 * @var string
	 */
	protected static $nestedGroup = '';

	/**
	 * Nested Depth variable
	 *
	 * @var integer
	 */
	protected static $nestedDepth  = 0;

	/**
	 * Set Http status
	 *
	 * @var boolean
	 */
	public static $trueHttp = false;

	/**
	 * Constants for route files
	 */
	const WEB_ROUTE = 'web';
	const RESTFUL_ROUTE = 'api';
	const CONSOLE_ROUTE = 'console';

	/**
	 * Uri variable
	 *
	 * @var string
	 */
	public $uri = '';

	/**
	 * refer to this class
	 *
	 * @var object
	 */
	protected static $self = null;

	/**
	 * Constructor function
	 *
	 * @param boolean $namespace
	 */
	public function __construct($namespace = null)
	{
		if ($namespace) {
			static::$namespace = $namespace;
		}
	}

	// --------------------------- Utility functions ----------------------------------

	/**
	 * Get path to display Route::view()
	 *
	 * @return string
	 */
	private static function routeView()
	{
		return $GLOBALS['CFG']->config['view']['route_views_through'];
	}

	/**
	 * Replace dot to slash
	 *
	 * @param string $string
	 * @return string
	 */
	public static function toSlash($string): string
	{
		$string = is_array($string) ? $string : dot2slash($string);

		if (strstr($string, '.')) {
			$string = str_replace('.', '/', $string);
		}

		return $string;
	}

	/**
	 * Get CodeIgniter Router Instance
	 *
	 * @return object
	 */
	public static function getRouter()
	{
		return static::$router = ci()->router;
	}

	/**
	 * Get Uri
	 *
	 * @return self|string
	 */
	private function getUri()
	{
		return $this->uri;
	}

	/**
	 * Set route
	 *
	 * @param mixed $uri
	 * @return self
	 */
	public function setRoute($uri = null)
	{
		$uri = $this->toSlash($uri);

		if (!empty($uri)) {
			$this->uri = ci()->config->site_url($uri);
		}

		if ($uri === null) {
			$this->uri = ci()->config->site_url('');
		}

		return $this;
	}

	/**
	 * To method
	 *
	 * @param string $uri
	 * @return mixed
	 */
	public function to($uri = '', $param = '')
	{
		$uri = $this->toSlash($uri);

		if (!empty($param)) {
			$uri = $uri . '/' . $param;
		}

		$this->uri = ci()->config->site_url($uri);

		if (empty($this->uri) || is_null($this->uri)) {
			return $this->uri = '';
		}

		return $this;
	}

	/**
	 * Back method
	 *
	 * @param string $uri
	 * @return mixed
	 */
	public function back($uri = '')
	{
		$uri = $this->toSlash($uri);

		$referer = $_SESSION['_webby_previous_url'] ?? ci()->input->server('HTTP_REFERER', FILTER_SANITIZE_URL);

		$referer = $referer ?? site_url('/');

		if (empty($uri)) {
			$this->uri = $referer;
			return $this;
		}

		$referer = site_url($uri);

		return redirect($referer);
	}

	/**
	 * Set Referrer
	 *
	 * @param string $value
	 * @return mixed
	 * 
	 * @Todo To be implemented
	 */
	public function setReferrer($value)
	{
		$value = $this->toSlash($value);

		$_SESSION['_webby_previous_url'] = $value;

		return $_SESSION['_webby_previous_url'];

		// if () {

		// } 

	}

	/**
	 * Redirect routes
	 *
	 * @return self
	 */
	public function redirect()
	{
		if (!empty($this->getUri())) {
			redirect($this->getUri());
		}

		return $this;
	}

	/**
	 * Set a name for defined route
	 * 
	 * @param string $name
	 * @return string|mixed
	 */
	public static function name($name)
	{

		if (isset(self::$namedRoutes[$name])) {
			return self::$namedRoutes[$name];
		} else {
			static::$namedRoutes[$name] = static::$from;
		}

		return null;
	}

	/**
	 * Get a route with a given name
	 *
	 * @return string
	 */
	public function named($name = '')
	{
		return !empty($name) ? static::name($name) : '';
	}

	/**
	 * Get the name of the defined route.
	 *
	 * @return string The name of the route.
	 */
	public static function getName($name = '')
	{
		return (new static)->named($name);
	}

	/**
	 * With method
	 *
	 * @param string $key
	 * @param string $value
	 * @return mixed
	 */
	public function with($key, $value = null)
	{

		ci()->use->library('session');

		if (is_array($key)) {
			ci()->session->set_flashdata($key);
		}

		if (!is_null($value) && is_string($key)) {
			ci()->session->set_flashdata($key, $value);
		}

		$uri = $this->getUri();

		if (!empty($uri)) {
			return $this->redirect();
		}

		return $this;
	}

	/**
	 * With Success
	 *
	 * @param string $message
	 * @return mixed
	 */
	public function withSuccess($message)
	{
		return $this->with('success_message', $message);
	}

	/**
	 * With Error
	 *
	 * @param string $message
	 * @return mixed
	 */
	public function withError($message)
	{
		return $this->with('error_message', $message);
	}

	/**
	 * With Input
	 * Grab all input fields and
	 * set to session to be accessed again
	 * 
	 * @return mixed
	 * 
	 */
	public function withInput($post = [])
	{
		ci()->use->library('session');

		if (empty($post)) {
			$post = ci()->input->post();
		}

		ci()->session->set_tempdata('old', $post, 10);
		ci()->session->set_tempdata('form_error', form_error_array(), 10);

		if (!empty($this->getUri())) {
			return $this->redirect();
		}

	}

	// ---------------------------- Route Energized -------------------------------

	/**
	 * Get all defined routes
	 *
	 * @return string
	 */
	public function allRoutes()
	{
		return self::getRouter()->routes;
	}

	/**
	 * Default routes
	 *
	 * @return mixed
	 */
	public static function defaultRoutes()
	{
		return static::$defaultRoutes = $GLOBALS['default_routes'];
	}

	/**
	 * Available routes
	 *
	 * @return mixed
	 */
	public static function availableRoutes()
	{
		return static::$availableRoutes = $GLOBALS['available_routes'];
	}

	/**
	 * API routes
	 *
	 * @return mixed
	 */
	public static function apiRoutes()
	{
		return static::$apiRoutes = $GLOBALS['api_routes'];
	}

	/* --------------------------------------------------------------
     * BASIC ROUTING
     * ------------------------------------------------------------ */

	/**
	 * Create and Generate All Routes
	 *
	 * @param string $from
	 * @param string $to
	 * @param array $options
	 * @param callable|null $nested
	 * @return void
	 */
	protected static function createRoute($from, $to, $options = [], ?Closure $nested = null)
	{
		$parameterfy = false;

		// Allow for array based routes and other symbol routes
		if (!is_array($to) && strstr($to, '.')) {
			$to = str_replace('.', '/', $to);
		}

		if (is_array($to)) {
			$to = $to[0] . '/' . strtolower($to[1]);
			$parameterfy = true;
		} elseif (
			preg_match('/^([a-zA-Z\_\-0-9\/]+)->([a-zA-Z\_\-0-9\/]+)$/m', $to, $matches)
		) {
			$to = $matches[1] . '/' . $matches[2];
			$parameterfy = true;
		} elseif (
			preg_match('/^([a-zA-Z\_\-0-9\/]+)::([a-zA-Z\_\-0-9\/]+)$/m', $to, $matches)
		) {
			$to = $matches[1] . '/' . $matches[2];
			$parameterfy = true;
		} elseif (
			preg_match('/^([a-zA-Z\_\-0-9\/]+)@([a-zA-Z\_\-0-9\/]+)$/m', $to, $matches)
		) {
			$to = $matches[1] . '/' . $matches[2];
			$parameterfy = true;
		}

		// If $to still contains ->|@|:: 
		// replace them with /
		if (preg_match("/(->|@|::)/i", $to)) {
			$to = str_replace(['->', '::', '@'], ['/', '/', '/'], $to);
		}

		// Do we have a namespace?
		if (static::$namespace) {
			$from = static::$namespace . '/' . $from;
		}

		// Account for parameters in the URL if we need to
		if ($parameterfy) {
			$to = static::parameterfy($from, $to);
		}

		// Apply our routes
		static::$temporaryRoutes[$from] = $to;

		$prefix = is_null(static::$prefix) || empty(static::$prefix) ? '' : static::$prefix . '/';
		$group = is_null(static::$group) || empty(static::$group) ? '' : static::$group . '/';
		
		static::$from = $from;

		$from = static::$nestedGroup . $prefix . $group . $from;

		// Are we saving the name for this one?
		if (isset($options['as']) && !empty($options['as'])) {
			static::$namedRoutes[$options['as']] = $from;
		}

		static::$routes[$from] = $to;

		// Do we have a nested function?
		if ($nested && is_callable($nested) && static::$nestedDepth === 0) {
			static::$nestedGroup    .= rtrim($from, '/') . '/';
			static::$nestedDepth     += 1;
			call_user_func($nested);

			static::$nestedGroup = '';
		}
	}

	/**
	 * Retrieves the HTTP host from 
	 * the $_SERVER['HTTP_HOST'] variable.
	 *
	 * @return string The HTTP host without the port number.
	 */
	public function getHttpHost()
	{
		[$httpHost,] = explode(':', $_SERVER['HTTP_HOST']);
		return $httpHost;
	}

	/**
	 * Examines the HTTP_HOST to get a best match for the subdomain. It
	 * won't be perfect, but should work for our needs.
	 *
	 * It's especially not perfect since it's possible to register a domain
	 * with a period (.) as part of the domain name.
	 * 
	 * Borrowed from CodeIgniter 4 RouteCollection.php
	 * @return mixed
	 */
	private static function getCurrentSubdomain()
	{
		// We have to ensure that an http scheme exists
		// on the URL else parse_url will mis-interpret
		// 'host' as the 'path'.
		// $url = $_SERVER['HTTP_HOST'];

		$isCLI = PHP_SAPI === 'cli' or defined('STDIN');

		$url = !($isCLI) ? $_SERVER['HTTP_HOST'] : '';

		if (strpos($url, 'http') !== 0) {
			$url = 'http://' . $url;
		}

		$parsedUrl = parse_url($url);

		if (($parsedUrl === false) && $isCLI) {
			return [];
		}

		$host = explode('.', $parsedUrl['host']);

		if ($host[0] === 'www') {
			unset($host[0]);
		}

		// Get rid of any domains, which will be the last
		unset($host[count($host)]);

		// Account for .co.uk, .co.nz, etc. domains
		if (end($host) === 'co') {
			$host = array_slice($host, 0, -1);
		}

		// If we only have 1 part left, then we don't have a sub-domain.
		if (count($host) === 1) {
			// Set it to false so we don't make it back here again.
			return false;
		}

		return array_shift($host);
	}

	/* --------------------------------------------------------------
     * HTTP VERB ROUTING
     * ------------------------------------------------------------ */

	/**
	 * Route any HTTP request
	 *
	 * @param string $from The URI pattern to match
	 * @param string $to The destination of the route
	 * @param array $options An array of options for the route
	 * @param callable|bool $nested A callable function to group nested routes, or boolean to check if it's nested
	 * @return self The Route class instance
	 */
	public static function any($from, $to, $options = [], ?Closure $nested = null)
	{
		static::createRoute($from, $to, $options, $nested);

		static::$options = $options;
		// Return a new instance of the Route class
		return new static;
	}

	/**
	 * Route a GET request
	 *
	 * @param string $from The URI pattern to match
	 * @param string $to The destination of the route
	 * @param array $options An array of options for the route
	 * @param callable|null $nested A callable function to group nested routes, or boolean to check if it's nested
	 * @return self The Route class instance
	 */
	public static function get($from, $to, $options = [], ?Closure $nested = null)
	{
		// Check if the current request method is GET
		if (static::methodIs('GET')) {
			// Create the route
			static::createRoute($from, $to, $options, $nested);
		}

		static::$options = $options;
		// Return a new instance of the Route class
		return new static;
	}

	/**
	 * Route a POST request
	 *
	 * @param string $from The URI pattern to match
	 * @param string $to The destination of the route
	 * @param array $options An array of options for the route
	 * @param callable|bool $nested A callable function to group nested routes, or boolean to check if it's nested
	 * @return self The Route class instance
	 */
	public static function post($from, $to, $options = [], ?Closure $nested = null)
	{
		if (static::methodIs('POST')) {
			static::createRoute($from, $to, $options, $nested);
		}

		static::$options = $options;
		// Return a new instance of the Route class
		return new static;
	}

	/**
	 * Route a PUT request
	 *
	 * @param string $from The URI pattern to match
	 * @param string $to The destination of the route
	 * @param array $options An array of options for the route
	 * @param callable|bool $nested A callable function to group nested routes, or boolean to check if it's nested
	 * @return self The Route class instance
	 */
	public static function put($from, $to, $options = [], ?Closure $nested = null)
	{
		if (static::methodIs('PUT')) {
			static::createRoute($from, $to, $options, $nested);
		}

		static::$options = $options;
		// Return a new instance of the Route class
		return new static;
	}

	/**
	 * Route a DELETE request
	 *
	 * @param string $from The URI pattern to match
	 * @param string $to The destination of the route
	 * @param array $options An array of options for the route
	 * @param callable|bool $nested A callable function to group nested routes, or boolean to check if it's nested
	 * @return self The Route class instance
	 */
	public static function delete($from, $to, $options = [], ?Closure $nested = null)
	{
		if (static::methodIs('DELETE')) {
			static::createRoute($from, $to, $options, $nested);
		}

		static::$options = $options;
		// Return a new instance of the Route class
		return new static;
	}

	/**
	 * Route a PATCH request
	 *
	 * @param string $from The URI pattern to match
	 * @param string $to The destination of the route
	 * @param array $options An array of options for the route
	 * @param callable|bool $nested A callable function to group nested routes, or boolean to check if it's nested
	 * @return self The Route class instance
	 */
	public static function patch($from, $to, $options = [], ?Closure $nested = null)
	{
		if (static::methodIs('PATCH')) {
			static::createRoute($from, $to, $options, $nested);
		}

		static::$options = $options;
		// Return a new instance of the Route class
		return new static;
	}

	/**
	 * Route a HEAD request
	 *
	 * @param string $from The URI pattern to match
	 * @param string $to The destination of the route
	 * @param array $options An array of options for the route
	 * @param callable|bool $nested A callable function to group nested routes, or boolean to check if it's nested
	 * @return self The Route class instance
	 */
	public static function head($from, $to, $options = [], ?Closure $nested = null)
	{
		if (
			isset($_SERVER['REQUEST_METHOD']) &&
			$_SERVER['REQUEST_METHOD'] == 'HEAD'
		) {
			static::createRoute($from, $to, $options, $nested);
		}

		static::$options = $options;
		// Return a new instance of the Route class
		return new static;
	}

	/**
	 * Route an OPTIONS request
	 *
	 * @param string $from The URI pattern to match
	 * @param string $to The destination of the route
	 * @param array $options An array of options for the route
	 * @param callable|bool $nested A callable function to group nested routes, or boolean to check if it's nested
	 * @return self The Route class instance
	 */
	public static function options($from, $to, $options = [], ?Closure $nested = null)
	{
		if (
			isset($_SERVER['REQUEST_METHOD']) &&
			$_SERVER['REQUEST_METHOD'] == 'OPTIONS'
		) {
			static::createRoute($from, $to, $options, $nested);
		}

		static::$options = $options;
		// Return a new instance of the Route class
		return new static;
	}

	/**
	 * Cli route
	 *
	 * @param string $from
	 * @param string $to
	 * @param array $options
	 * @param callable|null $nested
	 * @return void
	 */
	public static function cli($from, $to, $options = [], ?Closure $nested = null)
	{
		if (is_cli()) {
			static::createRoute($from, $to, $options, $nested);
		}
	}

	/**
	 * Simple route to get views
	 * from the Views folder
	 *
	 * @param  $name  view name to use as route
	 * @return void
	 */
	public static function view($name = '')
	{
		static::any($name, static::routeView() . $name);
	}

	/**
	 * Simple route to get views
	 * from the Views folder
	 * 
	 * This is an alias to Route::view();
	 * 
	 * @param  $name  view name to use as route
	 * @return void
	 */
	public static function page($name = '')
	{
		static::view($name);
	}

	/**
	 * Web Resource method
	 * Creates resource routes
	 *
	 * @param string $name i.e. module/controller name
	 * @param boolean $hasController
	 * @return void
	 */
	public static function webResource($name, $hasController = true)
	{
		$name = str_replace('/', '.', $name);
		$name = explode('.', $name);
		$module = $name[0];
		$controller = !isset($name[1]) ? $module : $name[1];

		$moc = static::setMOC($module, $controller, $hasController);

		$name = str_replace('.', '/', implode('.', $name));

		static::get($name . '/index', $moc . '/index');
		static::get($name . '/create', $moc . '/create');
		static::post($name . '/store', $moc . '/store');
		static::get($name . '/show/(:any)', $moc . '/show/$1');
		static::get($name . '/edit/(:any)', $moc . '/edit/$1');
		static::put($name . '/update/(:any)', $moc . '/update/$1');
		static::delete($name . '/delete/(:any)', $moc . '/delete/$1');
	}

	/**
	 * Alias to method above
	 *
	 * @param string $name
	 * @param boolean $hasController
	 * @return void
	 */
	public static function uselinks($name, $hasController = true)
	{
		static::webResource($name, $hasController);
	}

	/**
	 * Alias to method above
	 *
	 * @param string $name
	 * @param boolean $hasController
	 * @return void
	 */
	public static function web($name, $hasController = true)
	{
		static::webResource($name, $hasController);
	}

	/**
	 * Send routes outside of application
	 *
	 * @param string $name
	 * @param string $to
	 * @param string $route
	 * @return void
	 */
	public static function outside($name, $to = '', $route = '')
	{

		$parameterfy = false;

		if (is_array($to)) {
			$to = $to[0] . '/' . strtolower($to[1]);
			$parameterfy = true;
		} elseif (preg_match('/^([a-zA-Z\_\-0-9\/]+)->([a-zA-Z\_\-0-9\/]+)$/m', $to, $matches)) {
			$to = $matches[1] . '/' . $matches[2];
			$parameterfy = true;
		} elseif (preg_match('/^([a-zA-Z\_\-0-9\/]+)::([a-zA-Z\_\-0-9\/]+)$/m', $to, $matches)) {
			$to = $matches[1] . '/' . $matches[2];
			$parameterfy = true;
		} elseif (preg_match('/^([a-zA-Z\_\-0-9\/]+)@([a-zA-Z\_\-0-9\/]+)$/m', $to, $matches)) {
			$to = $matches[1] . '/' . $matches[2];
			$parameterfy = true;
		}

		// Account for parameters in the URL if we need to
		if ($parameterfy) {
			$to = static::parameterfy($name, $to);
		}

		if (empty($route)) {
			$route = config_item('default_outside_route');
		}

		// Apply our routes
		static::$temporaryRoutes[$name] = $to;

		static::$routes[$name] = $route . '/' . $to;
	}

	/**
	 * Api Resource method
	 * Creates resource routes
	 *
	 * @param string $name i.e. module/controller name
	 * @param boolean $hasController
	 * @return void
	 */
	public static function apiResource($name, $hasController = true)
	{
		$name = str_replace('/', '.', $name);
		$name = explode('.', $name);
		$module = $name[0];
		$controller = !isset($name[1]) ? $module : $name[1];

		$moc = static::setMOC($module, $controller, $hasController);

		$name = str_replace('.', '/', implode('.', $name));

		static::get($name . '/index', $moc . '/index');
		static::post($name . '/store', $moc . '/store');
		static::get($name . '/show/(:any)', $moc . '/show/$1');
		static::put($name . '/update/(:any)', $moc . '/update/$1');
		static::delete($name . '/delete/(:any)', $moc . '/delete/$1');
	}

	/**
	 * Alias to method above
	 *
	 * @param string $name
	 * @param boolean $hasController
	 * @return void
	 */
	public static function api($name, $hasController = true)
	{
		static::apiResource($name, $hasController);
	}

	/**
	 * Singleton Resource method
	 *
	 * @param string $name i.e. module/controller name
	 * @param boolean $hasController
	 * @return void
	 */
	public static function singleton($name, $hasController = true)
	{
		$name = str_replace('/', '.', $name);
		$name = explode('.', $name);
		$module = $name[0];
		$controller = !isset($name[1]) ? $module : $name[1];

		$moc = static::setMOC($module, $controller, $hasController);

		$name = str_replace('.', '/', implode('.', $name));

		static::get($name . '/show/(:any)', $moc . '/show/$1');
		static::get($name . '/edit/(:any)', $moc . '/edit/$1');
		static::put($name . '/update/(:any)', $moc . '/update/$1');
	}

	/**
	 * Partial Web Resource which 
	 * Creates partial resource routes
	 *
	 * @param string $name
	 * @param array $method
	 * @param boolean $hasController
	 * @return void
	 */
	public static function partial($name, $method = [], $hasController = true)
	{
		$name = str_replace('/', '.', $name);
		$name = explode('.', $name);
		$module = $name[0];
		$controller = !isset($name[1]) ? $module : $name[1];

		$moc = static::setMOC($module, $controller, $hasController);

		$name = str_replace('.', '/', implode('.', $name));

		static::setRouteSignature($name, $method, $moc);
	}

	/**
	 * Unique Route Signature
	 *
	 * @param string $route
	 * @param string $signature
	 * @param boolean $hasController
	 * @return void
	 */
	public static function unique($route, $signature, $hasController = true)
	{
		[$name, $as] = $route;

		$name = str_replace('/', '.', $name);
		$name = explode('.', $name);
		$module = $name[0];
		$controller = !isset($name[1]) ? $module : $name[1];

		$moc = static::setMOC($module, $controller, $hasController);

		$name = str_replace('.', '/', implode('.', $name));

		static::any($name . $as, $moc . $signature);
	}

	/**
	 * Set True http routes
	 *
	 * @param string $route
	 * @param string $httpMethod
	 * @param string $signature
	 * @return void
	 */
	public static function http($httpMethod, $route, $signature)
	{
		static::setRouteHttpMethod($route, $httpMethod, $signature);
	}

	/**
	 * Creates Semi HTTP-verb based routing for a module/controller.
	 *
	 * @param  string $name The name of the controller to route to.
	 * @param  array $options A list of possible ways to customize the routing.
	 * @param  mixed $nested A nested value to be passed into the routes.
	 * @param  bool $hasController A flag to indicate if the controller exists.
	 * @return void
	 */
	public static function resource($name, $options = [], $nested = null, $hasController = true)
	{
		if (empty($name)) {
			return;
		}

		$nestOffset = '';

		// In order to allow customization of the route the
		// resources are sent to, we need to have a new name
		// to store the values in.
		$givenName = $name;

		// If a new controller is specified, then we replace the
		// $name value with the name of the new controller.
		if (isset($options['controller'])) {
			$givenName = $options['controller'];
		}

		// If a new module was specified, simply put that path
		// in front of the controller.
		if (isset($options['module'])) {
			$givenName = $options['module'] . '/' . $givenName;
		}

		// In order to allow customization of allowed id values
		// we need someplace to store them.
		$id = static::$routeRegex;

		if (isset($options['constraint'])) {
			$id = $options['constraint'];
		}

		// If the 'offset' option is passed in, it means that all of our
		// parameter placeholders in the $to ($1, $2, etc), need to be
		// offset by that amount. This is useful when we're using an API
		// with versioning in the URL.
		$offset = isset($options['offset']) ? (int)$options['offset'] : 0;

		if (static::$nestedDepth) {
			$nestOffset = '/$1';
			$offset++;
		}

		$newName = str_replace('/', '.', $givenName);
		$newName = explode('.', $newName);
		$module = $newName[0];
		$controller = !isset($newName[1]) ? $module : $newName[1];

		$moc = static::setMOC($module, $controller, $hasController);

		$newName = str_replace('.', '/', implode('.', $newName));

		static::get($name, $moc . '/index' . $nestOffset, $options, $nested);
		static::get($name    . '/create', $moc . '/create' . $nestOffset, $options, $nested);
		static::post($name   . '/store', $moc . '/store' . $nestOffset, $options, $nested);
		static::get($name    . '/' . '(:any)', $moc . '/show' . $nestOffset . '/$' . (1 + $offset), $options, $nested);
		static::get($name    . '/' . '(:any)' . '/edit', $moc . '/edit' . $nestOffset . '/$' . (1 + $offset), $options, $nested);
		static::put($name    . '/' . '(:any)' . '/update', $moc . '/update' . $nestOffset . '/$' . (1 + $offset), $options, $nested);
		static::delete($name . '/' . '(:any)' . '/delete', $moc . '/delete' . $nestOffset . '/$' . (1 + $offset), $options, $nested);
	}

	/* --------------------------------------------------------------
     * UTILITY FUNCTIONS
     * ------------------------------------------------------------ */

	 /**
	 * Extract the URL parameters 
	 * from $from and copy to $to
	 *
	 * @param string $from
	 * @param string $to
	 * @return string
	 */
	private static function parameterfy($from, $to)
	{
		if (preg_match_all('/\/\((.*?)\)/', $from, $matches)) {

			$params = '';

			foreach ($matches[1] as $i => $match) {
				$i = $i + 1;
				$params .= "/\$$i";
			}

			$to .= $params;
		}

		return $to;
	}


	/**
	 * Sets a default route closure to be executed 
	 * when no specific route is matched.
	 *
	 * @param Closure|null $callable The closure to execute.
	 *  If null, the closure is not executed.
	 * 
	 * @return void
	 */
	public static function default(?Closure $callable = null)
	{
		// Check if the current SUBDOMAIN is not false.
		// If it is not false, we immediately return 
		// and do not execute the closure.
		if (SUBDOMAIN !== false) {
			return;
		}

		call_user_func($callable);
		static::$group = null;
		static::$prefix = null;
		static::$subdomain = null;
	}

	/**
	 * Set subdomain for routes.
	 *
	 * @param string $subdomain The subdomain to set. empty string by default.
	 * @return static Returns a new instance of the class.
	 */
	public static function domain($subdomain = '', ?string $definedController = null)
	{

		[$name,] = explode('.', $subdomain);

		static::$subdomain = $name;

		$currentDomain = (new static)->getCurrentSubdomain();

		if ($definedController !== null) {
			static::$definedController = $definedController;
		}

		if ($currentDomain === static::$subdomain) {
			static::$defaultController = static::$subdomain;
		}

		// Returns a new instance of the class.
		return new static;
	}

	/**
	 * Prefix routes
	 *
	 * @param  string  $name  The prefix to add to the routes.
	 * @param  Closure $callback
	 */
	public static function prefix($name, Closure $callback)
	{
		static::$prefix = $name;
		call_user_func($callback);
		static::$prefix = null;
		static::$subdomain = null;
	}

	/**
	 * Group routes
	 *
	 * @param string $from
	 * @param string $to
	 * 
	 */
	public static function group(?Closure $callable = null)
	{

		$currentDomain = (new static)->getCurrentSubdomain();

		if (static::$subdomain !== $currentDomain) {
			return;
		}

		static::$group = '';
		call_user_func($callable);
		static::$group = null;
		static::$prefix = null;
		static::$subdomain = null;
	}

	/**
	 * Group Module routes
	 *
	 * @param string $from
	 * @param string $to
	 * 
	 */
	public static function module($name, Closure $callable = null)
	{
		static::prefix($name, $callable);
	}

	/**
	 * Easily block access to any number of routes by setting
	 * that route to an empty path ('').
	 *
	 * Example:
	 *     Route::block('posts', 'photos/(:num)');
	 *
	 *     // Same as...
	 *     $route['posts']          = '';
	 *     $route['photos/(:num)']  = '';
	 */
	public static function block()
	{
		$paths = func_get_args();

		if (!is_array($paths)) {
			return;
		}

		foreach ($paths as $path) {
			static::createRoute($path, '');
		}
	}

	/**
	 * Set MoC (Module on Controller)
	 *
	 * @param  string $module
	 * @param string $controller
	 * @param boolean $hasController
	 * @return string
	 */
	private static function setMOC($module, $controller, $hasController)
	{
		$moc = ucfirst($module) . '/' . ucfirst($controller);

		if ($hasController && $controller) {
			$controller = ucfirst(Inflector::singularize($controller)) . "Controller";
			$moc = ucfirst($module) . '/' . $controller;
		}

		return $moc;
	}

	/**
	 * Set Route Signature
	 * Used for special cases
	 *
	 * @param $name
	 * @param mixed $method
	 * @param mixed $moc
	 * @return void
	 */
	private static function setRouteSignature($name, $method, $moc)
	{
		if (in_array('index', $method)) {
			static::get($name . '/index', $moc . '/index');
		}

		if (in_array('create', $method)) {
			static::get($name . '/create', $moc . '/create');
		}

		if (in_array('store', $method)) {
			static::post($name . '/store', $moc . '/store');
		}

		if (in_array('show', $method)) {
			static::get($name . '/show/(:any)', $moc . '/show/$1');
		}

		if (in_array('edit', $method)) {
			static::get($name . '/edit/(:any)', $moc . '/edit/$1');
		}

		if (in_array('update', $method)) {
			static::put($name . '/update/(:any)', $moc . '/update/$1');
		}

		if (in_array('delete', $method)) {
			static::delete($name . '/delete/(:any)', $moc . '/delete/$1');
		}
	}

	/**
	 * Set Route Using HTTP Method
	 * Used to mimic old $routes with HTTP Methods
	 *
	 * Example : $route['some-route-here']['GET'] = 'Module/Controller/method/parameter'
	 * 
	 * @param $name
	 * @param mixed $method
	 * @param mixed $signature
	 * @return void
	 */
	private static function setRouteHttpMethod($name, $httpMethod, $signature)
	{
		$httpMethods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
		$httpMethod = strtoupper($httpMethod);

		if (in_array($httpMethod, $httpMethods)) {
			static::{strtolower($httpMethod)}($name, $signature);
		}
	}
	/**
	 * Clear out the routing table
	 * @return mixed
	 */
	public static function clear()
	{
		static::$routes = [];
	}

	/**
	 * Resets the class to a first-load state. Mainly useful during testing.
	 *
	 * @return void
	 */
	public static function reset()
	{
		static::$routes = [];
		static::$namedRoutes = [];
		static::$nestedDepth = 0;
		static::$group = null;
		static::$prefix = null;
		static::$subdomain = null;
		static::$defaultController = null;
	}

	/**
	 * Return the routes array
	 *
	 * Used as a helper in HMVC Routing
	 * 
	 * @param array $route
	 * @return array
	 */
	public static function build(array $route = [])
	{

		if (empty($route)) {
			$route = static::availableRoutes();
		}

		$route['default_controller'] = static::$definedController 
			?? static::$defaultController 
			?? 'app';

		return array_merge(
			$route,
			static::$routes,
		);
	}

	/**
	 * Alias to above function
	 *
	 * @param array $route
	 * @return array
	 */
	public static function include(array $route = [])
	{
		return static::build($route);
	}

	/**
	 * Include other route files
	 *
	 * @param string $routeFile
	 * @return void
	 */
	public static function import(string $routeFile, $outsourced = false): void
	{
		if (!$outsourced) {
			include_once(ROOTPATH . 'routes' . DS . $routeFile . EXT);
		}

		if ($outsourced) {
			include_once($routeFile);
		}
		
	}

	/**
	 * Verify Http Method
	 * 
	 * And check whether it is to be 
	 * strictly true http method or not
	 *
	 * @param string $method
	 * @return mixed
	 */
	protected static function methodIs($method)
	{
		return (static::$trueHttp === false)
			? $method
			: (isset($_SERVER['REQUEST_METHOD']) &&
				($_SERVER['REQUEST_METHOD'] == $method ||
					($_SERVER['REQUEST_METHOD'] == 'POST' &&
						isset($_POST['_method']) &&
						strtolower($_POST['_method']) == strtolower($method))));
	}

}

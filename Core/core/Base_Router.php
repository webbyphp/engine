<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

defined('COREPATH') or exit('No direct script access allowed');

use Base\HMVC\Modules;

/**
 * Modular Extensions - HMVC
 *
 **/

class Base_Router extends CI_Router
{

	public $router;

	public $module;

	private $located = 0;

	private $controller;

	/**
	 * Construct
	 */
	public function __construct()
	{
		parent::__construct();
	}

	public function fetch_module()
	{
		return $this->module;
	}

	/**
	 * Set the route mapping
	 *
	 * This function determines what should be served based on the URI request,
	 * as well as any "routes" that have been set in the routing config file.
	 *
	 * @access   private
	 * @return   void
	 */
	public function _set_routing()
	{
		// Are query strings enabled in the config file?  Normally CI doesn't utilize query strings
		// since URI segments are more search-engine friendly, but they can optionally be used.
		// If this feature is enabled, we will gather the directory/class/method a little differently
		$segments = [];

		if ($this->config->item('enable_query_strings') === true and isset($_GET[$this->config->item('controller_trigger')])) {

			if (isset($_GET[$this->config->item('directory_trigger')])) {
				$this->set_directory(trim($this->uri->_filter_uri($_GET[$this->config->item('directory_trigger')])));
				$segments[] = $this->router->directory;
			}

			if (isset($_GET[$this->config->item('controller_trigger')])) {
				$this->set_class(trim($this->uri->_filter_uri($_GET[$this->config->item('controller_trigger')])));
				$segments[] = $this->router->class;
			}

			if (isset($_GET[$this->config->item('function_trigger')])) {
				$this->set_method(trim($this->uri->_filter_uri($_GET[$this->config->item('function_trigger')])));
				$segments[] = $this->router->method;
			}
		}

		// Load the routes.php file.
		if (defined('ENVIRONMENT') and is_file(COREPATH . 'config/' . ENVIRONMENT . '/routes.php')) {
			include(COREPATH . 'config/' . ENVIRONMENT . '/routes.php');
		} elseif (is_file(COREPATH . 'config/routes.php')) {
			include(COREPATH . 'config/routes.php');
		}

		// Include routes in every module
		$modules_locations = config_item('modules_locations') ? config_item('modules_locations') : false;

		if (!$modules_locations) {

			$modules_locations = COREPATH . 'modules/';

			if (is_dir($modules_locations)) {
				$modules_locations = [$modules_locations => '../modules/'];
			} else {
				show_error('Modules directory not found');
			}
		}

		foreach ($modules_locations as $key => $value) {

			if ($handle = opendir($key)) {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." && $entry != "..") {
						if (is_dir($key . $entry)) {

							// Use route in modules config directory
							$rfile = Modules::find('Routes' . PHPEXT, $entry, 'Config/');

							if ($rfile[0]) {
								include($rfile[0] . $rfile[1]);
							}

							// Use route types and their corresponding file names in modules routes directory
							$routeTypes = ['Routes/Web' . PHPEXT, 'Routes/Api' . PHPEXT, 'Routes/Console' . PHPEXT];

							// Loop through route types and include corresponding files if found
							foreach ($routeTypes as $routeType) {
								$routeFile = Modules::find(basename($routeType), $entry, 'Routes/'); // dirname($routeType) . '/'

								if ($routeFile[0]) {
									include $routeFile[0] . $routeFile[1];
								}
							}
						}
					}
				}

				closedir($handle);
			}
		}

		$this->routes = (!isset($route) or !is_array($route)) ? [] : $route;
		unset($route);

		// Set the default controller so we can display it in the event
		// the URI doesn't correlated to a valid controller.
		$this->default_controller = (!isset($this->routes['default_controller']) or $this->routes['default_controller'] == '') ? false : strtolower($this->routes['default_controller']);

		// Were there any query string segments?  If so, we'll validate them and bail out since we're done.
		if (count($segments) > 0) {
			return $this->_validate_request($segments);
		}

		// Fetch the complete URI string
		$this->uri->uri_string();

		// Is there a URI string? If not, the default controller specified in the "routes" file will be shown.
		if ($this->uri->uri_string == '') {
			return $this->_set_default_controller();
		}

		// Do we need to remove the URL suffix?
		$this->uri->slash_segment($this->uri->total_segments());

		// Compile the segments into an array
		$this->uri->segment_array();

		// Parse any custom routing that may exist
		$this->_parse_routes();

		// Re-index the segment array so that it starts with 1 rather than 0
		$this->uri->rsegment_array();
	}

    // --------------------------------------------------------------------

	/**
	 * Parse Routes
	 *
	 * Matches any routes that may exist in the config/routes.php file
	 * against the URI to determine if the class/method need to be remapped.
	 *
	 * @return	void
	 */
	protected function _parse_routes()
	{
		// Turn the segment array into a URI string
		$uri = implode('/', $this->uri->segments);

		// Get HTTP verb
		$http_verb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';

		// Loop through the route array looking for wildcards
		foreach ($this->routes as $key => $val) {
			// Check if route format is using HTTP verbs
			if (is_array($val)) {
				$val = array_change_key_case($val, CASE_LOWER);
				if (isset($val[$http_verb])) {
					$val = $val[$http_verb];
				} else {
					continue;
				}
			}

			// Check if named parameters exists
			$key = $this->namedParameter($key);

			// Convert wildcards to RegEx
			$key = str_replace(
				[':any', ':num', ':uuid', ':alphanum', ':alpha', ':subdomain'],
				[
					'[^/]+',
					'[0-9]+',
					'[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
					'[a-zA-Z]+[a-zA-Z0-9._]+$',
					'[a-zA-Z]+$',
					'[-_0-9{$alpha}]+.'
				],
				$key
			);

			// Does the RegEx match?
			if (preg_match('#^' . $key . '$#', $uri, $matches)) {
				// Are we using callbacks to process back-references?
				if (!is_string($val) && is_callable($val)) {
					// Remove the original string from the matches array.
					array_shift($matches);

					// Execute the callback using the values in matches as its parameters.
					$val = call_user_func_array($val, $matches);
				}
				// Are we using the default routing method for back-references?
				elseif (strpos($val, '$') !== FALSE && strpos($key, '(') !== FALSE) {
					$val = preg_replace('#^' . $key . '$#', $val, $uri);
				}

				$val = $val ??= '';

				$this->_set_request(explode('/', $val));
				return;
			}
		}

		// If we got this far it means we didn't encounter a
		// matching route so we'll set the site default route
		$this->_set_request(array_values($this->uri->segments));
	}

	/**
	 * Convert {id} and {num} to :num OR {anytext} to :any
	 *
	 * @param string $key
	 * @return string
	 */
	private function namedParameter($key)
	{

		$key = str_replace('{id}', '(:num)', $key);
		$key = str_replace('{num}', '(:num)', $key);
		$key = str_replace('{uuid}', '(:uuid)', $key);
		$key = str_replace('{alpha}', '(:alpha)', $key);
		$key = str_replace('{alphanum}', '(:alphanum)', $key);
		$key = str_replace('{subdomain}', '(:subdomain)', $key);

		$hasCurly = strpos($key, '{');
		$defaultKey = $key;

		$key = ($hasCurly && !(strpos($defaultKey, '{id}')))
			? preg_replace('/\{(.+?)\}/', '(:any)', $key)
			: $key;

		return $key;
	}

	protected function _set_request($segments = [])
	{
		if ($this->translate_uri_dashes === true) {
			foreach (range(0, 2) as $v) {
				isset($segments[$v]) && $segments[$v] = str_replace('-', '_', $segments[$v]);
			}
		}

		$segments = $this->locate($segments);

		if ($this->located == -1) {
			$this->_set_404override_controller();
			return;
		}

		if (empty($segments)) {
			$this->_set_default_controller();
			return;
		}

		$this->set_class($segments[0]);

		if (isset($segments[1])) {
			$this->set_method($segments[1]);
		} else {
			$segments[1] = 'index';
		}

		array_unshift($segments, null);
		unset($segments[0]);
		$this->uri->rsegments = $segments;
	}

	protected function _set_404override_controller()
	{
		$this->_set_module_path($this->routes['404_override']);
	}

	protected function _set_default_controller()
	{
		if (empty($this->directory)) {
			/* set the default controller module path */
			$this->_set_module_path($this->default_controller);
		}

		parent::_set_default_controller();

		if (empty($this->class)) {
			$this->_set_404override_controller();
		}
	}

	/** Locate the controller **/
	public function locate($segments)
	{
		$this->located = 0;
		$ext = $this->config->item('controller_suffix') . PHPEXT;
		$commandsDirectory = "Commands";
		$controllersDirectory = "Controllers";

		/* use module route if available */
		if (isset($segments[0]) && $routes = Modules::parse_routes($segments[0], implode('/', $segments))) {
			$segments = $routes;
		}

		/* get the segments array elements */
		list($module, $directory, $controller) = array_pad($segments, 3, null);

		if ($module === $controllersDirectory) {
			list($module, $directory, $controller) = array_pad($segments, 3, null);
		}

		if ($module === $commandsDirectory) {
			list($module, $controller) = array_pad($segments, 2, null);
		}

		if (str_contains((string) $directory, 'command')) {
			$directory = str_replace('command', 'Command', (string) $directory);
			$controller = str_replace('command', 'Command', (string) $controller);
		}

		$module ??= '';
		$directory ??= '';
		$controller ??= '';

		/* check modules */
		foreach (Modules::$locations as $location => $offset) {

			$hasCommand = str_contains((string) $directory, 'Command');

			if ($module === $controllersDirectory) {
				$location = APPROOT;
				$source = $location . ucfirst($module) . '/';
				$controller_location = ucfirst($module) . '/';
			} else if ($module === $commandsDirectory) {
				$source = $location . ucfirst($module) . '/';
				$controller_location = ucfirst($module) . '/';
			} else if ($hasCommand) {
				$source = $location . ucfirst($module) . '/Commands/';
				$controller_location = ucfirst($module) . '/Commands/';
				$controller = $controller ?? '';
			} else {
				$source = $location . ucfirst($module) . '/Controllers/';
				$controller_location = ucfirst($module) . '/Controllers/';
				$controller = $controller ?? '';
			}

			/* module exists? */
			if (is_dir($source)) {
				$this->module = ucfirst($module);
				$this->directory = $offset . $controller_location;
				$this->controller = $controller;

				if ($module === $controllersDirectory) {
					$offset = str_replace('/Console/', '/Controllers/', $offset);
					$this->directory = $offset;
				}

				/* module sub-controller exists? */
				if ($directory) {
					/* App/Controllers sub-directory controller exists? */
					if (is_file($source . ucfirst($directory) . '/' . ucfirst($controller) . $ext)) {
						$source .= ucfirst($directory) . '/';
						$this->directory .= ucfirst($directory) . '/';

						/* verify sub-directory controller exists? */
						if ($controller) {
							if (is_file($source . ucfirst($controller) . $ext)) {
								$this->located = 3;
								return array_slice($segments, 2);
							} else $this->located = -1;
						}
					}
					/* module sub-directory exists? */ else if (is_dir($source . ucfirst($directory) . '/')) {
						$source .= ucfirst($directory) . '/';
						$this->directory .= ucfirst($directory) . '/';

						/* module sub-directory controller exists? */
						if ($controller) {
							if (is_file($source . ucfirst($controller) . $ext)) {
								$this->located = 3;
								return array_slice($segments, 2);
							} else $this->located = -1;
						}
					} else if (is_file($source . ucfirst($directory) . $ext)) {
						$this->located = 2;
						return array_slice($segments, 1);
					} else $this->located = -1;
				}

				/* module controller exists? */
				if (is_file($source . ucfirst($module) . $ext)) {
					$this->located = 1;
					return $segments;
				}

				/* controller exists in commands directory? */
				if (is_file($source . '/' . ucfirst($this->controller) . $ext)) {
					$this->located = 1;
					return $segments;
				}
			}
		}

		if (!empty($this->directory)) return;

		// /* controller exists in App/Controllers directory? */
		// if (is_file(APPROOT . 'Controllers/' . ucfirst($module) . $ext)) {
		// 	$directory = $module;
		// }

		/* controller exists in commands directory? */
		if (is_file(COREPATH . 'controllers/' . $commandsDirectory . '/' . ucfirst($module) . $ext)) {
			$directory = $module;
		}

		/* application sub-directory controller exists? */
		if ($directory) {

			/* controller exists in App/Controllers sub-sub-directory? */
			if ($controller) {
				if (is_file(APPROOT . 'Controllers/' . ucfirst($module) . '/' . ucfirst($directory) . '/' . ucfirst($controller) . $ext)) {
					$this->directory = ucfirst($module) . '/' . ucfirst($directory) . '/';
					return array_slice($segments, 2);
				}
			}

			if (is_file(COREPATH . 'controllers/' . $module . '/' . ucfirst($directory) . $ext)) {
				$this->directory = $module . '/';
				return array_slice($segments, 1);
			}

			if (is_file(COREPATH . 'controllers/' . $commandsDirectory . '/' . ucfirst($directory) . $ext)) {
				$this->directory = $commandsDirectory . '/';
				return $segments;
			}

			/* application sub-sub-directory controller exists? */
			if ($controller) {
				if (is_file(COREPATH . 'controllers/' . $module . '/' . $directory . '/' . ucfirst($controller) . $ext)) {
					$this->directory = $module . '/' . $directory . '/';
					return array_slice($segments, 2);
				}
			}
		}

		/* controller exists in App/Controllers sub-directory? */
		if (is_dir(APPROOT . 'Controllers/' . ucfirst($module) . '/')) {
			$this->directory = ucfirst($module) . '/';
			return array_slice($segments, 1);
		}

		/* controller exists in App/Controllers directory? */
		if (is_file(APPROOT . 'Controllers/' . ucfirst($module) . $ext)) {
			return $segments;
		}

		/* application controllers sub-directory exists? */
		if (is_dir(COREPATH . 'controllers/' . $module . '/')) {
			$this->directory = $module . '/';
			return array_slice($segments, 1);
		}

		/* application controller exists? */
		if (is_file(COREPATH . 'controllers/' . ucfirst($module) . $ext)) {
			return $segments;
		}

		$this->located = -1;
	}

	/* set module path */
	protected function _set_module_path(&$_route)
	{
		if (! empty($_route)) {
			// Are module/directory/controller/method segments being specified?
			$sgs = sscanf($_route, '%[^/]/%[^/]/%[^/]/%s', $module, $directory, $class, $method);

			// set the module/controller directory location if found
			if ($this->locate([$module, $directory, $class])) {
				//reset to class/method
				switch ($sgs) {
					case 1:
						$_route = $module . '/index';
						break;
					case 2:
						$_route = ($this->located < 2) ? $module . '/' . $directory : $directory . '/index';
						break;
					case 3:
						$_route = ($this->located == 2) ? $directory . '/' . $class : $class . '/index';
						break;
					case 4:
						$_route = ($this->located == 3) ? $class . '/' . $method : $method . '/index';
						break;
				}
			}
		}
	}

	public function set_class($class)
	{
		$suffix = strval($this->config->item('controller_suffix'));

		$string_position = !empty($suffix) ? strpos($class, $suffix) : false;

		$class = str_contains($class, 'command')
			? ucfirst(str_replace('command', 'Command', $class))
			: $class;

		if ($string_position === false) {
			$class .= $suffix;
		}

		parent::set_class($class);
	}
}
/* end of file Base_Router.php */

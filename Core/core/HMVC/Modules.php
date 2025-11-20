<?php

namespace Base\HMVC;

global $CFG;

/* PHP5 spl_autoload */
spl_autoload_register('\Base\HMVC\Modules::autoload');

/* get module locations from config settings or use the default module location and offset */
is_array(Modules::$locations = $CFG->item('modules_locations')) or Modules::$locations = [
	COREPATH . 'modules/' => '../modules/',
];

/**
 * Modular Extensions - HMVC
 *
 * Adapted from the CodeIgniter Core Classes
 * @link	http://codeigniter.com
 *
 * Description:
 * This library provides functions to load and instantiate controllers
 * and module controllers allowing use of modules and the HMVC design pattern.
 *
 * Install this file as application/third_party/MX/Modules.php
 *
 * @copyright	Copyright (c) 2015 Wiredesignz
 * @version 	5.5
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 **/
class Modules
{
	public static $routes, $registry, $locations;

	/**
	 * Run a module controller method
	 * Output from module is buffered and returned.
	 **/
	public static function run($module)
	{
		$method = 'index';

		if (($pos = strrpos($module, '/')) != false) {
			$method = substr($module, $pos + 1);
			$module = substr($module, 0, $pos);
		}

		if ($class = self::load($module)) {
			if (method_exists($class, $method)) {
				ob_start();
				$args = func_get_args();
				$output = call_user_func_array([$class, $method], array_slice($args, 1));
				$buffer = ob_get_clean();
				return ($output !== null) ? $output : $buffer;
			}
		}

		log_message('error', "Module controller failed to run: {$module}/{$method}");
	}

	/** Load a module controller **/
	public static function load($module)
	{
		(is_array($module)) ? [$module, $params] = with_each($module) : $params = null;

		/* get the requested controller class name */
		$alias = strtolower(basename($module));

		/* create or return an existing controller from the registry */
		if (! isset(self::$registry[$alias])) {
			/* find the controller */
			[$class] = ci()->router->locate(explode('/', $module));

			/* controller cannot be located */
			if (empty($class)) return;

			/* set the module directory */
			$path = COREPATH . 'controllers/' . ci()->router->directory;

			/* load the controller class */
			$class = $class . ci()->config->item('controller_suffix');
			self::load_file(ucfirst($class), $path);

			/* create and register the new controller */
			$controller = ucfirst($class);
			self::$registry[$alias] = new $controller($params);
		}

		return self::$registry[$alias];
	}

	/** Library base class autoload **/
	public static function autoload($class)
	{
		/* don't autoload CI_ prefixed classes or those using the config subclass_prefix */
		if (strstr($class, 'CI_') or strstr($class, config_item('subclass_prefix'))) return;

		/* autoload Modular Extensions MX core classes */
		// if (strstr($class, 'MX_')) 
		// {
		// 	if (is_file($location = dirname(__FILE__).'/'.substr($class, 3).PHPEXT)) 
		// 	{
		// 		include_once $location;
		// 		return;
		// 	}
		// 	show_error('Failed to load MX core class: '.$class);
		// }

		/* autoload core classes */
		if (is_file($location = COREPATH . 'core/' . ucfirst($class) . PHPEXT)) {
			include_once $location;
			return;
		}

		/* autoload library classes */
		if (is_file($location = COREPATH . 'libraries/' . ucfirst($class) . PHPEXT)) {
			include_once $location;
			return;
		}
	}

	/** Load a module file **/
	public static function load_file($file, $path, $type = 'other', $result = true)
	{
		$file = str_replace(PHPEXT, '', $file);
		$location = $path . $file . PHPEXT;

		if ($type === 'other') {
			if (class_exists($file, false)) {
				log_message('debug', "File already loaded: {$location}");
				return $result;
			}
			include_once $location;
		} else {
			/* load config or language array */
			include $location;

			if (! isset($$type) or ! is_array($$type))
				show_error("{$location} does not contain a valid {$type} array");

			$result = $$type;
		}
		log_message('debug', "File loaded: {$location}");
		return $result;
	}

	/** 
	 * Find a file
	 * Scans for files located within modules directories.
	 * Also scans application directories for models, plugins and views.
	 * Generates fatal error if file not found.
	 **/
	public static function find($file, $module, $base)
	{
		$segments = explode('/', $file);

		$file = array_pop($segments);
		$file_ext = (pathinfo($file, PATHINFO_EXTENSION)) ? $file : $file . PHPEXT;

		$path = ltrim(implode('/', $segments) . '/', '/');
		$module ? $modules[$module] = $path : $modules = [];

		if (! empty($segments)) {
			$modules[array_shift($segments)] = ltrim(implode('/', $segments) . '/', '/');
		}

		foreach (\Base\HMVC\Modules::$locations as $location => $offset) {
			foreach ($modules as $module => $subpath) {
				$fullpath = $location . $module . '/' . ucfirst($base) . $subpath;

				if ($base == 'Libraries/' or $base == 'Models/') {
					if (is_file($fullpath . ucfirst($file_ext))) return [$fullpath, ucfirst($file)];
				} else
					/* load non-class files */
					if (is_file($fullpath . $file_ext)) return [$fullpath, $file];
			}
		}

		return [false, $file];
	}

	/** Parse module routes **/
	public static function parse_routes($module, $uri)
	{
		/* load the route file */
		if (! isset(self::$routes[$module])) {
			if (list($path) = self::find('Routes', $module, 'Config/')) {
				$path && self::$routes[$module] = self::load_file('Routes', $path, 'route');
			}
		}

		if (! isset(self::$routes[$module])) return;

		/* parse module routes */
		foreach (self::$routes[$module] as $key => $val) {
			$key = str_replace([':any', ':num'], ['.+', '[0-9]+'], $key);

			if (preg_match('#^' . $key . '$#', $uri)) {
				if (strpos($val, '$') !== false and strpos($key, '(') !== false) {
					$val = preg_replace('#^' . $key . '$#', $val, $uri);
				}
				return explode('/', $module . '/' . $val);
			}
		}
	}
}

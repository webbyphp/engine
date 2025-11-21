<?php

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
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
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Common Functions
 *
 * Loads the base classes and executes the request.
 *
 * @package		CodeIgniter
 * @subpackage	CodeIgniter
 * @category	Common Functions
 * @author		EllisLab Dev Team
 * @author		Kwame Oteng Appiah-Nti
 * @link		https://codeigniter.com/userguide3/
 */

// ------------------------------------------------------------------------

if (! function_exists('is_php')) {
	/**
	 * Determines if the current version of PHP is equal to or greater than the supplied value
	 *
	 * @param	string
	 * @return	bool	true if the current version is $version or higher
	 */
	function is_php($version)
	{
		static $_is_php;
		$version = (string) $version;

		if (! isset($_is_php[$version])) {
			$_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
		}

		return $_is_php[$version];
	}
}

// ------------------------------------------------------------------------

if (! function_exists('is_really_writable')) {
	/**
	 * Tests for file writability
	 *
	 * is_writable() returns true on Windows servers when you really can't write to
	 * the file, based on the read-only attribute. is_writable() is also unreliable
	 * on Unix servers if safe_mode is on.
	 *
	 * @link	https://bugs.php.net/bug.php?id=54709
	 * @param	string
	 * @return	bool
	 */
	function is_really_writable($file)
	{

		// Compatible code for PHP 8.5
		// If we're on a Unix server we call is_writable
		if (DIRECTORY_SEPARATOR === '/') {
			return is_writable($file);
		}

		/* For Windows servers and safe_mode "on" installations we'll actually
		 * write a file then read it. Bah...
		 */
		if (is_dir($file)) {
			$file = rtrim($file, '/') . '/' . md5(mt_rand());
			if (($fp = @fopen($file, 'ab')) === false) {
				return false;
			}

			fclose($fp);
			@chmod($file, 0777);
			@unlink($file);
			return true;
		} elseif (! is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
			return false;
		}

		fclose($fp);
		return true;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('is')) {
	/**
	 *  'Is' function to handle codeigniter 
	 *   interanl is_* helper functions
	 *
	 *  @param     string     $key
	 *  @param     string     $value
	 *  @return    bool
	 */
	function is($key, $value = null)
	{
		$common		= ['https', 'cli', 'php', 'writable'];
		$useragent	= ['browser', 'mobile', 'referral', 'robot'];
		$environment = ['production', 'testing', 'staging', 'development'];

		if (in_array($key, $environment)) {
			return $key === ENVIRONMENT;
		}

		if (in_array($key, $useragent)) {
			return get_instance()->user_agent->{'is_' . $key}($value);
		}

		if (in_array($key, $common)) {
			$function = ($key == 'writable')
				? 'is_really_writable'
				: 'is_' . $key;

			return $function($value);
		}

		if ($key == 'ajax') {
			return get_instance()->input->is_ajax_request();
		}

		if ($key == 'htmx' || $key === 'HTMX') {
			return get_instance()->input->isHtmx();
		}

		if ($key == 'boosted' || $key === 'BOOSTED') {
			return get_instance()->input->isBoosted();
		}

		if ($key == 'get') {
			return (get_instance()->input->server('REQUEST_METHOD') === 'GET');
		}

		if ($key == 'post') {
			return (get_instance()->input->server('REQUEST_METHOD') === 'POST');
		}

		if ($key == 'put') {
			return (get_instance()->input->server('REQUEST_METHOD') === 'PUT');
		}

		if ($key == 'patch') {
			return (get_instance()->input->server('REQUEST_METHOD') === 'PATCH');
		}

		if ($key == 'delete') {
			return (get_instance()->input->server('REQUEST_METHOD') === 'DELETE');
		}

		if ($key == 'loaded' or $key == 'load') {
			return (bool) get_instance()->load->is_loaded($value);
		}

		return false;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('boolify')) {
	/**
	 * Convert common true/false strings into boolean values
	 *
	 * @param int|string $status
	 * @return bool
	 */
	function boolify(int|string $status)
	{
		return filter_var(
			$status,
			FILTER_VALIDATE_BOOLEAN
		);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('is_blank')) {
	/**
	 * Determines if the given value is "blank"
	 *
	 * From https://amitmerchant.com/cool-helper-function-to-check-anything-blank-php
	 * 
	 * @param mixed $value
	 * @return bool
	 */
	function is_blank($value)
	{
		if (is_null($value)) {
			return true;
		}

		if (is_string($value)) {
			return trim($value) === '';
		}

		if (is_numeric($value) || is_bool($value)) {
			return false;
		}

		if ($value instanceof Countable) {
			return count($value) === 0;
		}

		return empty($value);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('import')) {
	/**
	 * Include once webby syntax
	 *
	 * @param string $path
	 * @return void
	 */
	function import($path)
	{
		include_once($path);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('calendar')) {

	/**
	 * Calendar class helper function
	 *
	 * @param array $config
	 * @return mixed
	 */
	function calendar($config = [])
	{

		$calendar = load_class('Calendar');

		if (!empty($config)) {
			$calendar->initialize($config);
		}

		return $calendar;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('hooks')) {
	/**
	 * Helper function to load and return an instance of the Hooks class.
	 *
	 * This function loads and returns an instance of the Hooks class,
	 * which is responsible for handling hooks in the application.
	 *
	 * @return CI_Hooks An instance of the Hooks class.
	 * 
	 */
	function hooks()
	{
		/**
		 * Instance of the Hooks class.
		 *
		 * @var CI_Hooks
		 */
		$hooks = load_class('Hooks', 'core');

		// Return the instance of the Hooks class.
		return $hooks;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('load_class')) {
	/**
	 * Class registry
	 *
	 * This function acts as a singleton. If the requested class does not
	 * exist it is instantiated and set to a static variable. If it has
	 * previously been instantiated the variable is returned.
	 *
	 * @param	string	the class name being requested
	 * @param	string	the directory where the class should be found
	 * @param	mixed	an optional argument to pass to the class constructor
	 * @return	object
	 */
	function &load_class($class, $directory = 'libraries', $param = null)
	{
		static $_classes = [];

		// Does the class exist? If so, we're done...
		if (isset($_classes[$class])) {
			return $_classes[$class];
		}

		$name = false;

		// Look for the class first in the local application/libraries folder
		// then in the native system/libraries folder
		foreach ([COREPATH, BASEPATH] as $path) {
			if (file_exists($path . $directory . '/' . $class . '.php')) {
				$name = 'CI_' . $class;

				if (class_exists($name, false) === false) {
					require_once($path . $directory . '/' . $class . '.php');
				}

				break;
			}
		}

		// Is the request a class extension? If so we load it too
		if (file_exists(COREPATH . $directory . '/' . config_item('subclass_prefix') . $class . '.php')) {
			$name = config_item('subclass_prefix') . $class;

			if (class_exists($name, false) === false) {
				require_once(COREPATH . $directory . '/' . $name . '.php');
			}
		}

		// Did we find the class?
		if ($name === false) {
			// Note: We use exit() rather than show_error() in order to avoid a
			// self-referencing loop with the Exceptions class
			set_status_header(503);
			echo 'Unable to locate the specified class: ' . $class . '.php';
			exit(5); // EXIT_UNK_CLASS
		}

		// Keep track of what we just loaded
		is_loaded($class);

		$_classes[$class] = isset($param)
			? new $name($param)
			: new $name();
		return $_classes[$class];
	}
}

if (! function_exists('use_class')) {
	/**
	 * Class registry
	 * 
	 * Alias to the load_class() function
	 * 
	 * This function acts as a singleton. If the requested class does not
	 * exist it is instantiated and set to a static variable. If it has
	 * previously been instantiated the variable is returned.
	 * 
	 * @param	string	the class name being requested
	 * @param	string	the directory where the class should be found
	 * @param	mixed	an optional argument to pass to the class constructor
	 * @return	object
	 */

	function &use_class($class, $directory = 'libraries', $param = null)
	{
		return load_class($class, $directory, $param);
	}
}

// --------------------------------------------------------------------

if (! function_exists('is_loaded')) {
	/**
	 * Keeps track of which libraries have been loaded. This function is
	 * called by the load_class() function above
	 *
	 * @param	string
	 * @return	array
	 */
	function &is_loaded($class = '')
	{
		static $_is_loaded = [];

		if ($class !== '') {
			$_is_loaded[strtolower($class)] = $class;
		}

		return $_is_loaded;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('get_config')) {
	/**
	 * Loads the main config.php file
	 *
	 * This function lets us grab the config file even if the Config class
	 * hasn't been instantiated yet
	 *
	 * @param	array
	 * @return	array
	 */
	function &get_config(array $replace = [])
	{
		static $config;

		if (empty($config)) {
			$file_path = COREPATH . 'config/config.php';
			$found = false;
			if (file_exists($file_path)) {
				$found = true;
				require($file_path);
			}

			// Is the config file in the environment folder?
			if (file_exists($file_path = COREPATH . 'config/' . ENVIRONMENT . '/config.php')) {
				require($file_path);
			} elseif (! $found) {
				set_status_header(503);
				echo 'The configuration file does not exist.';
				exit(3); // EXIT_CONFIG
			}

			$root_file_path = ROOTPATH . 'config/config.php';

			$found = false;
			if (file_exists($root_file_path)) {
				$found = true;
				require($root_file_path);
			}

			// Is the config file in the environment folder?
			if (file_exists($root_file_path = ROOTPATH . 'config/' . ENVIRONMENT . '/config.php')) {
				require($root_file_path);
			} elseif (! $found) {
				set_status_header(503);
				echo 'The root configuration file does not exist.';
				exit(3); // EXIT_CONFIG
			}

			// Does the $config array exist in the file?
			if (! isset($config) or ! is_array($config)) {
				set_status_header(503);
				echo 'Your config file does not appear to be formatted correctly.';
				exit(3); // EXIT_CONFIG
			}
		}

		// Are any values being dynamically added or replaced?
		foreach ($replace as $key => $val) {
			$config[$key] = $val;
		}

		return $config;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('config_item')) {
	/**
	 * Returns the specified config item
	 *
	 * @param	string
	 * @return	mixed
	 */
	function config_item($item)
	{
		static $_config;

		if (empty($_config)) {
			// references cannot be directly assigned to static variables, so we use an array
			$_config[0] = get_config();
		}

		return isset($_config[0][$item]) ? $_config[0][$item] : null;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('get_mimes')) {
	/**
	 * Returns the MIME types array from config/mimes.php
	 *
	 * @return	array
	 */
	function &get_mimes()
	{
		static $_mimes;

		if (empty($_mimes)) {
			$_mimes = file_exists(COREPATH . 'config/mimes.php')
				? include(COREPATH . 'config/mimes.php')
				: [];

			if (file_exists(COREPATH . 'config/' . ENVIRONMENT . '/mimes.php')) {
				$_mimes = array_merge($_mimes, include(COREPATH . 'config/' . ENVIRONMENT . '/mimes.php'));
			}
		}

		return $_mimes;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('is_https')) {
	/**
	 * Is HTTPS?
	 *
	 * Determines if the application is accessed via an encrypted
	 * (HTTPS) connection.
	 *
	 * @return	bool
	 */
	function is_https()
	{
		if (! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
			return true;
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
			return true;
		} elseif (! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
			return true;
		}

		return false;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('is_cli')) {

	/**
	 * Is CLI?
	 *
	 * Test to see if a request was made from the command line.
	 *
	 * @return 	bool
	 */
	function is_cli()
	{
		return (PHP_SAPI === 'cli' or defined('STDIN'));
	}
}

// ------------------------------------------------------------------------

// ------------------------------------------------------------------------

if (! function_exists('show_error')) {
	/**
	 * Error Handler
	 *
	 * This function lets us invoke the exception class and
	 * display errors using the standard error template located
	 * in application/views/errors/error_general.php
	 * This function will send the error page directly to the
	 * browser and exit.
	 *
	 * @param	string
	 * @param	int
	 * @param	string
	 * @return	void
	 */
	function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered')
	{
		$context = detect_request_context();
		$status_code = abs($status_code);

		switch ($context) {
			case 'web':
				// Create exception for ErrorHandler
				$exception = new Exception($message, $status_code);
				$errorHandler = get_error_handler_instance();
				$errorHandler->handleException($exception);
				return;
			case 'api':
				// display_api_error($severity, $message, $filepath, $line);
				$exception = new Exception($message, $status_code);
				$errorHandler = get_error_handler_instance();
				$errorHandler->handleException($exception);
				return;

			case 'cli':
				$exception = new Exception($message, $status_code);
				$errorHandler = get_error_handler_instance();
				$errorHandler->handleException($exception);
				return;
				// 	display_cli_error($severity, $message, $filepath, $line);
				// 	break;

				//   case 'api':
				//     //   display_api_error($heading, $message, $status_code);
				//       break;

				//   case 'cli':
				//     //   display_cli_error($heading, $message, $status_code);
				//       break;
		}

		if ($status_code < 100) {
			$exit_status = $status_code + 9; // 9 is EXIT__AUTO_MIN
			$status_code = 500;
		} else {
			$exit_status = 1; // EXIT_ERROR
		}

		// $_error = load_class('Exceptions', 'core');
		// echo $_error->show_error($heading, $message, 'error_general', $status_code);
		exit($exit_status);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('show_404')) {
	/**
	 * 404 Page Handler
	 *
	 * This function is similar to the show_error() function above
	 * However, instead of the standard error template it displays
	 * 404 errors.
	 *
	 * @param	string
	 * @param	bool
	 * @return	void
	 */
	function show_404($page = '', $log_error = true)
	{
		$context = detect_request_context();

		$_error = load_class('Exceptions', 'core');
		$_error->show_404($page, $log_error);
		exit(4); // EXIT_UNKNOWN_FILE
	}
}

// ------------------------------------------------------------------------

if (! function_exists('log_message')) {
	/**
	 * Error Logging Interface
	 *
	 * We use this as a simple mechanism to access the logging
	 * class and send messages to be logged.
	 *
	 * @param	string	the error level: 'error', 'debug' or 'info'
	 * @param	string	the error message
	 * @return	void
	 */
	function log_message($level, $message)
	{
		static $_log;

		if ($_log === null) {
			// references cannot be directly assigned to static variables, so we use an array
			$_log[0] = load_class('Log', 'core');
		}

		$_log[0]->write_log($level, $message);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('logmsg')) {
	/**
	 * Debug Log logmsg()
	 * 
	 * An implementation of the function above
	 *
	 * We use this to access the very common logs
	 * like user, app, dev and error messages to be logged.
	 *
	 * @return	object
	 */
	function logmsg()
	{
		$log = new class {

			/**
			 * Log levels available
			 * 'USER' => '1',
			 * 'APP' => '2',
			 * 'DEV' => '3',
			 * 'ERROR' => '4',
			 * 'INFO' => '5', 
			 * 'DEBUG' => '6', 
			 * 'ALL' => '7'
			 * 
			 * @param string $message
			 * @param string $errorLevel
			 * @return string
			 */
			public function app(string $message)
			{
				log_message('app', $message);

				return $message;
			}

			public function user(string $message)
			{
				log_message('user', $message);

				return $message;
			}

			public function dev(string $message)
			{
				log_message('dev', $message);

				return $message;
			}

			public function error(string $message)
			{
				log_message('error', $message);

				return $message;
			}

			public function log(string $message, string $errorLevel)
			{
				log_message($errorLevel, $message);

				return $errorLevel . ' | ' . $message;
			}
		};

		return $log;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('set_status_header')) {
	/**
	 * Set HTTP Status Header
	 *
	 * @param	int	the status code
	 * @param	string
	 * @return	void
	 */
	function set_status_header($code = 200, $text = '')
	{
		if (is_cli()) {
			return;
		}

		if (empty($code) or ! is_numeric($code)) {
			show_error('Status codes must be numeric', 500);
		}

		if (empty($text)) {
			is_int($code) or $code = (int) $code;
			$stati = [
				100	=> 'Continue',
				101	=> 'Switching Protocols',

				200	=> 'OK',
				201	=> 'Created',
				202	=> 'Accepted',
				203	=> 'Non-Authoritative Information',
				204	=> 'No Content',
				205	=> 'Reset Content',
				206	=> 'Partial Content',

				300	=> 'Multiple Choices',
				301	=> 'Moved Permanently',
				302	=> 'Found',
				303	=> 'See Other',
				304	=> 'Not Modified',
				305	=> 'Use Proxy',
				307	=> 'Temporary Redirect',

				400	=> 'Bad Request',
				401	=> 'Unauthorized',
				402	=> 'Payment Required',
				403	=> 'Forbidden',
				404	=> 'Not Found',
				405	=> 'Method Not Allowed',
				406	=> 'Not Acceptable',
				407	=> 'Proxy Authentication Required',
				408	=> 'Request Timeout',
				409	=> 'Conflict',
				410	=> 'Gone',
				411	=> 'Length Required',
				412	=> 'Precondition Failed',
				413	=> 'Request Entity Too Large',
				414	=> 'Request-URI Too Long',
				415	=> 'Unsupported Media Type',
				416	=> 'Requested Range Not Satisfiable',
				417	=> 'Expectation Failed',
				422	=> 'Unprocessable Entity',
				426	=> 'Upgrade Required',
				428	=> 'Precondition Required',
				429	=> 'Too Many Requests',
				431	=> 'Request Header Fields Too Large',

				500	=> 'Internal Server Error',
				501	=> 'Not Implemented',
				502	=> 'Bad Gateway',
				503	=> 'Service Unavailable',
				504	=> 'Gateway Timeout',
				505	=> 'HTTP Version Not Supported',
				511	=> 'Network Authentication Required',
			];

			if (isset($stati[$code])) {
				$text = $stati[$code];
			} else {
				show_error('No status text available. Please check your status code number or supply your own message text.', 500);
			}
		}

		if (strpos(PHP_SAPI, 'cgi') === 0) {
			header('Status: ' . $code . ' ' . $text, true);
			return;
		}

		$server_protocol = (isset($_SERVER['SERVER_PROTOCOL']) && in_array($_SERVER['SERVER_PROTOCOL'], ['HTTP/1.0', 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0'], true))
			? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
		header($server_protocol . ' ' . $code . ' ' . $text, true, $code);
	}
}

// --------------------------------------------------------------------

if (! function_exists('_error_handler')) {
	/**
	 * Error Handler
	 *
	 * This is the custom error handler that is declared at the (relative)
	 * top of CodeIgniter.php. The main reason we use this is to permit
	 * PHP errors to be logged in our own log files since the user may
	 * not have access to server logs. Since this function effectively
	 * intercepts PHP errors, however, we also need to display errors
	 * based on the current error_reporting level.
	 * We do that with the use of a PHP error template.
	 *
	 * @param	int	$severity
	 * @param	string	$message
	 * @param	string	$filepath
	 * @param	int	$line
	 * @return	void
	 */
	function _error_handler($severity, $message, $filepath, $line)
	{
		$is_error = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity);

		// Detect request context
		$context = detect_request_context(); // 'cli', 'api', or 'web'

		// When an error occurred, set the status header to '500 Internal Server Error'
		// to indicate to the client something went wrong.
		// This can't be done within the $_error->show_php_error method because
		// it is only called when the display_errors flag is set (which isn't usually
		// the case in a production environment) or when errors are ignored because
		// they are above the error_reporting threshold.
		if ($is_error && $context !== 'cli') {
			set_status_header(500);
		}

		// Should we ignore the error? We'll get the current error_reporting
		// level and add its bits with the severity bits to find out.
		if (($severity & error_reporting()) !== $severity) {
			return;
		}

		$_error = load_class('Exceptions', 'core');
		$_error->log_exception($severity, $message, $filepath, $line);

		// Should we display the error?
		if (str_ireplace(array('off', 'none', 'no', 'false', 'null'), '', ini_get('display_errors'))) {
			$_error->show_php_error($severity, $message, $filepath, $line);
		}

		// Handle display based on context
		if (should_display_errors()) {
			switch ($context) {
				case 'web':
					// Use ErrorHandler class instead of CI templates
					$errorHandler = get_error_handler_instance();
					$errorHandler->handleError($severity, $message, $filepath, $line, []);
					return; // ErrorHandler handles exit

				case 'api':
					display_api_error($severity, $message, $filepath, $line);
					break;

				case 'cli':
					display_cli_error($severity, $message, $filepath, $line);
					break;
			}
		}

		// If the error is fatal, the execution of the script should be stopped because
		// errors can't be recovered from. Halting the script conforms with PHP's
		// default error handling. See http://www.php.net/manual/en/errorfunc.constants.php
		if ($is_error) {
			exit(1); // EXIT_ERROR
		}
	}
}

// ------------------------------------------------------------------------

if (! function_exists('_exception_handler')) {
	/**
	 * Exception Handler
	 *
	 * Sends uncaught exceptions to the logger and displays them
	 * only if display_errors is On so that they don't show up in
	 * production environments.
	 *
	 * @param	Exception	$exception
	 * @return	void
	 */
	function _exception_handler($exception)
	{
		$context = detect_request_context();

		$_error = load_class('Exceptions', 'core');
		$_error->log_exception(E_ERROR, 'Exception: ' . $exception->getMessage(), $exception->getFile(), $exception->getLine());

		// is_cli() OR set_status_header(500);

		if ($context !== 'cli') {
			set_status_header(500);
		}

		if (should_display_errors()) {
			switch ($context) {
				case 'web':
					$errorHandler = get_error_handler_instance();
					$errorHandler->handleException($exception);
					return;

				case 'api':
					display_api_exception($exception);
					break;

				case 'cli':
					display_cli_exception($exception);
					break;
			}
		}

		// Should we display the error?
		if (str_ireplace(array('off', 'none', 'no', 'false', 'null'), '', ini_get('display_errors'))) {
			$_error->show_exception($exception);
		}

		exit(1); // EXIT_ERROR
	}
}

// ------------------------------------------------------------------------

if (! function_exists('_shutdown_handler')) {
	/**
	 * Shutdown Handler
	 *
	 * This is the shutdown handler that is declared at the top
	 * of CodeIgniter.php. The main reason we use this is to simulate
	 * a complete custom exception handler.
	 *
	 * E_STRICT is purposively neglected because such events may have
	 * been caught. Duplication or none? None is preferred for now.
	 *
	 * @link	http://insomanic.me.uk/post/229851073/php-trick-catching-fatal-errors-e-error-with-a
	 * @return	void
	 */
	function _shutdown_handler()
	{
		$last_error = error_get_last();
		if (
			isset($last_error) &&
			($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING))
		) {
			_error_handler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
		}
	}
}

// --------------------------------------------------------------------

if (! function_exists('detect_request_context')) {
	function detect_request_context()
	{
		// CLI detection
		if (php_sapi_name() === 'cli' || defined('STDIN')) {
			return 'cli';
		}

		// $input = load_class('Input', 'core');

		// API detection
		if (is_api_request() /*|| has_json_accept_header() || is_api_route()*/) {
			return 'api';
		}

		return 'web';
	}
}

// --------------------------------------------------------------------

if (! function_exists('get_error_handler_instance')) {
	function get_error_handler_instance($config = [])
	{
		static $handler = null;

		if ($handler === null) {

			require_once(__DIR__ . '/ErrorHandler.php');

			$handler = new \CI_ErrorHandler($config ?? [
				'environment' => ENVIRONMENT,
				'debug' => (ENVIRONMENT !== 'production'),
				'enable_ajax_errors' => true
			]);
		}

		return $handler;
	}
}

/**
 * Enhanced Error Handling Functions for CodeIgniter 3
 * 
 * This file contains improved error handling functions that integrate
 * the ErrorHandler.php class and provide context-aware error responses
 * for CLI, API, and web requests.
 */

// ------------------------------------------------------------------------

if (! function_exists('detect_request_context')) {
	/**
	 * Detect Request Context (CLI/API/Web)
	 * 
	 * Determines the type of request being handled to format errors appropriately
	 * 
	 * @return string 'cli', 'api', or 'web'
	 */
	function detect_request_context()
	{
		// CLI context
		if (php_sapi_name() === 'cli' || (defined('STDIN') && is_resource(STDIN))) {
			return 'cli';
		}

		// API context detection
		if (is_api_request()) {
			return 'api';
		}

		// Default to web context
		return 'web';
	}
}

// ------------------------------------------------------------------------

if (! function_exists('is_api_request')) {
	/**
	 * Check if current request is an API request
	 * 
	 * @return bool
	 */
	function is_api_request()
	{
		// Check for AJAX requests
		if (
			!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
			strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
		) {
			return true;
		}

		// Check Accept header for JSON
		if (isset($_SERVER['HTTP_ACCEPT'])) {

			$accept = strtolower($_SERVER['HTTP_ACCEPT']);
			if (
				strpos($accept, 'application/json') !== false ||
				// strpos($accept, 'application/xml') !== false ||
				strpos($accept, 'text/json') !== false
			) {
				return true;
			}
		}

		// Check Accept header for XML and Content-Type is XML
		if (isset($_SERVER['HTTP_ACCEPT']) && isset($_SERVER['CONTENT_TYPE'])) {

			$accept = strtolower($_SERVER['HTTP_ACCEPT']);
			$contentType = strtolower($_SERVER['CONTENT_TYPE']);

			if (
				strpos($accept, 'application/xml') !== false &&
				strpos($contentType, 'application/xml') !== false
			) {
				return true;
			}
		}

		// Check Content-Type header for API requests
		if (isset($_SERVER['CONTENT_TYPE'])) {
			$contentType = strtolower($_SERVER['CONTENT_TYPE']);
			if (
				strpos($contentType, 'application/json') !== false ||
				strpos($contentType, 'application/xml') !== false
			) {
				return true;
			}
		}

		// Check for common API route patterns
		if (isset($_SERVER['REQUEST_URI'])) {
			$uri = strtolower($_SERVER['REQUEST_URI']);
			if (
				strpos($uri, '/api/') !== false ||
				strpos($uri, '/rest/') !== false ||
				preg_match('/\.(json|xml)(\?|$)/', $uri)
			) {
				return true;
			}
		}

		return false;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('get_enhanced_error_handler')) {
	/**
	 * Get or create enhanced error handler instance
	 * 
	 * @return \CI_ErrorHandler
	 */
	function get_enhanced_error_handler()
	{
		static $errorHandler = null;

		if ($errorHandler === null) {
			// Load the ErrorHandler class if not already loaded
			if (!class_exists('ErrorHandler')) {
				require_once(__DIR__ . 'ErrorHandler.php');
			}

			// Create enhanced configuration
			$config = [
				'environment' => ENVIRONMENT,
				'debug' => (ENVIRONMENT !== 'production'),
				'enable_ajax_errors' => true,
				'log_errors' => true,
				'show_request_data' => (ENVIRONMENT === 'development'),
				'dark_theme' => true
			];

			$errorHandler = new \CI_ErrorHandler($config);
		}

		return $errorHandler;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('enhanced_error_handler')) {
	/**
	 * Enhanced Error Handler
	 * 
	 * Improved version of _error_handler that uses ErrorHandler class
	 * and provides context-aware error responses
	 * 
	 * @param int    $severity
	 * @param string $message
	 * @param string $filepath
	 * @param int    $line
	 * @return void
	 */
	function enhanced_error_handler($severity, $message, $filepath, $line)
	{
		$is_error = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity);
		$context = detect_request_context();

		// Set status header for web/api requests
		if ($is_error && $context !== 'cli') {
			set_status_header(500);
		}

		// Check if we should ignore the error
		if (($severity & error_reporting()) !== $severity) {
			return;
		}

		// Log the error using CI's logging system
		$_error = load_class('Exceptions', 'core');
		$_error->log_exception($severity, $message, $filepath, $line);

		// Handle error display based on context
		if (should_display_errors()) {
			switch ($context) {
				case 'cli':
					display_cli_error($severity, $message, $filepath, $line);
					break;

				case 'api':
					display_api_error($severity, $message, $filepath, $line);
					break;

				case 'web':
				default:
					// Use ErrorHandler class for web errors
					$errorHandler = get_enhanced_error_handler();
					$errorHandler->handleError($severity, $message, $filepath, $line, []);
					return; // ErrorHandler handles exit
			}
		}

		// Exit for fatal errors
		if ($is_error) {
			exit(1);
		}
	}
}

// ------------------------------------------------------------------------

if (! function_exists('enhanced_exception_handler')) {
	/**
	 * Enhanced Exception Handler
	 * 
	 * Improved version of _exception_handler that uses ErrorHandler class
	 * and provides context-aware exception responses
	 * 
	 * @param Exception $exception
	 * @return void
	 */
	function enhanced_exception_handler($exception)
	{
		$context = detect_request_context();

		// Log the exception
		$_error = load_class('Exceptions', 'core');
		$_error->log_exception(E_ERROR, 'Exception: ' . $exception->getMessage(), $exception->getFile(), $exception->getLine());

		// Set status header for web/api requests
		if ($context !== 'cli') {
			set_status_header(500);
		}

		// Handle exception display based on context
		if (should_display_errors()) {
			switch ($context) {
				case 'cli':
					display_cli_exception($exception);
					break;

				case 'api':
					display_api_exception($exception);
					break;

				case 'web':
				default:
					// Use ErrorHandler class for web exceptions
					$errorHandler = get_enhanced_error_handler();
					$errorHandler->handleException($exception);
					return; // ErrorHandler handles exit
			}
		}

		exit(1);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('enhanced_show_error')) {
	/**
	 * Enhanced Show Error Function
	 * 
	 * Context-aware version of show_error that uses ErrorHandler class
	 * for web requests and appropriate formatting for CLI/API
	 * 
	 * @param string $message
	 * @param int    $status_code
	 * @param string $heading
	 * @return void
	 */
	function enhanced_show_error($message, $status_code = 500, $heading = 'An Error Was Encountered')
	{
		$context = detect_request_context();
		$status_code = abs($status_code);

		if ($status_code < 100) {
			$exit_status = $status_code + 9;
			$status_code = 500;
		} else {
			$exit_status = 1;
		}

		switch ($context) {
			case 'cli':
				display_cli_show_error($heading, $message, $status_code);
				break;

			case 'api':
				display_api_show_error($heading, $message, $status_code);
				break;

			case 'web':
			default:
				// Create a mock exception to use with ErrorHandler
				$exception = new Exception($message, $status_code);
				$errorHandler = get_enhanced_error_handler();
				$errorHandler->handleException($exception);
				return; // ErrorHandler handles exit
		}

		exit($exit_status);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('should_display_errors')) {
	/**
	 * Check if errors should be displayed
	 * 
	 * @return bool
	 */
	function should_display_errors()
	{
		return str_ireplace(array('off', 'none', 'no', 'false', 'null'), '', ini_get('display_errors')) ||
			ENVIRONMENT === 'development';
	}
}

// ------------------------------------------------------------------------

if (! function_exists('display_cli_error')) {
	/**
	 * Display error for CLI context
	 * 
	 * @param int    $severity
	 * @param string $message
	 * @param string $filepath
	 * @param int    $line
	 * @return void
	 */
	function display_cli_error($severity, $message, $filepath, $line)
	{
		$errorTypes = [
			E_ERROR => 'Fatal Error',
			E_WARNING => 'Warning',
			E_PARSE => 'Parse Error',
			E_NOTICE => 'Notice',
			E_CORE_ERROR => 'Core Error',
			E_CORE_WARNING => 'Core Warning',
			E_COMPILE_ERROR => 'Compile Error',
			E_COMPILE_WARNING => 'Compile Warning',
			E_USER_ERROR => 'User Error',
			E_USER_WARNING => 'User Warning',
			E_USER_NOTICE => 'User Notice',
			E_RECOVERABLE_ERROR => 'Recoverable Error',
			E_DEPRECATED => 'Deprecated',
			E_USER_DEPRECATED => 'User Deprecated'
		];

		$errorType = isset($errorTypes[$severity]) ? $errorTypes[$severity] : 'Unknown Error';

		$output = "\n" . str_repeat('=', 60) . "\n";
		$output .= "PHP " . $errorType . "\n";
		$output .= str_repeat('=', 60) . "\n";
		$output .= "Message: " . $message . "\n";
		$output .= "File: " . $filepath . "\n";
		$output .= "Line: " . $line . "\n";
		$output .= "Time: " . date('Y-m-d H:i:s') . "\n";
		$output .= str_repeat('=', 60) . "\n\n";

		// Add stack trace if available
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		if (!empty($trace)) {
			$output .= "Stack Trace:\n";
			$output .= str_repeat('-', 40) . "\n";
			foreach (array_slice($trace, 1, 10) as $i => $frame) {
				$file = isset($frame['file']) ? $frame['file'] : 'unknown';
				$line = isset($frame['line']) ? $frame['line'] : 0;
				$function = isset($frame['function']) ? $frame['function'] : 'unknown';
				$class = isset($frame['class']) ? $frame['class'] . '::' : '';

				$output .= sprintf("#%d %s%s() called at %s:%d\n", $i, $class, $function, $file, $line);
			}
			$output .= str_repeat('-', 40) . "\n\n";
		}

		fwrite(STDERR, $output);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('display_api_error')) {
	/**
	 * Display error for API context
	 * 
	 * @param int    $severity
	 * @param string $message
	 * @param string $filepath
	 * @param int    $line
	 * @return void
	 */
	function display_api_error($severity, $message, $filepath, $line)
	{
		$errorTypes = [
			E_ERROR => 'fatal_error',
			E_WARNING => 'warning',
			E_PARSE => 'parse_error',
			E_NOTICE => 'notice',
			E_CORE_ERROR => 'core_error',
			E_CORE_WARNING => 'core_warning',
			E_COMPILE_ERROR => 'compile_error',
			E_COMPILE_WARNING => 'compile_warning',
			E_USER_ERROR => 'user_error',
			E_USER_WARNING => 'user_warning',
			E_USER_NOTICE => 'user_notice',
			E_RECOVERABLE_ERROR => 'recoverable_error',
			E_DEPRECATED => 'deprecated',
			E_USER_DEPRECATED => 'user_deprecated'
		];

		$errorType = isset($errorTypes[$severity]) ? $errorTypes[$severity] : 'unknown_error';

		$response = [
			'error' => true,
			'type' => $errorType,
			'message' => $message,
			'timestamp' => date('c'),
			'request_id' => uniqid()
		];

		// Add debug information in development
		if (ENVIRONMENT === 'development') {
			$response['debug'] = [
				'file' => $filepath,
				'line' => $line,
				'severity' => $severity,
				'trace' => array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1, 5)
			];
		}

		// Clear any previous output
		while (ob_get_level()) {
			ob_end_clean();
		}

		header('Content-Type: application/json');
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('display_cli_exception')) {
	/**
	 * Display exception for CLI context
	 * 
	 * @param Exception $exception
	 * @return void
	 */
	function display_cli_exception($exception)
	{
		$output = "\n" . str_repeat('=', 60) . "\n";
		$output .= "UNCAUGHT EXCEPTION\n";
		$output .= str_repeat('=', 60) . "\n";
		$output .= "Type: " . get_class($exception) . "\n";
		$output .= "Message: " . $exception->getMessage() . "\n";
		$output .= "File: " . $exception->getFile() . "\n";
		$output .= "Line: " . $exception->getLine() . "\n";
		$output .= "Code: " . $exception->getCode() . "\n";
		$output .= "Time: " . date('Y-m-d H:i:s') . "\n";
		$output .= str_repeat('=', 60) . "\n\n";

		// Add stack trace
		$output .= "Stack Trace:\n";
		$output .= str_repeat('-', 40) . "\n";
		$output .= $exception->getTraceAsString() . "\n";
		$output .= str_repeat('-', 40) . "\n\n";

		fwrite(STDERR, $output);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('display_api_exception')) {
	/**
	 * Display exception for API context
	 * 
	 * @param Exception $exception
	 * @return void
	 */
	function display_api_exception($exception)
	{
		$response = [
			'error' => true,
			'type' => 'exception',
			'exception_class' => get_class($exception),
			'message' => $exception->getMessage(),
			'code' => $exception->getCode(),
			'timestamp' => date('c'),
			'request_id' => uniqid()
		];

		// Add debug information in development
		if (ENVIRONMENT === 'development') {
			$response['debug'] = [
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'trace' => array_slice($exception->getTrace(), 0, 10)
			];
		}

		// Clear any previous output
		while (ob_get_level()) {
			ob_end_clean();
		}

		header('Content-Type: application/json');
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('display_cli_show_error')) {
	/**
	 * Display show_error for CLI context
	 * 
	 * @param string $heading
	 * @param string $message
	 * @param int    $status_code
	 * @return void
	 */
	function display_cli_show_error($heading, $message, $status_code)
	{
		$output = "\n" . str_repeat('=', 60) . "\n";
		$output .= strtoupper($heading) . " (HTTP {$status_code})\n";
		$output .= str_repeat('=', 60) . "\n";
		$output .= $message . "\n";
		$output .= "Time: " . date('Y-m-d H:i:s') . "\n";
		$output .= str_repeat('=', 60) . "\n\n";

		fwrite(STDERR, $output);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('display_api_show_error')) {
	/**
	 * Display show_error for API context
	 * 
	 * @param string $heading
	 * @param string $message
	 * @param int    $status_code
	 * @return void
	 */
	function display_api_show_error($heading, $message, $status_code)
	{
		$response = [
			'error' => true,
			'type' => 'application_error',
			'heading' => $heading,
			'message' => $message,
			'status_code' => $status_code,
			'timestamp' => date('c'),
			'request_id' => uniqid()
		];

		// Clear any previous output
		while (ob_get_level()) {
			ob_end_clean();
		}

		http_response_code($status_code);
		header('Content-Type: application/json');
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
}

// --------------------------------------------------------------------

if (! function_exists('remove_invisible_characters')) {
	/**
	 * Remove Invisible Characters
	 *
	 * This prevents sandwiching null characters
	 * between ascii characters, like Java\0script.
	 *
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function remove_invisible_characters($str = '', $url_encoded = true)
	{
		$non_displayables = [];

		// every control character except newline (dec 10),
		// carriage return (dec 13) and horizontal tab (dec 09)
		if ($url_encoded) {
			$non_displayables[] = '/%0[0-8bcef]/i';	// url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/i';	// url encoded 16-31
			$non_displayables[] = '/%7f/i';	// url encoded 127
		}

		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

		do {
			if ($str == null) {
				$str = '';
			}

			$str = preg_replace($non_displayables, '', (string) $str, -1, $count);
		} while ($count);

		return $str;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('html_escape')) {
	/**
	 * Returns HTML escaped variable.
	 *
	 * @param	mixed	$var		The input string or array of strings to be escaped.
	 * @param	bool	$double_encode	$double_encode set to false prevents escaping twice.
	 * @return	mixed			The escaped string or array of strings as a result.
	 */
	function html_escape($var, $double_encode = true)
	{
		if (empty($var)) {
			return $var;
		}

		if (is_array($var)) {
			foreach (array_keys($var) as $key) {
				$var[$key] = html_escape($var[$key], $double_encode);
			}

			return $var;
		}

		return htmlspecialchars($var, ENT_QUOTES, config_item('charset'), $double_encode);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('esc')) {
	/**
	 * Returns HTML escaped variable.
	 * Or escaped javascript literal
	 * 
	 * Alias to html_escape() function
	 *
	 * @param	mixed	$str The input string or array of strings to be escaped.
	 * @param	bool	$double_encode	$double_encode set to false prevents escaping twice.
	 * @return	mixed	The escaped string or array of strings as a result.
	 */
	function esc($str, $escape_js = false, $double_encode = true)
	{
		if (!empty($escape_js)) {
			// Escape for JavaScript string literals.
			// Useful for confirm() or alert() - but of course not document.write() or  similar
			return addcslashes($str, "\"'\\\0..\037\/");
		}

		return html_escape($str, $double_encode);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('_evaluated')) {
	/**
	 * Verify if eval()'d code is contained in a string
	 * This feels unhealthy since eval() is seen as evil
	 * 
	 * It is used to render view files that are handled
	 * by the Plates template engine
	 * 
	 * @param string $string
	 * @param string $evalError
	 * @return bool
	 */
	function _evaluated($string, $evalError = "eval()'d code")
	{
		if (strpos($string, $evalError) !== false) {
			return true;
		}

		return false;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('request')) {
	/**
	 * CI Input class function
	 *
	 * @return CI_Input
	 */
	function request()
	{
		return get_instance()->input;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('response')) {
	/**
	 * CI Output class function
	 *
	 * @return CI_Output
	 */
	function response()
	{
		return get_instance()->output;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('_stringify_attributes')) {
	/**
	 * Stringify attributes for use in HTML tags.
	 *
	 * Helper function used to convert a string, array, or object
	 * of attributes to a string.
	 *
	 * @param	mixed	string, array, object
	 * @param	bool
	 * @return	string
	 */
	function _stringify_attributes($attributes, $js = false)
	{
		$atts = null;

		if (empty($attributes)) {
			return $atts;
		}

		if (is_string($attributes)) {
			return ' ' . $attributes;
		}

		$attributes = (array) $attributes;

		foreach ($attributes as $key => $val) {
			$atts .= ($js) ? $key . '=' . $val . ',' : ' ' . $key . '="' . $val . '"';
		}

		return rtrim($atts, ',');
	}
}

// ------------------------------------------------------------------------

if (! function_exists('function_usable')) {
	/**
	 * Function usable
	 *
	 * Executes a function_exists() check, and if the Suhosin PHP
	 * extension is loaded - checks whether the function that is
	 * checked might be disabled in there as well.
	 *
	 * This is useful as function_exists() will return false for
	 * functions disabled via the *disable_functions* php.ini
	 * setting, but not for *suhosin.executor.func.blacklist* and
	 * *suhosin.executor.disable_eval*. These settings will just
	 * terminate script execution if a disabled function is executed.
	 *
	 * The above described behavior turned out to be a bug in Suhosin,
	 * but even though a fix was committed for 0.9.34 on 2012-02-12,
	 * that version is yet to be released. This function will therefore
	 * be just temporary, but would probably be kept for a few years.
	 *
	 * @link	http://www.hardened-php.net/suhosin/
	 * @param	string	$function_name	Function to check for
	 * @return	bool	true if the function exists and is safe to call,
	 *			false otherwise.
	 */
	function function_usable($function_name)
	{
		static $_suhosin_func_blacklist;

		if (function_exists($function_name)) {
			if (! isset($_suhosin_func_blacklist)) {
				$_suhosin_func_blacklist = extension_loaded('suhosin')
					? explode(',', trim(ini_get('suhosin.executor.func.blacklist')))
					: [];
			}

			return ! in_array($function_name, $_suhosin_func_blacklist, true);
		}

		return false;
	}
}

// -------------------------------------- Diety Functions ---------------------------

// Global container instance
$GLOBALS['service_container'] = null;

if (! function_exists('container')) {
	/**
	 * Get the global service container instance
	 * 
	 * @return CI_ServiceContainer
	 */
	function container(): CI_ServiceContainer
	{
		if (! isset($GLOBALS['service_container'])) {
			// Load the ServiceContainer class if not already loaded
			if (! class_exists('CI_ServiceContainer', FALSE)) {
				require_once BASEPATH . 'core/ServiceContainer.php';
			}
			$GLOBALS['service_container'] = new CI_ServiceContainer();
		}

		return $GLOBALS['service_container'];
	}
}

// ------------------------------------------------------------------------

if (! function_exists('ci')) {
	/**
	 * CodeIgniter Instance function
	 * Powered with loading internal libraries
	 * in an expressive manner
	 * 
	 * @param string $class
	 * @param array $params
	 * @return object CodeIgniter Instance
	 */
	function ci(?string $class = null, array $params = [])
	{
		if ($class === null) {
			return get_instance();
		}

		if ($class === 'database') {
			get_instance()->use->database();
			return get_instance()->db;
		}

		//	Special cases 'user_agent' and 'unit_test' are loaded
		//	with diferent names
		if ($class !== 'user_agent') {
			$library = ($class == 'unit_test') ? 'unit' : $class;
		} else {
			$library = 'agent';
		}

		$ci = ci();

		//	Library not loaded
		if (! isset($ci->$library)) {

			//	Special case 'cache' is a driver
			if ($class == 'cache') {
				$ci->load->driver($class, $params);
			}

			// Let's guess it's a library
			$ci->load->library($class, $params);
		}

		//	Special name for 'unit_test' is 'unit'
		if ($class == 'unit_test') {
			return $ci->unit;
		}
		//	Special name for 'user_agent' is 'agent'
		elseif ($class == 'user_agent') {
			return $ci->agent;
		}

		if (! ends_with($class, '_model') || !ends_with($class, '_m')) {
			return $ci->$class;
		} else {
			$class = ($params == []) ? $class : $params;
			return $ci->$class;
		}
	}
}

// ------------------------------------------------------------------------

if (! function_exists('app')) {
	/**
	 * Updated app helper function
	 * 
	 * @param string|null $abstract
	 * @param array $parameters
	 * @return mixed
	 */
	function app(?string $abstract = null, array $parameters = []): mixed
	{
		// If no parameters, return CI instance for backward compatibility
		if ($abstract === null) {
			return get_instance();
		}

		// Special case: return container instance
		if ($abstract === 'container' || $abstract === 'service_container') {
			return container();
		}

		$container = container();
		$ci = null;

		try {
			return $container->get($abstract, $parameters);
		} catch (Exception $e) {
			$ci = ci();
		}

		// Handle special CI cases first (for performance)
		switch ($abstract) {
			case 'database':
				if (!isset($ci->db)) {
					$ci->load->database();
				}
				return $ci->db;

			case 'load':
				return $ci->load;

			case 'input':
				return $ci->input;

			case 'output':
				return $ci->output;

			case 'config':
				return $ci->config;

			case 'session':
				return $ci->session;
		}

		// Fallback to CI instance properties
		if (isset($ci->{$abstract})) {
			return $ci->{$abstract};
		}

		$getClass = explode('/', has_dot($abstract));
		$classType = count($getClass);
		$className = ($classType == 2) ? $getClass[1] : $getClass[0];

		if (
			ends_with($abstract, '_model')
			|| ends_with($abstract, '_m')
			|| contains('Model', $abstract)
		) {

			use_model($abstract); // load model

			return ci()->{$className}; // return model object
		}

		if (contains('Action', $abstract)) {
			use_action($abstract); // load action
			return $ci->{$className}; // return action object
		}

		// Try direct class instantiation
		if (class_exists($abstract)) {
			return empty($parameters) ? new $abstract() : new $abstract($parameters);
		}

		// Try container resolution first
		if ($container->has($abstract)) {
			return $container->get($abstract, $parameters);
		}

		// let's assume it's a model without
		// the above conditions
		// If it does not exists we will load a library
		// Or discover it as a service
		// Not a good implementation but it works
		try {
			$ci->load->model(has_dot($abstract));
		} catch (Exception $e) {
			(!empty($ci->{$abstract}) && is_object($ci->{$abstract}))
				? $ci->{$abstract}
				: $ci->load->library(has_dot($abstract));
		}

		if (!is_object($ci->{$className})) {
			return ci(has_dot($abstract), $parameters);
		}

		if (isset($ci->{$className})) {
			return $ci->{$className};
		}

		// Final fallback - try container resolution (might throw exception)
		return $container->get($abstract, $parameters);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('service')) {
	/**
	 * Easy access to services
	 *
	 * @param string $class
	 * @param string $alias
	 * @param mixed $params
	 * @return object
	 */
	function service(string $class = '', string $alias = '', $params = [])
	{

		// Special case: return container instance
		if ($class === 'container' || $class === 'service_container') {
			return container();
		}

		$container = container();
		$ci = null;

		try {
			return $container->get($class, $params);
		} catch (Exception $e) {
			$ci = ci();
		}

		$getClass = explode('/', has_dot($class));
		$classType = count($getClass);
		$className = ($classType == 2) ? $getClass[1] : $getClass[0];

		if (contains('Service', $class)) {
			(!empty($alias))
				? use_service($class, $alias)
				: use_service($class);
			return (!empty($alias)) ? $ci->{$alias} : $ci->{$className};
		}

		$app_services = $ci->config->item('app_services');

		if (array_key_exists($class, $app_services)) {

			$class = isset($app_services[$class]) ? $app_services[$class] : [];

			return (is_object(new $class()))
				? (!empty($params) ?: (new $class($params))) : (new $class());
		}

		$webby_services = $ci->config->item('webby_services');

		if (array_key_exists($class, $webby_services)) {

			$class = isset($webby_services[$class]) ? $webby_services[$class] : [];

			$class = has_dot($class);

			use_service($class, $className);

			return $ci->{$className};
		}

		if (is_object($class) && !empty($alias)) {
			class_alias(get_class($class), $alias);
			return new $alias;
		}

		if (is_object(new $class()) && !empty($alias)) {
			$class = new $class($params);
			class_alias(get_class($class), $alias);
			return new $alias;
		}

		if (is_object(new $class())) {
			return new $class;
		}

		(!empty($alias)) ? use_service($className, $alias) : use_service($className);

		return (!empty($alias)) ? $ci->{$alias} : $ci->{$className};
	}
}

// ------------------------------------------------------------------------

if (! function_exists('env')) {
	/**
	 * Allows user to retrieve values from the environment
	 * variables that have been set. Especially useful for
	 * retrieving values set from the .env file for
	 * use in config files.
	 *
	 * @param string $key
	 * @param string   $default
	 *
	 * @return mixed
	 */
	function env(string $key, ?string $default = null, $set = false)
	{
		$value = getenv($key);

		if ($value === false) {
			$value = $_ENV[$key] ?? $_SERVER[$key] ?? false;
		}

		// Not found? Return the default value
		if ($value === false && $set === false) {
			return $default;
		}

		// Not found? Then set to $_ENV
		if ($value === false && $set === true) {
			$value = $_ENV[$key] = $default;
		}

		$env = new DotEnv(ROOTPATH);

		$value = $env->prepareVariable($value);

		// Handle any boolean values
		switch (strtolower($value)) {
			case 'true':
				return true;
			case 'false':
				return false;
			case 'empty':
				return '';
			case 'null':
				return null;
		}

		return $value;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('bind')) {
	function bind($abstract, $concrete)
	{
		return container()->bind($abstract, $concrete);
	}
}

if (! function_exists('singleton')) {
	function singleton($abstract, $concrete)
	{
		return container()->singleton($abstract, $concrete);
	}
}

if (! function_exists('class_basename')) {
	/**
	 *  Get the class 'basename' of the given object/class
	 *
	 *  @param     string|object    $class
	 *  @return    string
	 */
	function class_basename($class)
	{
		$class = is_object($class) ? get_class($class) : $class;

		return basename(str_replace('\\', '/', $class));
	}
}

// ------------------------------------------------------------------------

if (! function_exists('class_uses_recursive')) {
	/**
	 *  Return all traits used by a class, it's subclasses and trait of their traits
	 *
	 *  @param     string    $class
	 *  @return    array
	 */
	function class_uses_recursive($class)
	{
		$result = [];

		foreach (array_merge([$class => $class], class_parents($class)) as $class) {
			$result += trait_uses_recursive($class);
		}

		return array_unique($result);
	}
}

if (! function_exists('trait_uses_recursive')) {
	/**
	 *  Returns all traits used by a trait and its traits
	 *
	 *  @param     string    $trait
	 *  @return    array
	 */
	function trait_uses_recursive($trait)
	{
		$traits = class_uses($trait);

		foreach ($traits as $trait) {
			$traits += trait_uses_recursive($trait);
		}

		return $traits;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('retry')) {
	/**
	 *  Attempt to execute an operation a given number of times
	 *
	 *  @param     int         $attempts
	 *  @param     callable    $callback
	 *  @param     int     $sleep
	 *  @return    mixed
	 *
	 *  @throws    \Exception
	 */
	function retry($attempts, callable $callback, $sleep = 0)
	{
		$attempts--;	//	Decrement the number of attempts

		beginning:
		try {
			return $callback();
		} catch (Exception $e) {
			if (! $attempts) {
				throw $e;
			}

			$attempts--;	//	Decrement the number of attempts

			if ($sleep) {
				usleep($sleep * 1000);
			}

			goto beginning;
		}
	}
}

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
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Config Class
 *
 * This class contains functions that enable config files to be managed
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/userguide3/libraries/config.html
 */
class CI_Config {

	/**
	 * List of all loaded config values
	 *
	 * @var	array
	 */
	public $config = [];

	/**
	 * List of all loaded config files
	 *
	 * @var	array
	 */
	public $is_loaded =	[];

	/**
	 * List of paths to search when trying to load a config file.
	 *
	 * @used-by	CI_Loader
	 * @var		array
	 */
	public $_config_paths =	[COREPATH, ROOTPATH];

	// --------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * Sets the $config data from the primary config.php file as a class variable.
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->config =& get_config();

		// Set the base_url automatically if none was provided
		if (empty($this->config['base_url']))
		{
			if (isset($_SERVER['SERVER_ADDR']))
			{
				if (str_contains((string) $_SERVER['SERVER_ADDR'], ':'))
				{
					$server_addr = '['.$_SERVER['SERVER_ADDR'].']';
				}
				else
				{
					$server_addr = $_SERVER['SERVER_ADDR'];
				}

				$base_url = (is_https() ? 'https' : 'http').'://'.$server_addr
					.substr((string) $_SERVER['SCRIPT_NAME'], 0, strpos((string) $_SERVER['SCRIPT_NAME'], basename((string) $_SERVER['SCRIPT_FILENAME'])));
			}
			else
			{
				$base_url = 'http://localhost/';
			}

			$this->set_item('base_url', $base_url);
		}

		log_message('info', 'Config Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Load Config File
	 *
	 * @param	string	$file			Configuration file name
	 * @param	bool	$use_sections		Whether configuration values should be loaded into their own section
	 * @param	bool	$fail_gracefully	Whether to just return false or display an error message
	 * @return	bool	true if the file was loaded correctly or false on failure
	 */
	public function load(string $file = '', bool $use_sections = false, bool $fail_gracefully = false)
	{
		$file = ($file === '') ? 'config' : str_replace('.php', '', $file);
		$loaded = false;

		foreach ($this->_config_paths as $path)
		{
			foreach ([$file, ENVIRONMENT.DIRECTORY_SEPARATOR.$file] as $location)
			{
				$file_path = $path.'config/'.$location.'.php';
				if (in_array($file_path, $this->is_loaded, true))
				{
					return true;
				}

				if ( ! file_exists($file_path))
				{
					continue;
				}

				include($file_path);

				if ( ! isset($config) OR ! is_array($config))
				{
					if ($fail_gracefully === true)
					{
						return false;
					}

					show_error('Your '.$file_path.' file does not appear to contain a valid configuration array.');
				}

				if ($use_sections === true)
				{
					$this->config[$file] = isset($this->config[$file])
						? array_merge($this->config[$file], $config)
						: $config;
				}
				else
				{
					$this->config = array_merge($this->config, $config);
				}

				$this->is_loaded[] = $file_path;
				$config = null;
				$loaded = true;
				log_message('debug', 'Config file loaded: '.$file_path);
			}
		}

		if ($loaded === true)
		{
			return true;
		}
		elseif ($fail_gracefully === true)
		{
			return false;
		}

		show_error('The configuration file '.$file.'.php does not exist.');
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a config file item
	 *
	 * @param	string|array	$item	Config item name
	 * @param	string	$index	Index name
	 * @return	string|mixed	The configuration item or null if the item doesn't exist
	 */
	public function item(string|array $item, string $index = ''): mixed
	{
		if ($index == '')
		{
			return $this->config[$item] ?? null;
		}

		return isset($this->config[$index], $this->config[$index][$item]) ? $this->config[$index][$item] : null;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a config file item with slash appended (if not empty)
	 *
	 * @param	string		$item	Config item name
	 * @return	string|null	The configuration item or null if the item doesn't exist
	 */
	public function slash_item(string $item): ?string
	{
		if (! isset($this->config[$item])) {
			return null;
		} elseif (trim($this->config[$item]) === '') {
			return '';
		}

		return rtrim((string) $this->config[$item], '/').'/';
	}

	// --------------------------------------------------------------------

	/**
	 * Site URL
	*
	* Returns base_url . index_page [. uri_string]
	*
	* @uses	CI_Config::_uri_string()
	*
	* @param	string|array $uri	URI string or an array of segments
	* @param	string $protocol
	*/
	public function site_url(string|array $uri = '', $protocol = null): string
	{
		$base_url = $this->slash_item('base_url');

		if (isset($protocol))
		{
			// For protocol-relative links
			if ($protocol === '')
			{
				$base_url = substr($base_url, strpos($base_url, '//'));
			}
			else
			{
				$base_url = $protocol.substr($base_url, strpos($base_url, '://'));
			}
		}

		if (empty($uri))
		{
			return $base_url.$this->item('index_page');
		}

		$uri = $this->_uri_string($uri);

		if ($this->item('enable_query_strings') === false) {

			$suffix = $this->config['url_suffix'] ?? '';
		  
		  if ($suffix !== '')
		  {
			  if (($offset = strpos($uri, '?')) !== false)
			  {
				  $uri = substr($uri, 0, $offset).$suffix.substr($uri, $offset);
			  }
			  else
			  {
				  $uri .= $suffix;
			  }
		  }

		  return $base_url.$this->slash_item('index_page').$uri;
	  }

	  if (!str_contains($uri, '?')) {
		  $uri = '?'.$uri;
	  }

		return $base_url.$this->item('index_page').$uri;
	}

	// -------------------------------------------------------------

	/**
	* Base URL
	*
	* Returns base_url [. uri_string]
	*
	* @uses	CI_Config::_uri_string()
	*
	* @param	string|string[]	$uri	URI string or an array of segments
	* @param	string	$protocol
	*/
	public function base_url(string|array $uri = '', $protocol = null): string
	{
		$base_url = $this->slash_item('base_url');

		if (isset($protocol))
		{
			// For protocol-relative links
			if ($protocol === '')
			{
				$base_url = substr($base_url, strpos($base_url, '//'));
			}
			else
			{
				$base_url = $protocol.substr($base_url, strpos($base_url, '://'));
			}
		}

		return $base_url.$this->_uri_string($uri);
	}

	// -------------------------------------------------------------

	/**
	 * Build URI string
	*
	* @used-by	CI_Config::site_url()
	* @used-by	CI_Config::base_url()
	*
	* @param	string|string[]	$uri	URI string or an array of segments
	*/
	protected function _uri_string(string|array $uri): string
	{
		if ($this->item('enable_query_strings') === false) {
			is_array($uri) && $uri = implode('/', $uri);
			return ltrim($uri, '/');
		}

		if (is_array($uri)) {
			return http_build_query($uri);
		}

		return $uri;
	}

	// --------------------------------------------------------------------

	/**
	 * System URL
	*
	* @deprecated	3.0.0	Encourages insecure practices
	*/
	public function system_url(): string
	{
		$x = explode('/', preg_replace('|/*(.+?)/*$|', '\\1', BASEPATH));
		return $this->slash_item('base_url').end($x).'/';
	}

	// --------------------------------------------------------------------

	/**
	 * Set a config file item
	*
	* @param	string	$item	Config item key
	* @param	string	$value	Config item value
	*/
	public function set_item(string $item, string $value): void
	{
		$this->config[$item] = $value;
	}

}

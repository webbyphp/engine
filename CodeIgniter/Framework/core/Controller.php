<?php

declare(strict_types=1);

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

/**
 * Application Controller Class
 *
 * This class object is the super class that every library in
 * CodeIgniter will be assigned to.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/userguide3/general/controllers.html
 */

/**
 * 
 * @property CI_Loader    $load      Loader library
 * @property CI_Input     $input     Input library  
 * @property CI_Output    $output    Output library
 * @property CI_Config    $config    Configuration library
 * @property CI_URI       $uri       URI library
 * @property CI_Router    $router    Router library
 * @property CI_Security  $security  Security library
 * @property CI_Lang      $lang      Language library
 * @property CI_Zip       $zip       Zip library
 * @property CI_Benchmark $benchmark Benchmark library
 * @property CI_Session   $session   Session library (if loaded)
 * @property CI_DB_query_builder $db Database connection (if loaded)
 * @property CI_Form_validation $form_validation Form validation library (if loaded)
 * 
 */

class CI_Controller
{

	/**
	 * Dynamic variables container
	 *
	 * @var array<string,mixed>
	 */
	protected array $container = [];

	/**
	 * Reference to the CI singleton
	 * super-object instance reference 
	 * (legacy get_instance()).
	 *
	 * @var CI_Controller|null
	 */
	private static ?CI_Controller $instance = null;

	/**
	 * CI_Loader
	 *
	 * @var	CI_Loader
	 */
	public $load;

	/**
	 * CI_Loader
	 *
	 * @var	CI_Loader
	 */
	public $use;

	/**
	 * CI_Input
	 *
	 * @var	CI_Input
	 */
	public $input;

	/**
	 * CI_Input
	 *
	 * @var	CI_Input
	 */
	public $request;

	/**
	 * CI_Output
	 *
	 * @var	CI_Output
	 */
	public $output;

	/**
	 * CI_Output
	 *
	 * @var	CI_Output
	 */
	public $response;

	/**
	 * Class constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		self::$instance = &$this;

		// Assign all the class objects that were instantiated by the
		// bootstrap file (CodeIgniter.php) to local class variables
		// so that CI can run as one big super object.
		foreach (is_loaded() as $var => $class) {
			$this->$var = load_class($class);

			// place into container so __get will return it
			$this->container[$var] = $this->$var;
		}

		$this->load = load_class('Loader', 'core');
		$this->use = &$this->load;
		$this->request = load_class('Input', 'core');
		$this->response = load_class('Output', 'core');
		$this->load->initialize();
		log_message('info', 'Controller Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Get the CI singleton
	 *
	 * @static
	 * @return	object
	 */
	public static function get_instance()
	{
		return self::$instance;
	}

	/**
	 * Get CI or Current Controller _output() method
	 *
	 * @return	void
	 */
	// public function _output($output) {}

	/**
	 * Magic setter for dynamic properties 
	 * -> store into container.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set(string $name, mixed $value): void
	{
		$this->container[$name] = $value;
	}

	/**
	 * Magic getter for properties 
	 * -> read from container.
	 *
	 * @param string $name
	 * @return mixed|null
	 */
	public function __get(string $name)
	{
		// If the property exists in the container, return it
		if (array_key_exists($name, $this->container)) {
			return $this->container[$name];
		}

		// As a fallback, let the loader try to lazy-load the item (same behavior as CI)
		if (isset($this->load) && method_exists($this->load, $name)) {
			return $this->load->$name;
		}

		return null;
	}

	/**
	 * Optionally expose isset behavior.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset(string $name): bool
	{
		return array_key_exists($name, $this->container);
	}
}

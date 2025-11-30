<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Base\HMVC;

use CI_Instance;

/**
 * Modular Extensions - HMVC
 * 
 */

#[\AllowDynamicProperties]
class ModuleController
{
	public $autoload = [];
	public $load;
	public $use;

	public function __construct()
	{
		$ci = CI_Instance::create();

		$class = str_replace($ci->config->item('controller_suffix'), '', get_class($this));
		log_message('debug', "{$class} ModuleController Initialized");
		Modules::$registry[$class] = $this;

		/* copy a loader instance and initialize */
		$this->load = clone load_class('Loader');
		$this->use = &$this->load;
		$this->load->initialize($this);

		/* autoload module items */
		$this->load->_autoloader($this->autoload);
	}

	public function __get($class)
	{
		$ci = CI_Instance::create();
		return $ci->$class;
	}
}

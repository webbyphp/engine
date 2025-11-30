<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

(defined('COREPATH')) or exit('No direct script access allowed');

use Base\HMVC\Modules;

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Modular Extensions - HMVC
 *
 */

class Base_Config extends \CI_Config
{
	public function load($file = '', $use_sections = false, $fail_gracefully = false, $_module = '')
	{
		if (in_array($file, $this->is_loaded, true)) return $this->item($file);

		$_module or $_module = ci()->router->fetch_module();
		list($path, $file) = Modules::find($file, $_module, 'Config/');

		if ($path === false) {
			parent::load($file, $use_sections, $fail_gracefully);
			return $this->item($file);
		}

		if ($config = Modules::load_file($file, $path, 'config')) {
			/* reference to the config array */
			$current_config = &$this->config;

			if ($use_sections === true) {
				if (isset($current_config[$file])) {
					$current_config[$file] = array_merge($current_config[$file], $config);
				} else {
					$current_config[$file] = $config;
				}
			} else {
				$current_config = array_merge($current_config, $config);
			}

			$this->is_loaded[] = $file;

			unset($config);
			return $this->item($file);
		}
	}
}

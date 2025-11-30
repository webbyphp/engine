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
 * Modular Extensions - HMVC
 *
 **/
class Base_Lang extends \CI_Lang
{
	public function load($langfile, $lang = '', $return = false, $add_suffix = true, $alt_path = '', $_module = '')
	{
		if (is_array($langfile)) {
			foreach ($langfile as $_lang) $this->load($_lang);
			return $this->language;
		}

		$deft_lang = ci()->config->item('language');
		$idiom = ($lang == '') ? $deft_lang : $lang;

		if (in_array($langfile . '_lang' . PHPEXT, $this->is_loaded, true))
			return $this->language;

		$_module or $_module = ci()->router->fetch_module();
		list($path, $_langfile) = Modules::find($langfile . '_lang', $_module, 'Language/' . $idiom . '/');

		if ($path === false) {
			if ($lang = parent::load($langfile, $lang, $return, $add_suffix, $alt_path)) return $lang;
		} else {
			if ($lang = Modules::load_file($_langfile, $path, 'lang')) {
				if ($return) return $lang;
				$this->language = array_merge($this->language, $lang);
				$this->is_loaded[] = $langfile . '_lang' . PHPEXT;
				unset($lang);
			}
		}

		return $this->language;
	}
}

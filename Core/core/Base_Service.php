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

/**
 * A service layer implementation for Webby
 * It can be used to load service based classes
 * to simplify logic created in controllers
 */
class Base_Service
{
    public function __construct()
    {
        log_message('debug', "Service Class Initialized");
    }

    function __get($key)
    {
        $CI = get_instance();
        return $CI->$key;
    }
}
/* end of file Base_Service.php */

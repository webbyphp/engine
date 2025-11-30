<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Base\Taps;

use  Base\Taps\ToTap;

class Input
{
    /**
     * CI Input instance
     *
     * @var \CI_Input string
     */
    protected static $instance = \CI_Input::class;

    public static function __callStatic($method, $arguments)
    {
        return ToTap::make(self::$instance, $method, $arguments);
    }
}

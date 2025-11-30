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

class Tap
{
    protected static $instance = \CI_Instance::class;

    public static function use(string $instance = '')
    {
        self::$instance = $instance;

        return new static;
    }

    public static function __callStatic($method, $arguments)
    {
        return ToTap::make(self::$instance, $method, $arguments);
    }
}

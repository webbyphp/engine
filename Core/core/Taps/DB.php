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

class DB
{
    /**
     * CI Database instance
     *
     * @var string
     */
    protected static $instance = 'database';

    public static function use($model = '')
    {
        // Set the static property
        self::$instance = $model;

        // Return the class itself to enable method chaining
        return new static;
    }

    public static function __callStatic($method, $arguments)
    {
        return ToTap::make(self::$instance, $method, $arguments);
    }
}

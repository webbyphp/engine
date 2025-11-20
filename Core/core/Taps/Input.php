<?php

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

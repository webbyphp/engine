<?php

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

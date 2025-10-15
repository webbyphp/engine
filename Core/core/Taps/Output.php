<?php

namespace Base\Taps;

use  Base\Taps\ToTap;

class Output
{
    /**
     * CI Output instance
     *
     * @var \CI_Output string
     */
    protected static $instance = 'output';

    public static function __callStatic($method, $arguments)
    {
        return ToTap::make(self::$instance, $method, $arguments);
    }

}

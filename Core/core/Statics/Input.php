<?php

namespace Base\Statics;

use  Base\Statics\ToStatic;

class Input
{
    /**
     * CI Input instance
     *
     * @var \CI_Input string
     */
    protected static $instance = 'input';

    public static function __callStatic($method, $arguments)
    {
        return ToStatic::make(self::$instance, $method, $arguments);
    }

}

<?php

namespace Base\Statics;

use  Base\Statics\ToStatic;

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
        return ToStatic::makeStatic(self::$instance, $method, $arguments);
    }

}

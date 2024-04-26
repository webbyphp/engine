<?php

namespace Base\Statics;

use  Base\Statics\ToStatic;

class DB
{
     /**
     * CI Database instance
     *
     * @var string
     */
    protected static $instance = 'database';

    public static function __callStatic($method, $arguments)
    {
        return ToStatic::make(self::$instance, $method, $arguments);
    }

}

<?php

namespace Base\Taps;

/**
 * MakeTap Abstract Class
 *
 * @author  Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 * 
 */
abstract class MakeTap
{
    /**
     * Returns the fully qualified class name.
     *
     * @return mixed
     */
    public static function getFullyQualifiedClass() {}

    /**
     * Get the constructor arguments.
     *
     * @return mixed
     */
    public static function getConstructorArguments() {}
}

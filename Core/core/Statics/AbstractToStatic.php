<?php

namespace Base\Statics;

/**
 * ToStatic Abstract Class
 *
 * @author  Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 * 
 */
abstract class AbstractToStatic
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

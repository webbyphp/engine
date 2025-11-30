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

/**
 * MakeTap Abstract Class
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

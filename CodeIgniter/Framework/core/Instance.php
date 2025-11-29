<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class CI_Instance
{
    /**
     * Creates an instance of CodeIgniter 
     *
     * @param  string $path
     * @param  array  $server
     * @param  array  $globals
     * @return \CI_Controller
     */
    public static function create(array $server = [], array $globals = [])
    {
        $globals = empty($globals) ? $GLOBALS : $globals;

        $server = empty($server) ? $_SERVER : $server;

        // $ci is CodeIgniter for short
        $ci = new \CI_CodeIgniter($globals, $server);

        return $ci->instance();
    }
}

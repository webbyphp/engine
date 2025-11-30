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

use  Base\Taps\ToTap;

class Log
{

    /**
     * App log variable
     * @var string
     */
    private static $app = 'app';

    /**
     * User log variable
     * @var string
     */
    private static $user = 'user';

    /**
     * Dev log variable
     * @var string
     */
    private static $dev = 'dev';

    /**
     * Error log variable
     * @var string
     */
    private static $error = 'error';

    /**
     * Info log variable
     * @var string
     */
    private static $info = 'info';

    /**
     * Debug log variable
     * @var string
     */
    private static $debug = 'debug';

    /**
     * CI Input instance
     *
     * @var \CI_Log
     */
    protected static $instance = \CI_Log::class;

    public static function app(string $message)
    {
        log_message(self::$app, $message);
    }

    public static function user(string $message)
    {
        log_message(self::$user, $message);
    }

    public static function dev(string $message)
    {
        log_message(self::$dev, $message);
    }

    public static function error(string $message)
    {
        log_message(self::$error, $message);
    }

    public static function info(string $message)
    {
        log_message(self::$info, $message);
    }

    public static function debug(string $message)
    {
        log_message(self::$debug, $message);
    }

    public static function message(string $message, string $level = 'error')
    {
        log_message($level, $message);
    }

    public static function __callStatic($method, $arguments)
    {
        return ToTap::make(self::$instance, $method, $arguments);
    }
}

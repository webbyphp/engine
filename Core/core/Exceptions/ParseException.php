<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Base\Exceptions;

use \Base\Exceptions\ErrorException as BaseErrorException;

class ParseException extends BaseErrorException
{
    public function __construct(array $error)
    {
        $message   = $error['message'] ?: 'There was an error parsing the file';
        $code      = isset($error['code']) ? $error['code'] : 0;
        $severity  = isset($error['type']) ? $error['type'] : 1;
        $filename  = isset($error['file']) ? $error['file'] : __FILE__;
        $lineno    = isset($error['line']) ? $error['line'] : __LINE__;
        $exception = isset($error['exception']) ? $error['exception'] : null;

        parent::__construct($message, $code, $severity, $filename, $lineno, $exception);
    }
}

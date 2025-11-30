<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Base\Console\ConsoleColor;
use Base\Console\Commands\Help as ConsoleHelp;
use Base\Controllers\ConsoleController;

class Help extends ConsoleController
{
    public function __construct()
    {
        parent::__construct();
        $this->onlydev();
    }

    public function index()
    {
        ConsoleHelp::showHelp();
    }
}

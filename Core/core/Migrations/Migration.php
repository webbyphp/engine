<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Migration
 *
 * Extend CI_Migration class
 *
 */

namespace Base\Migrations;

class Migration extends \CI_Migration
{
    public function __construct($config = [])
    {
        parent::__construct($config);
    }
}

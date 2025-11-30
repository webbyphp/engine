<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

defined('COREPATH') or exit('No direct script access allowed');

use Base\HMVC\ModuleController;

// class Base_Controller extends MX_Controller
class Base_Controller extends ModuleController
{
    /**
     * Data array variable
     *
     * @var array
     */
    public $data = [];

    public function __construct()
    {
        parent::__construct();

        // Protection
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }
}
/* end of file Base_Controller.php */

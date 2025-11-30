<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Base\Models;

use Base\Models\Model;

class EasyModel extends Model
{
    /**
     * The model's default table.
     *
     * @var string
     */
    public $table;

    /**
     * EasyModel __construct
     */
    public function __construct()
    {
        parent::__construct();

        $this->use->database();
    }
}
/* end of file Core/core/Models/EasyModel.php */

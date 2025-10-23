<?php

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

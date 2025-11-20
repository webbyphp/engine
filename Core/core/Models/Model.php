<?php

namespace Base\Models;

class Model extends \CI_Model
{
    /**
     * The model's default table.
     *
     * @var string
     */
    public $table;

    /**
     * The handle variable.
     *
     * @var string
     */
    public $handle;

    /**
     * Construct the CI_Model
     */
    public function __construct()
    {
        parent::__construct();

        $this->use->database();
    }

    /**
     * Override with extended class
     */
    public function table()
    {
        return $this->db->from($this->table);
    }

    public function __get($key)
    {
        // CI parent::__get() check
        if (property_exists(get_instance(), $key)) {

            return parent::__get($key);
        }

        // Exception
        throw new \Exception("Property `{$key}` does not exist", 500);
    }
}
/* end of file Core/core/Models/Model.php */

<?php
defined('COREPATH') or exit('No direct script access allowed');

/**
 * An action layer implementation for Webby
 * It can be used to load action based classes
 * to simplify logic created in controllers
 * mostly to assist CRUD Based functionalities
 */

class Base_Action
{
    public function __construct()
    {
        log_message('debug', "Action Class Initialized");
    }

    /**
     * __get magic
     *
     * Allows models to access CI's loaded classes using the same
     * syntax as controllers.
     *
     * This is the same as what CI's model uses, but we keep it
     * here since that's the ONLY thing that CI's model does.
     *
     * @param    string $key
     */
    public function __get($key)
    {
        // Give access to protected class vars
        if (isset($this->$key)) {
            return $this->$key;
        }

        $CI = get_instance();
        return $CI->$key;
    }
}
/* end of file Base_Action.php */

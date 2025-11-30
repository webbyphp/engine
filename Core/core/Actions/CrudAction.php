<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Base\Actions;

abstract class CrudAction extends Action
{

    /**
     * Use to load database
     *
     * @return void
     */
    protected function useDatabase()
    {
        // Load the CodeIgniter Database 
        // Object from here i.e $this->db
        $this->load->database();
    }

    /**
     * The model method 
     * 
     * Will be implemented in child classes
     *
     * @return object
     */
    abstract public function model();
}

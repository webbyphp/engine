<?php

use Base\Session\Prune;
use Base\Controllers\ConsoleController;

class Session extends ConsoleController
{

    private $prune;

    public function __construct()
    {
        parent::__construct();
        
        $this->onlydev();

        $this->prune = new Prune;
    }

    public function index($path = '')
    {
        $this->clean($path);
    }

    public function clean($path = '')
    {
        try {

            if ($path === 'db') {
                $this->prune->databaseSessions();
            } else {
                $this->prune->fileSessions();
            }

            echo $this->success(ucwords($path). " sessions pruned successfully");

        } catch (\Exception $e) {
            echo $this->error(ucwords($path) . " session failed to prune, please check for path permissions");
        }
        
    }

}

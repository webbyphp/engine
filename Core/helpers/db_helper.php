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

/**
 *  DB Helper functions
 *
 *  @package		Webby
 *	@subpackage		Helpers
 *	@category		Helpers
 *	@author			Kwame Oteng Appiah-Nti
 */

// ------------------------------------------------------------------------


/* ------------------------------- DB Functions ---------------------------------*/

if (! function_exists('use_table')) {
    /**
     * Select and use any table by specifying
     * the table name
     * 
     * By default it uses the EasyModel class
     * 
     * @param string $table
     * @return object
     */
    function use_table($table = '', $with = 'BaseModel')
    {
        // This will default to EasyModel unless 
        // Model type $with is changed appropriately
        $model = match ($with) {
            'EasyModel' => new \Base\Models\EasyModel,
            'BaseModel' => new \Base\Models\BaseModel,
            'Model'     => new \Base\Models\Model,
            default     => new \Base\Models\Model, // Default to Model
        };

        if (!empty($table)) {
            $model->{'table'} = $table; // bypass dynamic property error
        }

        return $model;
    }
}

if (! function_exists('use_db')) {
    /**
     * CodeIgniter's database object
     *
     * @return object
     */
    function use_db($database_name = '', $db_group = 'default')
    {
        $db = null;

        if (contains('://', $database_name) || contains('=', $database_name)) {
            $db = ci()->load->database($database_name, true);
            return ci()->db =  $db;
        }

        if (strstr($db_group, '.')) {
            $db_group = str_replace('.', '/', $db_group);
        }

        if (contains('/', $db_group)) {

            $module = $config_file = $config_name = null;

            [$module, $config_file, $config_name] = explode('/', $db_group);

            ci()->load->config(ucfirst($module) . '/' . ucfirst($config_file));

            $db_config = ci()->config->item($config_name);

            $db = ci()->load->database($db_config, true);
        }

        if ($db_group === 'default' && $db === null) {
            ci()->load->database($db_group);
        } else if ($db_group !== 'default' && $db === null) {
            ci()->load->database($db_group);
        }

        ci()->db = $db ?? ci()->db;

        $exists = $database_name !== null && select_db($database_name);

        if ($exists) {
            return ci()->db;
        } else {
            ci()->load->database();
        }

        return ci()->db;
    }
}

if (! function_exists('use_json')) {
    /**
     * Creates a new instance of the
     * Base\Json\Db class and sets the path and filename.
     *
     * @param string $path The path to the JSON file.
     * @throws Exception If the file or file path is not set correctly.
     * @return Base\Json\Db The newly created instance of the Base\Json\Db class.
     */
    function use_json($path = '')
    {
        $json = null;

        try {

            $path = pathinfo($path);
            $source = (object) $path;

            $json = new Base\Json\Db();
            $json->{'path'} = $source->dirname;

            $json->from($source->basename);
        } catch (Exception $e) {
            throw new Exception('File or File path not set correctly');
        }

        return $json;
    }
}

if (! function_exists('select_db')) {
    /**
     * Select a database to use
     *
     * @param string $database_name
     * @return mixed
     */
    function select_db(string $database_name)
    {
        return ci()->db->db_select($database_name);
    }
}

if (! function_exists('close_db')) {
    /**
     * Close a selected database
     *
     * @return void
     */
    function close_db()
    {
        ci()->db->close();
    }
}

if (! function_exists('max_id')) {
    /**
     * This function let's you get
     * highest id value from a table.
     * 
     * @param string $table
     * @param string $select_as
     * @return string|int
     */
    function max_id($table, $select_as = null)
    {

        if ($select_as != null) {
            $maxid = ci()->db->query('SELECT MAX(id) AS ' . $select_as . ' FROM ' . $table)->row()->$select_as;
        } else {
            $maxid = ci()->db->query('SELECT MAX(id) AS biggest  FROM ' . $table)->row()->biggest;
        }

        return $maxid;
    }
}

if (! function_exists('add_foreign_key')) {
    /**
     * @param string $table       Table name
     * @param string $foreign_key Collumn name having the Foreign Key
     * @param string $references  Table and column reference. Ex: users(id)
     * @param string $on_delete   RESTRICT, NO ACTION, CASCADE, SET NULL, SET DEFAULT
     * @param string $on_update   RESTRICT, NO ACTION, CASCADE, SET NULL, SET DEFAULT
     *
     * @return string SQL command
     */
    function add_foreign_key($table, $foreign_key, $references, $on_delete = 'RESTRICT', $on_update = 'RESTRICT')
    {
        $references = explode('(', str_replace(')', '', str_replace('`', '', $references)));

        return "ALTER TABLE `{$table}` ADD CONSTRAINT `{$table}_{$foreign_key}_fk` FOREIGN KEY (`{$foreign_key}`) REFERENCES `{$references[0]}`(`{$references[1]}`) ON DELETE {$on_delete} ON UPDATE {$on_update}";
    }
}

if (! function_exists('drop_foreign_key')) {
    /**
     * @param string $table       Table name
     * @param string $foreign_key Collumn name having the Foreign Key
     *
     * @return string SQL command
     */
    function drop_foreign_key($table, $foreign_key)
    {
        return "ALTER TABLE `{$table}` DROP FOREIGN KEY `{$table}_{$foreign_key}_fk`";
    }
}

if (! function_exists('add_trigger')) {
    /**
     * Add an SQL Trigger Command
     * 
     * @param string $trigger_name Trigger name
     * @param string $table        Table name
     * @param string $statement    Command to run
     * @param string $time         BEFORE or AFTER
     * @param string $event        INSERT, UPDATE or DELETE
     * @param string $type         FOR EACH ROW [FOLLOWS|PRECEDES]
     *
     * @return string SQL Command
     */
    function add_trigger($trigger_name, $table, $statement, $time = 'BEFORE', $event = 'INSERT', $type = 'FOR EACH ROW')
    {
        return 'DELIMITER ;;' . PHP_EOL . "CREATE TRIGGER `{$trigger_name}` {$time} {$event} ON `{$table}` {$type}" . PHP_EOL . 'BEGIN' . PHP_EOL . $statement . PHP_EOL . 'END;' . PHP_EOL . 'DELIMITER ;;';
    }
}

if (! function_exists('drop_trigger')) {
    /**
     * Trigger an SQL Command using name
     *
     * @param string $trigger_name Trigger name
     * @return string SQL Command
     */
    function drop_trigger($trigger_name)
    {
        return "DROP TRIGGER {$trigger_name};";
    }
}

// --------------------------------------------------------------

if (! function_exists('use_json_model')) {
    /**
     * Create a JSON model instance
     *
     * @param string $table
     * @return object
     */
    function use_json_model($modelname = '')
    {
        $model = use_model($modelname);

        if (is_object($model)) {
            return $model;
        }

        return app()->{$modelname};
    }
}

if (! function_exists('json_db')) {
    /**
     * Get JSON database instance
     *
     * @param string $path
     * @return Base\Json\Db
     */
    function json_db($path = '')
    {
        return new Base\Json\Db($path);
    }
}

if (! function_exists('json_table')) {
    /**
     * Quick table access with JSON database
     *
     * @param string $table
     * @return Base\Json\Db
     */
    function json_table($table)
    {
        $path = config('json_db_path');

        $table = $path . DS . $table . '.json';

        return use_json($table);
    }
}

if (! function_exists('json_migrate')) {
    /**
     * Migrate data from one format to another
     *
     * @param string $fromTable
     * @param string $toTable
     * @param callable|null $transformer
     * @return bool
     */
    function json_migrate($fromTable, $toTable, $transformer = null)
    {
        $sourceData = json_table($fromTable)->all();

        if (empty($sourceData)) {
            return false;
        }

        $targetModel = json_table($toTable);

        foreach ($sourceData as $record) {
            $data = is_object($record) ? (array) $record : $record;

            if ($transformer && is_callable($transformer)) {
                $data = $transformer($data);
            }

            $targetModel->insert($data);
        }

        return true;
    }
}

if (! function_exists('json_backup_all')) {
    /**
     * Backup all JSON tables
     *
     * @return array
     */
    function json_backup_all()
    {
        $db = json_db();
        $tables = $db->listTables();
        $results = [];

        foreach ($tables as $table) {
            $results[$table] = $db->backupTable($table);
        }

        return $results;
    }
}

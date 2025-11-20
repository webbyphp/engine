<?php

/*
| -------------------------------------------------------------------------
| Json Database
| -------------------------------------------------------------------------
| This file implements json as a flat file database
|
*/

namespace Base\Json;

/**
 * Enhanced JSON Database Class
 * 
 * Provides improved functionality and CI_Model compatibility
 *
 * @package		Webby
 * @subpackage	JSON
 * @category	database
 * @author		Kwame Oteng Appiah-Nti
 */
class Db extends \CI_DB_json_driver
{
    /**
     * Constructor
     *
     * @param string|array $params
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // Load JSON database configuration
        // ci()->load->config('json_database');
    }

    /**
     * Create a database (directory)
     *
     * @param string $name
     * @return bool|string
     */
    public function createDatabase($name = '')
    {
        $path = $this->path . DIRECTORY_SEPARATOR . $name;

        if (!is_dir($path)) {
            return mkdir($path, config_item('json_db_dir_permissions'), true);
        }

        return true;
    }

    /**
     * Drop a database (directory and all files)
     *
     * @param string $name
     * @return bool
     */
    public function dropDatabase($name)
    {
        $path = $this->path . DIRECTORY_SEPARATOR . $name;

        if (is_dir($path)) {
            return $this->removeDirectory($path);
        }

        return true;
    }

    /**
     * List all tables (JSON files) in the database
     *
     * @return array
     */
    public function listTables()
    {
        $tables = [];
        $files = glob($this->path . '/*.json');

        foreach ($files as $file) {
            $tables[] = pathinfo($file, PATHINFO_FILENAME);
        }

        return $tables;
    }

    /**
     * Check if table exists
     *
     * @param string $table
     * @return bool
     */
    public function tableExists($table)
    {
        $filePath = $this->path . DIRECTORY_SEPARATOR . $table . '.json';
        return file_exists($filePath);
    }

    /**
     * Create table (JSON file)
     *
     * @param string $table
     * @param array $schema
     * @return bool
     */
    public function createTable($table, array $schema = [])
    {
        $filePath = $this->path . DIRECTORY_SEPARATOR . $table . '.json';

        if (!file_exists($filePath)) {
            $initialData = [];
            return file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT)) !== false;
        }

        return true;
    }

    /**
     * Load all data from a table (JSON file)
     *
     * @param string $table The name of the table (JSON file).
     * @return array The decoded JSON data as an array, or an empty array if the file doesn't exist or is invalid.
     */
    public function loadTableData($table)
    {
        $filePath = $this->path . DIRECTORY_SEPARATOR . $table . '.json';

        if (!file_exists($filePath)) {
            return [];
        }

        $json_data = file_get_contents($filePath);
        $data = json_decode($json_data, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Save data to a table (JSON file)
     *
     * @param string $table The name of the table (JSON file).
     * @param array $data The data to be encoded and saved.
     * @return bool TRUE on success, FALSE on failure.
     */
    public function saveTableData($table, array $data)
    {
        $filePath = $this->path . DIRECTORY_SEPARATOR . $table . '.json';
        $json_data = json_encode($data, JSON_PRETTY_PRINT);

        if ($json_data === false) {
            return false;
        }

        return file_put_contents($filePath, $json_data) !== false;
    }

    /**
     * Drop table (delete JSON file)
     *
     * @param string $table
     * @return bool
     */
    public function dropTable($table)
    {
        $filePath = $this->path . DIRECTORY_SEPARATOR . $table . '.json';

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true;
    }

    /**
     * Backup table
     *
     * @param string $table
     * @return bool
     */
    public function backupTable($table)
    {
        if (!config_item('json_db_backup_enabled')) {
            return false;
        }

        $backupPath = config_item('json_db_backup_path');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, config_item('json_db_dir_permissions'), true);
        }

        $sourceFile = $this->path . DIRECTORY_SEPARATOR . $table . '.json';
        $backupFile = $backupPath . DIRECTORY_SEPARATOR . $table . '_' . date('Y-m-d_H-i-s') . '.json';

        if (file_exists($sourceFile)) {
            return copy($sourceFile, $backupFile);
        }

        return false;
    }

    /**
     * Restore table from backup
     *
     * @param string $table
     * @param string $backupFile
     * @return bool
     */
    public function restoreTable($table, $backupFile)
    {
        $targetFile = $this->path . DIRECTORY_SEPARATOR . $table . '.json';

        if (file_exists($backupFile)) {
            return copy($backupFile, $targetFile);
        }

        return false;
    }

    /**
     * Get table statistics
     *
     * @param string $table
     * @return array
     */
    public function getTableStats($table)
    {
        $data = $this->loadTableData($table);
        $filePath = $this->path . DIRECTORY_SEPARATOR . $table . '.json';

        // Clear the cache to get fresh stats
        // clearstatcache();

        return [
            'table' => $table,
            'records' => count($data),
            'file_size' => file_exists($filePath) ? filesize($filePath) : 0,
            'created' => file_exists($filePath) ? date('Y-m-d H:i:s', filectime($filePath)) : null,
            'modified' => file_exists($filePath) ? date('Y-m-d H:i:s', filemtime($filePath)) : null
        ];
    }

    /**
     * Optimize table (clean up and reformat JSON)
     *
     * @param string $table
     * @return bool
     */
    public function optimizeTable($table)
    {
        $data = $this->loadTableData($table);

        // Remove any invalid records and reindex
        $cleanData = array_values(array_filter($data, function ($record) {
            return is_array($record) && !empty($record);
        }));

        return $this->saveTableData($table, $cleanData);
    }

    /**
     * Remove directory recursively
     *
     * @param string $dir
     * @return bool
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($filePath)) {
                $this->removeDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }

        return rmdir($dir);
    }
}

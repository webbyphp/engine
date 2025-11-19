<?php

/**
 * 
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * SQLite Cache Driver for Webby
 *
 * This driver uses SQLite to store cache data.
 * 
 * @package	CodeIgniter
 * @subpackage Libraries
 * @author	Jens Segers
 * @author   Kwame Oteng Appiah-Nti <developerkwame@gmail.com> (Developer Kwame)
 * @license	MIT License Copyright (c) 2011 Jens Segers
 *
 */

class CI_Cache_sqlite extends CI_Driver
{

    /**
     * Path to the cache database file.
     *
     * @var string
     */
    protected $cache_path;

    /**
     * Directory in which to save cache files
     *
     * @var string
     */
    protected $cache_file;

    /**
     * Whether to automatically flush expired cache items.
     *
     * @var bool
     */
    protected $auto_flush;

    /**
     * SQLite object.
     *
     * @var PDO
     */
    protected $sqlite;

    /**
     * Constructor.
     */
    public function __construct()
    {

        $CI = get_instance();

        // Get cache_path from config if available.
        $path = $CI->config->item('cache_path');
        $this->cache_path = ($path == '') ? WRITABLEPATH . 'cache/' : $path;

        // Get cache_file name from config if available.
        $cache_file = $CI->config->item('cache_file');
        $this->cache_file = ($cache_file == '') ? 'cache.sqlite' : $cache_file;

        // Get auto_flush name from config if available.
        $auto_flush = $CI->config->item('cache_autoflush');
        $this->auto_flush = ($auto_flush == '') ? false : $auto_flush;

        // Initialize the database.
        try {

            $this->sqlite = new PDO('sqlite:' . $this->cache_path . $this->cache_file);
            $this->sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create cache database.
            $this->sqlite->exec("CREATE TABLE IF NOT EXISTS cache (id TEXT PRIMARY KEY, data BLOB, expire INTEGER)");

            // Don't verify data on disk.
            $this->sqlite->exec("PRAGMA synchronous = OFF");

            // Turn off rollback.
            $this->sqlite->exec("PRAGMA journal_mode = OFF");

            // Periodically clean the database.
            $this->sqlite->exec("PRAGMA auto_vacuum = INCREMENTAL");
        } catch (PDOException $e) {
            show_error($e->getMessage());
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Is supported
     *
     * Check if the SQLite PDO driver is available
     * 
     * @return boolean
     */
    public function is_supported()
    {
        return in_array("sqlite", PDO::getAvailableDrivers());
    }

    // ------------------------------------------------------------------------

    /**
     * Fetch from cache
     *
     * @param 	mixed		unique key id
     * @return 	mixed		data on success/false on failure
     */
    public function get($id)
    {
        try {

            $query = $this->sqlite->query("SELECT * FROM cache WHERE id = '" . (string) $id . "'");

            // cache miss
            if (!$query || !$data = $query->fetch(PDO::FETCH_ASSOC)) {
                return false;
            }

            // time to live elapsed
            if (time() > $data['expire']) {
                $this->delete($id);
                return false;
            }

            return unserialize($data['data']);
        } catch (PDOException $e) {
            return false;
        }
    }

   // ------------------------------------------------------------------------

    /**
     * Save into cache
     *
     * @param 	string		unique key
     * @param 	mixed		data to store
     * @param 	int			length of time (in seconds) the cache is valid 
     * Default is 60 seconds
     * @return 	boolean		true on success/false on failure
     */
    public function save($id, $data, $ttl = 60)
    {
        try {
            // insert or replace data
            $query = $this->sqlite->query("INSERT OR REPLACE INTO cache(id, data, expire) VALUES ('" . (string) $id . "', '" . serialize($data) . "', '" . (time() + $ttl) . "')");

            // trigger auto-flush
            if ($this->auto_flush)
                $this->flush();

            return $query ? true : false;
        } catch (PDOException $e) {
            return false;
        }
    }
   
   // ------------------------------------------------------------------------


    /**
     * Delete from Cache
     *
     * @param 	mixed		unique identifier of item in cache
     * @return 	boolean		true on success/false on failure
     */
    public function delete($id)
    {
        try {
            // delete data
            $query = $this->sqlite->exec("DELETE FROM cache WHERE id = '" . (string) $id . "'");
            return $query ? true : false;
        } catch (PDOException $e) {
            return false;
        }
    }
   
   // ------------------------------------------------------------------------

    /**
     * Clean the Cache
     *
     * @return 	boolean		false on failure/true on success
     */
    public function clean()
    {
        try {
            // delete all data
            $query = $this->sqlite->exec("DELETE FROM cache");
            return $query ? true : false;
        } catch (PDOException $e) {
            return false;
        }
    }
   
   // ------------------------------------------------------------------------

    /**
     * A custom method that will flush all expired cache items
     *
     * @return 	boolean		false on failure/true on success
     */
    public function flush()
    {
        try {
            $query = $this->sqlite->exec("DELETE FROM cache WHERE expire < '" . time() . "'");
            return $query ? true : false;
        } catch (PDOException $e) {
            return false;
        }
    }
   
   // ------------------------------------------------------------------------

    /**
     * Cache Info
     *
     * @return 	mixed 	false
     */
    public function cache_info()
    {
        try {

            $info = [];

            // get number of items in cache
            $query = $this->sqlite->query("SELECT count(1) FROM cache");

            if ($query && $result = $query->fetch()) {
                $info["items"] = $result[0];
            } else {
                $info["items"] = 0;
            }

            $info["size"] = filesize($this->cache_path . $this->cache_file);
            $info["path"] = $this->cache_path;
            $info["filename"] = $this->cache_file;

            return $info;
        } catch (PDOException $e) {
            return false;
        }
    }
   
   // ------------------------------------------------------------------------

    /**
     * Get Cache Metadata
     *
     * @param 	mixed		key to get cache metadata on
     * @return 	mixed		false on failure, array on success.
     */
    public function get_metadata($id)
    {
        try {

            $query = $this->sqlite->query("SELECT * FROM cache WHERE id = '" . (string) $id . "'");

            // cache miss
            if (!$query || !$data = $query->fetch(PDO::FETCH_ASSOC)) {
                return false;
            }

            return $data;
        } catch (\PDOException $e) {
            return false;
        }
    }
}

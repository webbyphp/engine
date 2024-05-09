<?php

namespace Base\Cache;

class Cache extends \Base_Output
{
    protected $ci;

    private $thisDirectory = 'files';

    /**
     * Number of seconds that a 
     * cached item will be considered current
     *
     * @var integer
     */
    public $expireAfter = 1800;

    public $serializeWith = 'serialize';

    private const JSON = 'json';
    private const IGBINARY = 'igbinary';
    private const SERIALIZE = 'serialize';

    public $cacheExtension = '.cache';

    public function __construct()
    {
        parent::__construct();

        $this->ci =& get_instance();
    }

    /* ----------------------------- For Custom Caching and Retrieving ---------------------- */

    // Credit https://github.com/colettesnow/Simple-Cache-for-CodeIgniter

    /**
     * Caches an item which can be retrieved by key
     *
     * @param string $key identitifer to retrieve the data later
     * @param mixed $value to be cached
     * @return mixed
     */
    public function cacheItem($key, $value = null)
    {
        if (is_string($key) && $value !== null) {
            $this->setCacheItem($key, $value);
            return true;
        }

        if ($this->isCached($key)) {
            return $this->getCacheItem($key);
        }
        
        return null;
    }
    
    /**
     * Caches an item which can be retrieved by key
     * 
     * Alias to $this->cacheItem()
     * 
     * @param string $key identitifer to retrieve the data later
     * @param mixed $value to be cached
     * @return mixed
     */
    public function item($key, $value = null)
    {
        return $this->cacheItem($key, $value);
    }

    /**
     * Set Custom Path
     *
     * @param string $path
     * @return object
     */
    public function setCachePath($path = '')
    {
        if ($this->customPath == $this->defaultPath) {
            $this->customPath = $this->thisDirectory;
        }

        if (!empty($path)) {
            $this->customPath = $path;
        }

        $this->customPath;

        return $this;
    }

    /**
     * Set Custom Path
     *
     * @param string $path
     * @return mixed // return type will change
     */
    public function path($path = '')
    {
        return $this->setCachePath($path);
    }

    /**
     * Check's whether an item is cached or not
     *
     * @param string $key containing the identifier of the cached item
     * @param int $ttl time to expire
     * @return bool whether the item is currently cached or not
     */
    public function isCached($key, $ttl = null)
    {
        $key = sha1($key);

        $this->setCachePath(); // Set the correct cache path

        // Use the default expiration time if $ttl is not provided
        $ttl = $ttl ?? $this->expireAfter;

        $cachePath = $this->filesCachePath();

        $cachedFile = $cachePath . $key . $this->cacheExtension;

        if (!file_exists($cachedFile)) {
            return false;
        }

        // Get the modification time of the cached file 
        // and add the expiration time
        $expirationTime = filemtime($cachedFile) + $ttl;

        // Check if the item has expired or not
        if ($expirationTime < time()) {
            return false;
        }

        return true;
    }

    /**
     * Check's whether an item is cached or not
     *
     * @param string $key containing the identifier of the cached item
     * @param int $ttl time to expire
     * @return bool whether the item is currently cached or not
     */
    public function exists($key, $ttl = null)
    {
        return $this->isCached($key, $ttl);
    }

    /**
     * Set Cache Item with Time to live
     *
     * @return void
     */
    public function setCacheItem($key, $value, $ttl = null)
    {
        $this->setCachePath(); // Set the correct cache path

        // Use the default expiration time if $ttl is not provided
        $ttl = $ttl ?? $this->expireAfter;

        // Hash the key in order to ensure that the item
        // is stored with an appropriate file name in the file system.
        $key = sha1($key);

        $cachedFile = $this->filesCachePath() . $key . $this->cacheExtension;

        // Serialize or compress the contents so that they can 
        // be stored in plain text
        if ($this->serializeWith === self::JSON) {
            $value = json_encode($value);
        }

        if (
            $this->serializeWith === self::IGBINARY
            && function_exists('igbinary_serialize')
        ) {
            $value = \igbinary_serialize($value);
        }

        if ($this->serializeWith === self::SERIALIZE) {
            $value = serialize($value);
        }

        try {
            file_put_contents($cachedFile, $value, LOCK_EX);
            touch($cachedFile, time() + $ttl);
        } catch(\Exception $e) {
            log_message('error', $e->getMessage());
        }

    }

    /**
     * Set Cache Item with Time to live
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return void
     */
    public function save($key, $value, $ttl = null)
    {
        $this->setCacheItem($key, $value, $ttl);
    }

    /**
     * Retrieve's the cached item
     *
     * @param string $key containing the identifier of the item to retrieve
     * @return mixed the cached item or items
     */
    public function getCacheItem($key)
    {
        $this->setCachePath(); // Set the correct cache path

        $key = sha1($key); // hash the key

        $cachedFile = $this->filesCachePath() . $key . $this->cacheExtension;

        $exists = file_exists($cachedFile);

        if (!$exists) {
            return false;
        }

        $items = file_get_contents($cachedFile);

        // unserializes or uncompress the contents so that they can
        // be read in array or object
        if ($this->serializeWith === self::JSON) {
            $items = json_decode($items);
        }

        if ( $this->serializeWith === self::IGBINARY
            && function_exists('igbinary_unserialize')
        ) {
            shush();
                $items = \igbinary_unserialize($items);
            speak_up();
        }

        if ($this->serializeWith === self::SERIALIZE) {
            $items = unserialize($items);
        }
        
        return $items;
    }

     /**
     * Retrieve's the cached item
     *
     * @param string $key containing the identifier of the item to retrieve
     * @return mixed the cached item or items
     */
    public function get($key)
    {
        return $this->getCacheItem($key);
    }

    /**
     * Return time remaining until cached file expires
     *
     * @return mixed
     */
    public function getTTL($key, $ttl = null)
    {
        $this->setCachePath(); // Set the correct cache path

        // Use the default expiration time if $ttl is not provided
        $ttl = $ttl ?? $this->expireAfter;
        
        $cachedFile = $this->filesCachePath() . $key . $this->cacheExtension;

        $expirationTime = filemtime($cachedFile) + $ttl;
        $timeRemaining = $expirationTime - time();
        return $timeRemaining > 0 ? $timeRemaining : 0;
    }

    /**
     * Return time remaining until cached file expires
     *
     * @param [type] $key
     * @return mixed
     */
    public function ttl($key)
    {
        return $this->getTTL($key);
    }

    /**
     * Delete's the cached item
     *
     * @param string $key containing the identifier of the item to delete.
    */
    public function deleteCacheItem($key)
    {
        $this->setCachePath(); // Set the correct cache path

        $cachePath = $this->filesCachePath();
        @unlink($cachePath .sha1($key). $this->cacheExtension);

        return true;
    }

    /* ----------------------------- For Checking and Pruning Cached Files ---------------------- */

    /**
     * Clears the cache for the specified path
     * @param string $uri The URI path
     * @return bool true if successful, false if not
     */
    public function clearPathCache($uri = '')
    {

        $cachePath = $this->filesCachePath();

        if (empty($uri)) {
            $uri = $this->ci->config->item('base_url') .
            $this->ci->config->item('index_page') .
            $uri;
        }

        $cachePath .= md5($uri);

        return @unlink($cachePath);
    }

    /**
     * Clears all cache from a cache directory
     */
    public function clearAllCache()
    {

        $cachePath = $this->filesCachePath();

        $handle = opendir($cachePath);

        while (($file = readdir($handle)) !== false) {
            // Leave the directory protection alone
            if ($file != '.htaccess' && $file != 'index.html') {
                @unlink($cachePath . '/' . $file);
            }
        }

        closedir($handle);
    }

    /**
     * Checks to see if a cache file exists for the specified path
     * @param string $uri The URI path to check
     * @return bool true if it is, false if not
     */
    public function pathCached($uri = '')
    {
        $cachePath = $this->filesCachePath();

        if (empty($uri)) {
            $uri = $this->ci->config->item('base_url') .
            $this->ci->config->item('index_page') .
            $uri;
        }

        $cachePath .= md5($uri);

        return file_exists($cachePath);
    }

    /**
     * Returns the cache expiration timestamp for the specified path
     * @param string $uri The URI path to check
     * @return int|boolean The expiration Unix timestamp or false if there is no cache
     */
    public function getPathCacheExpiration($uri, $readableDate = false)
    {
        $cachePath = $this->filesCachePath();

        if ((empty($uri))) {
            $uri = $this->ci->config->item('base_url') .
            $this->ci->config->item('index_page') .
            $uri;
        }
        
        $cachePath .= md5($uri);
        
        if (!$fp = @fopen($cachePath, FOPEN_READ)) {
            return false;
        }

        flock($fp, LOCK_SH);

        $cache = '';
        
        if (filesize($cachePath) > 0) {
            $cache = fread($fp, filesize($cachePath));
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        $searchTimestamp = substr($cache, 0, 31);
        
        // Strip out the embedded timestamp
        $timestamp = str_replace([':', ';', ' '], '', substr($searchTimestamp, 19));

        // Strip out the embedded timestamp
        if (empty($timestamp)) {
            return false;
        }

        // Return the timestamp
        return ($readableDate) ? date('d/m/Y H:i:s', $timestamp) : (int)trim($timestamp);
    }

    /* ----------------------------- Specific For Cached Web Views ---------------------- */

    /**
     * Write Cache for web pages
     *
     * @param   string  $output Output data to cache
     * @return mixed
     */
    public function writeWebCache(string $output)
    {
        return parent::_write_cache($output);
    }

    /**
     * Delete cache for web pages
     *
     * @param   string  $uri    URI string
     * @return  bool
     */
    public function deleteWebCache(string $uri = '') : bool
    {
        return parent::delete_cache($uri);
    }

}

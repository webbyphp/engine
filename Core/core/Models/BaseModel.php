<?php

/**
 * BaseModel with Laravel-like ORM Features
 * 
 * An advanced Model providing Laravel Eloquent-like functionality
 * for CodeIgniter 3 or Webby Framework with PHP 8.3 support
 * 
 * @author  Kwame Oteng Appiah-Nti <developerkwame@gmail.com> (Developer Kwame)
 * @license MIT
 * @version 1.0.0
 */

namespace Base\Models;

use DateTime;
use DateTimeZone;

class BaseModel extends Model implements \ArrayAccess
{
    /**
     * The model's default table.
     *
     * @var string
     */
    public $table;

    /**
     * The model's default primary key.
     *
     * @var string
     */
    public $primaryKey = 'id';

    /**
     * The model's attributes
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The model's original attributes
     *
     * @var array
     */
    protected $original = [];

    /**
     * Indicates if the model exists in database
     *
     * @var bool
     */
    public $exists = false;

    /**
     * Indicates if the model was recently created
     *
     * @var bool
     */
    public $wasRecentlyCreated = false;

    /**
     * The where variable to be used
     *
     * @var mixed
     */
    public $where;

    public $order;
    public $columnOrder;
    public $columnSearch;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    protected $createdAt = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    protected $updatedAt = 'updated_at';

    /**
     * Support for soft deletes and a model's 'deleted' key
     */
    protected $useSoftDelete = false;
    protected $useSoftDeleteKey = 'deleted_at';
    
    /**
     * The active value for $softDeleteField
     *
     * @var mixed
     */
    protected $softDeleteFalseValue = null;

    /**
     * The deleted value for $softDeleteField
     *
     * @var mixed
     */
    protected $softDeleteTrueValue = DATETIME;

    protected $temporaryWithDeleted = false;
    protected $temporaryOnlyDeleted = false;

    /**
     * Indicates if the model should track user 
     * who created/updated/deleted records.
     *
     * @var bool
     */
    public $trackUser = false;

    /**
     * Holds the user ID specifically for 
     * this model's instance/query.
     *
     * @var string
     */
    public $userId = '';

    /**
     * 
     * If $trackUser is true, set the session id
     * to be used to set the user id, by default
     * the session id used is 'user_id'.
     *
     * @var string
     */
    protected $userSessionKey = 'user_id';

    /**
     * The name of the "created by" column.
     *
     * @var string
     */
    protected $createdBy = 'created_by';

    /**
     * The name of the "updated by" column.
     *
     * @var string
     */
    protected $updatedBy = 'updated_by';

    /**
     * The name of the "deleted by" column (for soft deletes).
     *
     * @var string
     */
    protected $deletedBy = 'deleted_by';

    /**
     * Current user ID provider callback
     *
     * @var callable|null
     */
    protected static $currentUserProvider;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * Custom cast handlers
     *
     * @var array
     */
    protected static $customCastHandlers = [];

    /**
     * Cache configuration
     *
     * @var array
     */
    protected $cacheConfig = [
        'enabled' => false,
        'driver' => 'file', // file, memcached, redis, apc
        'ttl' => 3600, // Time to live in seconds
        'prefix' => 'BaseModel_',
        'tags' => []
    ];

    /**
     * Current query cache settings
     *
     * @var array
     */
    protected $currentCacheSettings = [
        'enabled' => null,
        'ttl' => null,
        'key' => null,
        'tags' => []
    ];

    /**
     * Cache driver instance
     *
     * @var object
     */
    protected $cacheDriver;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['*'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * The relationships that should be touched on save.
     *
     * @var array
     */
    protected $touches = [];

    /**
     * User-defined relationships
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * Model name mapping for relationships
     *
     * @var array
     */
    protected $modelMap = [];

    /**
     * Change the fetch mode if desired
     *
     * @var string $returnAs Optionally set to 'array', 
     * default is 'object', can also be set to 'json'
     */
    public $returnAs = 'object';

	/**
     * If the return type is object , one can specify 
     * a custom class representing the data to rather 
     * be created and returned as the stdClass object
     *
     * @var string
     */
    protected $customReturnObject = '';

    /**
     * Protected fields (legacy support)
     *
     * @var array
     */
    public $protected = [];

    /**
     * Global scopes
     *
     * @var array
     */
    protected $globalScopes = [];

    /**
     * The current globally used timezone
     *
     * @var string
     */
    protected $timezone = 'UTC';

    /**
     * Query builder instance
     *
     * @var object
     */
    public $queryBuilder;

    /**
     * Construct the CI_Model
     */
    public function __construct($attributes = [])
    {
        
        parent::__construct();
        
        // Initialize query builder
        $this->queryBuilder = $this->db;
        
        // Initialize cache driver
        $this->initializeCacheDriver();

        // Initialize attributes
        if (!empty($attributes)) {
            $this->fill($attributes);
            $this->exists = true;
            $this->wasRecentlyCreated = false;
        }
        
        // Apply global scopes
        $this->applyGlobalScopes();
        
        // Set default timezone
        $this->setTimezone();

    }

    /**
     * Set the table name
     *
     * @param string $table
     * @return $this
     */
    public function setTable(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Get the primary key value
     *
     * @return mixed
     */
    public function getKey(): mixed
    {
        return $this->getAttribute($this->primaryKey);
    }

    /**
     * Get the primary key name
     *
     * @return string
     */
    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    /**
     * Get the table name
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the model's attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get query builder with table
     *
     * @return \CI_DB_mysqli_driver|object
     */
    public function builder(): object
    {
        return $this->queryBuilder->from($this->table);
    }

    /**
     * Resolve the related model instance
     *
     * @param string $modelName
     * @return BaseModel
     * @throws \Exception
     */
    protected function resolveRelatedModel(string $modelName): BaseModel
    {
        $ci = get_instance();
        
        // Check model mapping first
        if (isset($this->modelMap[$modelName])) {
            $modelName = $this->modelMap[$modelName];
        }
        
        // Try different naming conventions
        $possibleNames = [
            strtolower($modelName) . '_model',  // post_model
            $modelName . '_model',              // Post_model
            $modelName . 'Model',               // PostModel
            $modelName,                         // Post
            strtolower($modelName),             // post
        ];
        
        foreach ($possibleNames as $name) {
            // Check if model is already loaded
            if (isset($ci->{$name})) {
                return $ci->{$name};
            }
            
            // Try to load the model
            try {
                $ci->load->model($name);
                if (isset($ci->{$name})) {
                    return $ci->{$name};
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // If no model found, try to create instance directly
        foreach ($possibleNames as $name) {
            if (class_exists($name)) {
                return new $name();
            }
        }
        
        throw new \Exception("Could not resolve model: {$modelName}. Please ensure the model exists and follows Webby's naming conventions.");
    }

    /**
     * Initialize cache driver
     */
    protected function initializeCacheDriver(): void
    {
        if (!$this->cacheConfig['enabled']) {
            return;
        }

        $ci = get_instance();
        
        // Load appropriate cache driver
        switch ($this->cacheConfig['driver']) {
            case 'memcached':
                $ci->load->driver('cache', array('adapter' => 'memcached'));
                break;
            case 'redis':
                $ci->load->driver('cache', array('adapter' => 'redis'));
                break;
            case 'apc':
                $ci->load->driver('cache', array('adapter' => 'apc'));
                break;
            case 'file':
            default:
                $ci->load->driver('cache', array('adapter' => 'file'));
                break;
        }
        
        $this->cacheDriver = $ci->cache;
    }

    /**
     * Apply global scopes
     */
    protected function applyGlobalScopes(): void
    {
        foreach ($this->globalScopes as $scope) {
            if (method_exists($this, $scope)) {
                $this->{$scope}();
            }
        }
    }

    /**
     * Set timezone
     */
    protected function setTimezone(): void
    {
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set($this->timezone);
        }
    }

    /**
     * Get current timestamp
     */
    protected function getCurrentTimestamp(): string
    {
        return (new DateTime('now', new DateTimeZone($this->timezone)))->format('Y-m-d H:i:s');
    }

     /**
     * Set the user ID to be used for created_by/updated_by.
     * Useful for API/CLI environments.
     *
     * @param mixed $userId User Id (int, string, or UUID)
     * @return $this
     */
    public function asUser(mixed $userId): static
    {
        $this->userId = $this->formatUserIdForDatabase($userId); 
        return $this;
    }

    /**
     * Set user id for tracking users
     *
     * @return string
     */
    protected function setUser(?string $userId = '')
    {
        if ( $userId != '' ) {
            $this->userId = $userId;
            return $this->userId;
        }

        if ( $this->userId != '' ) {
            return $this->userId;
        }

        if (session($this->userSessionKey)) {
            return session($this->userSessionKey);
        }

        return '';
    }

    /**
     * Get current user ID for tracking
     * Supports any ID type: int, string, UUID, etc.
     *
     * @return mixed
     */
    protected function getCurrentUserId(?string $userId = null): mixed
    {

        if ($this->userId !== null) {
            return $this->userId;
        }

        if (static::$currentUserProvider) {
            return call_user_func(static::$currentUserProvider);
        }

        // Common session keys
        $sessionKeys = ['user_id', 'id', 'user', 'auth_user_id', 'uuid', 'user_uuid'];
        $sessionKeyExists = in_array($this->userSessionKey, $sessionKeys);

        $userId = ($sessionKeyExists) ? session($this->userSessionKey) : null; 

        if ($userId) {
            $this->userId = $userId;
            return $userId;
        }

        return null;
    }

    /**
     * Set the current user provider callback
     * The callback should return the user ID in whatever format your system uses
     *
     * @param callable $provider Function that returns current user ID (int|string|uuid|etc)
     */
    public static function setCurrentUserProvider(callable $provider): void
    {
        static::$currentUserProvider = $provider;
    }

    /**
     * Validate and format user ID for database storage
     * Override this method if you need custom validation/formatting
     *
     * @param mixed $userId
     * @return mixed
     */
    protected function formatUserIdForDatabase(mixed $userId): mixed
    {
        if ($userId === null) {
            return null;
        }

        // Handle UUID strings - ensure they're properly formatted
        if (is_string($userId) && strlen($userId) === 36 && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $userId)) {
            return strtolower($userId); // Normalize UUID to lowercase
        }

        // Handle integer IDs
        if (is_numeric($userId)) {
            return (int) $userId;
        }

        // Handle other string IDs (custom formats)
        if (is_string($userId)) {
            return trim($userId);
        }

        // Handle objects with __toString method
        if (is_object($userId) && method_exists($userId, '__toString')) {
            return (string) $userId;
        }

        return $userId;
    }

    // ----------------- DATABASE CACHING METHODS -----------------

    /**
     * Enable caching for queries
     *
     * @param int|null $ttl Time to live in seconds
     * @param string|null $key Custom cache key
     * @param array $tags Cache tags for invalidation
     * @return $this
     */
    public function cache(?int $ttl = null, ?string $key = null, array $tags = []): static
    {
        $this->currentCacheSettings = [
            'enabled' => true,
            'ttl' => $ttl ?? $this->cacheConfig['ttl'],
            'key' => $key,
            'tags' => array_merge($this->cacheConfig['tags'], $tags)
        ];
        
        return $this;
    }

    /**
     * Disable caching for current query
     *
     * @return $this
     */
    public function noCache(): static
    {
        $this->currentCacheSettings['enabled'] = false;
        return $this;
    }

    /**
     * Remember query result for specified time
     *
     * @param int $ttl Time to live in seconds
     * @param string|null $key Custom cache key
     * @return $this
     */
    public function remember(int $ttl, ?string $key = null): static
    {
        return $this->cache($ttl, $key);
    }

    /**
     * Remember query result forever (until manually cleared)
     *
     * @param string|null $key Custom cache key
     * @return $this
     */
    public function rememberForever(?string $key = null): static
    {
        return $this->cache(0, $key); // 0 means no expiration
    }

    /**
     * Get cached result or execute query
     *
     * @param string $method Query method to execute
     * @param array $params Parameters for the method
     * @return mixed
     */
    protected function getCachedResult(string $method, array $params = []): mixed
    {
        // Check if caching is enabled globally and for this query
        if (!$this->cacheConfig['enabled'] || $this->currentCacheSettings['enabled'] === false) {
            return $this->executeFreshQuery($method, $params);
        }

        $cacheKey = $this->buildCacheKey($method, $params);
        
        // Try to get from cache
        $cachedResult = $this->getCacheValue($cacheKey);
        
        if ($cachedResult !== false) {
            $this->resetCacheSettings();
            return $this->unserializeCachedResult($cachedResult);
        }

        // Execute fresh query and cache result
        $result = $this->executeFreshQuery($method, $params);
        $this->setCacheValue($cacheKey, $result);
        
        $this->resetCacheSettings();
        return $result;
    }

    /**
     * Build cache key for query
     *
     * @param string $method
     * @param array $params
     * @return string
     */
    protected function buildCacheKey(string $method, array $params = []): string
    {
        // Use custom key if provided
        if ($this->currentCacheSettings['key']) {
            return $this->cacheConfig['prefix'] . $this->currentCacheSettings['key'];
        }

        // Build key from query components
        $keyComponents = [
            $this->table,
            $method,
            $this->db->last_query() ?: 'no_query',
            serialize($params),
            serialize($this->currentCacheSettings)
        ];

        $key = $this->cacheConfig['prefix'] . md5(implode('|', $keyComponents));
        
        return $key;
    }

    /**
     * Get value from cache
     *
     * @param string $key
     * @return mixed
     */
    protected function getCacheValue(string $key): mixed
    {
        if (!$this->cacheDriver) {
            return false;
        }

        return $this->cacheDriver->get($key);
    }

    /**
     * Set value in cache
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    protected function setCacheValue(string $key, mixed $value): bool
    {
        if (!$this->cacheDriver) {
            return false;
        }

        $ttl = $this->currentCacheSettings['ttl'] ?? $this->cacheConfig['ttl'];
        $serializedValue = $this->serializeForCache($value);
        
        // Store with TTL (0 means no expiration)
        if ($ttl > 0) {
            return $this->cacheDriver->save($key, $serializedValue, $ttl);
        } else {
            return $this->cacheDriver->save($key, $serializedValue);
        }
    }

    /**
     * Serialize data for cache storage
     *
     * @param mixed $value
     * @return array
     */
    protected function serializeForCache(mixed $value): array
    {
        return [
            'data' => serialize($value),
            'type' => gettype($value),
            'cached_at' => time(),
            'tags' => $this->currentCacheSettings['tags']
        ];
    }

    /**
     * Unserialize data from cache
     *
     * @param array $cachedData
     * @return mixed
     */
    protected function unserializeCachedResult(array $cachedData): mixed
    {
        return unserialize($cachedData['data']);
    }

    /**
     * Execute fresh query without cache
     *
     * @param string $method
     * @param array $params
     * @return mixed
     */
    protected function executeFreshQuery(string $method, array $params): mixed
    {
        return match($method) {
            'find' => $this->executeFreshFind($params),
            'findAll' => $this->executeFreshFindAll($params),
            'first' => $this->executeFreshFirst($params),
            'count' => $this->executeFreshCount($params),
            'get' => $this->executeFreshGet($params),
            default => null
        };
    }

    /**
     * Execute fresh find query
     */
    protected function executeFreshFind(array $params): mixed
    {
        $id = $params[0] ?? null;
        $originalMethod = 'parent::find';
        
        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }
        
        $row = $this->get($id, null, null);
        
        if ($row) {
            return $row[0];
        }
        
        return null;
    }

    /**
     * Execute fresh findAll query
     */
    protected function executeFreshFindAll(array $params): mixed
    {
        $idOrRow = $params[0] ?? null;
        $optionalValue = $params[1] ?? null;
        $orderBy = $params[2] ?? null;
        
        return $this->get($idOrRow, $optionalValue, $orderBy);
    }

    /**
     * Execute fresh first query
     */
    protected function executeFreshFirst(array $params): mixed
    {
        $this->db->limit(1);
        $rows = $this->findAll();
        
        if (is_array($rows) && count($rows) == 1) {
            return $rows[0];
        }
        
        return $rows;
    }

    /**
     * Execute fresh count query
     */
    protected function executeFreshCount(array $params): int
    {
        $column = $params[0] ?? '*';
        
        $this->db->from($this->table);
        
        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        return $this->db->count_all_results();
    }

    /**
     * Execute fresh get query
     */
    protected function executeFreshGet(array $params): mixed
    {
        $idOrRow = $params[0] ?? null;
        $optionalValue = $params[1] ?? null;
        $orderBy = $params[2] ?? null;
        
        // Custom order by if desired
        if ($orderBy != null) {
            $this->db->order_by($orderBy);
        }

        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        // Fetch all records for a table
        if ($idOrRow == null) {
            $query = $this->db->get($this->table);
        } elseif (is_array($idOrRow)) {
            $query = $this->db->get_where($this->table, $idOrRow);
        } else {
            if ($optionalValue == null) {
                $query = $this->db->get_where($this->table, [$this->primaryKey => $idOrRow]);
            } else {
                $query = $this->db->get_where($this->table, [$idOrRow => $optionalValue]);
            }
        }

        return $this->getResult($query);
    }

    /**
     * Reset cache settings to defaults
     */
    protected function resetCacheSettings(): void
    {
        $this->currentCacheSettings = [
            'enabled' => null,
            'ttl' => null,
            'key' => null,
            'tags' => []
        ];
    }

    /**
     * Clear cache for specific key or pattern
     *
     * @param string|null $key Specific key to clear (null clears all model cache)
     * @param array $tags Clear by tags
     * @return bool
     */
    public function clearCache(?string $key = null, array $tags = []): bool
    {
        if (!$this->cacheDriver) {
            return false;
        }

        if ($key) {
            // Clear specific key
            $fullKey = $this->cacheConfig['prefix'] . $key;
            return $this->cacheDriver->delete($fullKey);
        }

        if (!empty($tags)) {
            // Clear by tags (if supported by cache driver)
            return $this->clearCacheByTags($tags);
        }

        // Clear all cache for this model
        return $this->clearModelCache();
    }

    /**
     * Clear cache by tags
     *
     * @param array $tags
     * @return bool
     */
    protected function clearCacheByTags(array $tags): bool
    {
        // This is a simplified implementation
        // In production, you might want to maintain a tag index
        $pattern = $this->cacheConfig['prefix'] . '*';
        $keys = $this->getCacheKeys($pattern);
        
        foreach ($keys as $key) {
            $cached = $this->getCacheValue($key);
            if ($cached && isset($cached['tags'])) {
                if (array_intersect($tags, $cached['tags'])) {
                    $this->cacheDriver->delete($key);
                }
            }
        }
        
        return true;
    }

    /**
     * Clear all cache for this model
     *
     * @return bool
     */
    protected function clearModelCache(): bool
    {
        $pattern = $this->cacheConfig['prefix'] ?? '' . $this->table . '*';
        $keys = $this->getCacheKeys($pattern);
        
        foreach ($keys as $key) {
            $this->cacheDriver->delete($key);
        }
        
        return true;
    }

    /**
     * Get cache keys by pattern (driver-specific implementation)
     *
     * @param string $pattern
     * @return array
     */
    protected function getCacheKeys(string $pattern): array
    {
        // This is a simplified implementation
        // Different cache drivers handle key patterns differently
        $keys = [];
        
        switch ($this->cacheConfig['driver']) {
            case 'redis':
                // Redis supports KEYS command
                if (method_exists($this->cacheDriver, 'redis')) {
                    $redis = $this->cacheDriver->redis();
                    $keys = $redis->keys($pattern);
                }
                break;
                
            case 'file':
                // For file cache, scan cache directory
                $cacheDir = APPPATH . 'cache/';
                if (is_dir($cacheDir)) {
                    $files = glob($cacheDir . '*.cache');
                    foreach ($files as $file) {
                        if (fnmatch($pattern, basename($file, '.cache'))) {
                            $keys[] = basename($file, '.cache');
                        }
                    }
                }
                break;
                
            default:
                // For other drivers, we can't easily get all keys
                break;
        }
        
        return $keys;
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        $stats = [
            'driver' => $this->cacheConfig['driver'],
            'enabled' => $this->cacheConfig['enabled'],
            'prefix' => $this->cacheConfig['prefix'],
            'default_ttl' => $this->cacheConfig['ttl']
        ];

        if ($this->cacheDriver) {
            try {
                $info = $this->cacheDriver->cache_info();
                $stats['info'] = $info;
            } catch (\Exception $e) {
                $stats['info'] = 'Cache info not available';
            }
        }

        return $stats;
    }

    /**
     * Flush all cache
     *
     * @return bool
     */
    public function flushCache(): bool
    {
        if (!$this->cacheDriver) {
            return false;
        }

        return $this->cacheDriver->clean();
    }

    /**
     * Set cache configuration
     *
     * @param array $config
     * @return $this
     */
    public function setCacheConfig(array $config): static
    {
        $this->cacheConfig = array_merge($this->cacheConfig, $config);
        
        // Reinitialize cache driver if settings changed
        if (isset($config['enabled']) || isset($config['driver'])) {
            $this->initializeCacheDriver();
        }
        
        return $this;
    }

    /**
     * Enable database result caching
     *
     * @param int $ttl Time to live in seconds
     * @param string $driver Cache driver to use
     * @return $this
     */
    public function enableCache(int $ttl = 3600, string $driver = 'file'): static
    {
        $this->cacheConfig['enabled'] = true;
        $this->cacheConfig['ttl'] = $ttl;
        $this->cacheConfig['driver'] = $driver;
        
        $this->initializeCacheDriver();
        
        return $this;
    }

    /**
     * Disable database result caching
     *
     * @return $this
     */
    public function disableCache(): static
    {
        $this->cacheConfig['enabled'] = false;
        return $this;
    }

    // -------------------- ELOQUENT-LIKE FEATURES ---------------------------

    /**
     * Create a new model instance
     *
     * @param array $attributes
     * @return static
     */
    public static function make(array $attributes = []): static
    {
        $instance = new static();
        return $instance->fill($attributes);
    }

    /**
     * Fill the model with an array of attributes
     *
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    /**
     * Save the model to the database
     *
     * @param array $options
     * @return bool
     */
    public function save(array $options = []): bool
    {
        // If model exists, update it
        if ($this->exists) {
            return $this->performUpdate();
        }
        
        // Otherwise, create a new record
        return $this->performInsert();
    }

    /**
     * Perform a model insert operation
     *
     * @return bool
     */
    protected function performInsert(): bool
    {
        $data = $this->getAttributesForInsert();
        
        // Fire creating event
        $data = $this->creating($data);
        
        // Add created timestamp and user tracking
        $currentUserId = $this->trackUser ? $this->getCurrentUserId() : null;

        if ($this->trackUser) {
            $data[$this->createdBy] = $currentUserId;
        }

        // Add timestamps if enabled
        if ($this->timestamps) {
            
            $timestamp = $this->getCurrentTimestamp();
            
            if ( ! isset($data[$this->createdAt])) {
                $data[$this->createdAt] = $timestamp;
            }
            
            if ( ! isset($data[$this->updatedAt])) {
                $data[$this->updatedAt] = $timestamp;
            }
        }

        // Insert the record
        $result = $this->db->insert($this->table, $data);
        
        if ($result) {
            $insertId = $this->db->insert_id();
            
            // Set the primary key
            $this->setAttribute($this->primaryKey, $insertId);
            
            // Mark as existing and recently created
            $this->exists = true;
            $this->wasRecentlyCreated = true;
            
            // Update original attributes
            $this->syncOriginal();
            
            // Clear related cache
            $this->clearModelCacheAfterWrite();
            
            // Fire created event
            $this->created($this);
            
            return true;
        }
        
        return false;
    }

    /**
     * Perform a model update operation
     *
     * @return bool
     */
    protected function performUpdate(): bool
    {
        $changed = $this->getChangedAttributes();
        
        if (empty($changed)) {
            return true; // No changes to save
        }
        
        // Fire updating event
        $changed = $this->updating($changed);
        
        // Add updated timestamp and user tracking
        $currentUserId = $this->trackUser ? $this->getCurrentUserId() : null;
        
        if ($this->timestamps && !isset($changed[$this->updatedAt])) {
            $changed[$this->updatedAt] = $this->getCurrentTimestamp();
        }
        
        if ($this->trackUser && $currentUserId && !isset($changed[$this->updatedBy])) {
            $changed[$this->updatedBy] = $currentUserId;
        }
        
        // Convert values for database storage
        foreach ($changed as $key => $value) {
            $changed[$key] = $this->getValueForDatabase($key, $value);
        }
        
        // Update the record
        $primaryKeyValue = $this->getAttribute($this->primaryKey);
        $this->db->where($this->primaryKey, $primaryKeyValue);
        $result = $this->db->update($this->table, $changed);
        
        if ($result) {
            // Update attributes with new values
            foreach ($changed as $key => $value) {
                $this->setAttribute($key, $value);
            }
            
            // Sync original attributes
            $this->syncOriginal();
            
            // Clear related cache
            $this->clearModelCacheAfterWrite();
            
            // Fire updated event
            $this->updated($this);
            
            return true;
        }
        
        return false;
    }

    /**
     * Clear model cache after write operations
     *
     * @return void
     */
    protected function clearModelCacheAfterWrite(): void
    {
        if (!$this->cacheConfig['enabled']) {
            return;
        }

        // Clear all cache for this model table
        $this->clearModelCache();
        
        // Also clear any tagged cache
        if (!empty($this->cacheConfig['tags'])) {
            $this->clearCacheByTags($this->cacheConfig['tags']);
        }
    }

    /**
     * Delete the model from the database
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        $primaryKeyValue = $this->getAttribute($this->primaryKey);
        
        // Fire deleting event
        if (!$this->deleting($primaryKeyValue)) {
            return false;
        }
        
        // Perform soft delete if enabled
        if ($this->useSoftDelete) {
            $result = $this->performSoftDelete();
        } else {
            // Hard delete
            $this->db->where($this->primaryKey, $primaryKeyValue);
            $result = $this->db->delete($this->table);
        }
        
        if ($result) {
            $this->exists = false;
            
            // Fire deleted event
            $this->deleted($this);
            
            return true;
        }
        
        return false;
    }

    /**
     * Perform soft delete
     *
     * @return bool
     */
    protected function performSoftDelete(): bool
    {
        $data = [
            $this->useSoftDeleteKey => $this->getCurrentTimestamp()
        ];
        
        // Add deleted_by tracking if user tracking is enabled
        if ($this->trackUser) {
            $currentUserId = $this->getCurrentUserId();
            if ($currentUserId) {
                $data[$this->deletedBy] = $currentUserId;
            }
        }
        
        $primaryKeyValue = $this->getAttribute($this->primaryKey);
        $this->db->where($this->primaryKey, $primaryKeyValue);
        
        return $this->db->update($this->table, $data);
    }

    /**
     * Refresh the model with fresh data from database
     *
     * @return $this|null
     */
    public function fresh(): ?static
    {
        if (!$this->exists) {
            return null;
        }
        
        $primaryKeyValue = $this->getAttribute($this->primaryKey);
        $fresh = static::find($primaryKeyValue);
        
        if ($fresh) {
            $this->attributes = $fresh->attributes;
            $this->syncOriginal();
        }
        
        return $this;
    }

    /**
     * Reload the current model instance with fresh attributes from the database
     *
     * @return $this
     */
    public function refresh(): static
    {
        if (!$this->exists) {
            return $this;
        }
        
        $primaryKeyValue = $this->getAttribute($this->primaryKey);
        $this->db->where($this->primaryKey, $primaryKeyValue);
        $query = $this->db->get($this->table);
        
        if ($query->num_rows() > 0) {
            $data = $query->row_array();
            $this->attributes = [];
            $this->fill($data);
            $this->syncOriginal();
        }
        
        return $this;
    }

    // ----------------- ATTRIBUTE METHODS -----------------

    /**
     * Get attributes for insert operation
     *
     * @return array
     */
    protected function getAttributesForInsert(): array
    {
        $attributes = [];
        
        foreach ($this->attributes as $key => $value) {
            // Skip primary key if it's auto-increment and empty
            if ($key === $this->primaryKey && empty($value)) {
                continue;
            }
            
            // Convert value for database storage
            $attributes[$key] = $this->getValueForDatabase($key, $value);
        }
        
        return $attributes;
    }

    /**
     * Get the attributes that have been changed since last sync
     *
     * @return array
     */
    public function getChangedAttributes(): array
    {
        $changed = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $changed[$key] = $value;
            }
        }
        
        return $changed;
    }

    /**
     * Determine if the model or any of the 
     * given attribute(s) have been modified
     *
     * @param array|string|null $attributes
     * @return bool
     */
    public function hasChanges($attributes = null): bool
    {
        $changed = $this->getChangedAttributes();
        
        if (is_null($attributes)) {
            return count($changed) > 0;
        }
        
        $attributes = is_array($attributes) ? $attributes : func_get_args();
        
        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $changed)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Determine if the model and all 
     * the given attribute(s) are clean
     *
     * @param array|string|null $attributes
     * @return bool
     */
    public function isClean($attributes = null): bool
    {
        return !$this->hasChanges(...func_get_args());
    }

    /**
     * Sync the original attributes with the current
     *
     * @return $this
     */
    public function syncOriginal(): static
    {
        $this->original = $this->attributes;
        return $this;
    }

    /**
     * Get the model's original attribute values
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getOriginal(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->original;
        }
        
        return $this->original[$key] ?? $default;
    }

    /**
     * Check if user tracking is enabled
     *
     * @return bool
     */
    public function hasUserTracking(): bool
    {
        return $this->trackUser;
    }

    /**
     * Get user tracking column names
     *
     * @return array
     */
    public function getUserTrackingColumns(): array
    {
        return [
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'deleted_by' => $this->deletedBy
        ];
    }

    /**
     * Enable user tracking
     *
     * @return $this
     */
    public function enableUserTracking(): static
    {
        $this->trackUser = true;
        return $this;
    }

    /**
     * Disable user tracking
     *
     * @return $this
     */
    public function disableUserTracking(): static
    {
        $this->trackUser = false;
        return $this;
    }

    /**
     * Get timestamp column names
     *
     * @return array
     */
    public function getTimestampColumns(): array
    {
        return [
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->useSoftDeleteKey
        ];
    }

    /**
     * Set custom user tracking column names
     *
     * @param array $columns
     * @return $this
     */
    public function setUserTrackingColumns(array $columns): static
    {
        if (isset($columns['created_by'])) {
            $this->createdBy = $columns['created_by'];
        }
        if (isset($columns['updated_by'])) {
            $this->updatedBy = $columns['updated_by'];
        }
        if (isset($columns['deleted_by'])) {
            $this->deletedBy = $columns['deleted_by'];
        }
        
        return $this;
    }

    /**
     * Get all audit trail columns (timestamps + user tracking)
     *
     * @return array
     */
    public function getAuditColumns(): array
    {
        $columns = $this->getTimestampColumns();
        
        if ($this->trackUser) {
            $columns = array_merge($columns, $this->getUserTrackingColumns());
        }
        
        return $columns;
    }

    /**
     * Filter out fillable fields
     *
     * @param array $data
     * @return mixed
     */
    protected function filterFillable($data)
    {
        if ($this->fillable) {
            $data = array_intersect_key($data, array_flip($this->fillable)); 
        }

        return $data;
    }

    /**
     * Filter out hidden fields
     *
     * @param array|object $data
     * @return mixed
     */
    public function filterHidden(array|object $data): array|object
    {
        // If data is a single object, filter its properties.
        if (is_object($data)) {
            foreach ($this->hidden as $property) {
                if (property_exists($data, $property)) {
                    unset($data->$property);
                }
            }
            return $data;
        }

        // If data is an array, filter each item.
        if (is_array($data)) {
            foreach ($data as $key => $item) {
                $data[$key] = $this->filterHidden($item);
            }
            return $data;
        }

        // Return other types as-is.
        return $data;
    }

    /**
     * Determine if the given attribute may be mass assigned
     *
     * @param string $key
     * @return bool
     */
    public function isFillable(string $key): bool
    {
        if (in_array($key, $this->fillable)) {
            return true;
        }

        if ($this->isGuarded($key)) {
            return false;
        }

        return empty($this->fillable) && !str_starts_with($key, '_');
    }

    /**
     * Determine if the given key is guarded
     *
     * @param string $key
     * @return bool
     */
    public function isGuarded(string $key): bool
    {
        return in_array($key, $this->guarded) || $this->guarded === ['*'];
    }

    /**
     * Set a given attribute on the model
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute(string $key, mixed $value): static
    {
        // Check for mutator
        if ($this->hasSetMutator($key)) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
            return $this->{$method}($value);
        }

        // Cast the value
        $value = $this->castAttribute($key, $value);

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get an attribute from the model
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key): mixed
    {
        if (!$key) {
            return null;
        }

        // Check for accessor
        if ($this->hasGetMutator($key)) {
            $method = 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
            return $this->{$method}($this->attributes[$key] ?? null);
        }

        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }

        return null;
    }

    /**
     * Determine if a get mutator exists for an attribute
     *
     * @param string $key
     * @return bool
     */
    public function hasGetMutator(string $key): bool
    {
        return method_exists($this, 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute');
    }

    /**
     * Determine if a set mutator exists for an attribute
     *
     * @param string $key
     * @return bool
     */
    public function hasSetMutator(string $key): bool
    {
        return method_exists($this, 'set' . str_replace('_', '', ucwords($key, '_')) . 'Attribute');
    }

    /**
     * Cast an attribute to a native PHP type
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function castAttribute(string $key, mixed $value): mixed
    {
        if (is_null($value)) {
            return $value;
        }

        if (!array_key_exists($key, $this->casts)) {
            return $value;
        }

        $cast = $this->casts[$key];

        // Handle array-based casting (with parameters)
        if (is_array($cast)) {
            return $this->castWithParameters($key, $value, $cast);
        }

        // Handle class-based casting
        if (class_exists($cast)) {
            return $this->castToClass($key, $value, $cast);
        }

        // Handle custom cast handlers
        if (isset(static::$customCastHandlers[$cast])) {
            return call_user_func(static::$customCastHandlers[$cast], $value, $key, $this);
        }

        // Handle built-in casts
        return match ($cast) {
            'int', 'integer' => (int) $value,
            'real', 'float', 'double' => (float) $value,
            'decimal' => number_format((float) $value, 2),
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'object' => json_decode($value, false),
            'array', 'json' => json_decode($value, true),
            'collection' => arrayz(json_decode($value, true)),
            'date' => new DateTime($value),
            'datetime' => new DateTime($value),
            'timestamp' => (int) strtotime($value),
            'enum' => $this->castToEnum($value, $cast),
            'encrypted' => $this->decryptValue($value),
            default => $value,
        };
    }

    /**
     * Cast attribute with parameters
     *
     * @param string $key
     * @param mixed $value
     * @param array $castConfig
     * @return mixed
     */
    protected function castWithParameters(string $key, mixed $value, array $castConfig): mixed
    {
        $castType = $castConfig[0];
        $parameters = array_slice($castConfig, 1);

        return match ($castType) {
            'enum' => $this->castToEnum($value, $parameters[0] ?? null),
            'dto' => $this->castToDto($value, $parameters[0] ?? null, $parameters[1] ?? []),
            'collection' => $this->castToCollection($value, $parameters[0] ?? null),
            'decimal' => number_format((float) $value, $parameters[0] ?? 2),
            'date' => $this->castToDate($value, $parameters[0] ?? 'Y-m-d H:i:s'),
            'encrypted' => $this->decryptValue($value, $parameters[0] ?? null),
            'hashed' => $this->castToHashed($value, $parameters[0] ?? 'bcrypt'),
            'serialized' => unserialize($value),
            'base64' => base64_decode($value),
            'url' => filter_var($value, FILTER_VALIDATE_URL) ? $value : null,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null,
            'phone' => $this->castToPhone($value, $parameters[0] ?? null),
            'money' => $this->castToMoney($value, $parameters[0] ?? 'USD'),
            'coordinates' => $this->castToCoordinates($value),
            'address' => $this->castToAddress($value),
            default => class_exists($castType) ? $this->castToClass($key, $value, $castType, $parameters) : $value,
        };
    }

    /**
     * Cast to a specific class
     *
     * @param string $key
     * @param mixed $value
     * @param string $class
     * @param array $parameters
     * @return mixed
     */
    protected function castToClass(string $key, mixed $value, string $class, array $parameters = []): mixed
    {
        // Handle enum classes
        if (enum_exists($class)) {
            return $this->castToEnum($value, $class);
        }

        // Handle classes with fromDatabase method
        if (method_exists($class, 'fromDatabase')) {
            return $class::fromDatabase($value, ...$parameters);
        }

        // Handle classes with constructor that accepts value
        if (method_exists($class, '__construct')) {
            $reflection = new \ReflectionClass($class);
            $constructor = $reflection->getConstructor();
            
            if ($constructor && $constructor->getNumberOfRequiredParameters() <= 1) {
                return new $class($value, ...$parameters);
            }
        }

        // Handle DTOs/Value Objects with static factory methods
        if (method_exists($class, 'make') || method_exists($class, 'create')) {
            $method = method_exists($class, 'make') ? 'make' : 'create';
            return $class::$method($value, ...$parameters);
        }

        // Handle serializable objects
        if (is_string($value)) {
            $data = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $this->hydrateObject($class, $data);
            }
        }

        return $value;
    }

    /**
     * Cast to enum
     *
     * @param mixed $value
     * @param string|null $enumClass
     * @return mixed
     */
    protected function castToEnum(mixed $value, ?string $enumClass = null): mixed
    {
        if (!$enumClass || !enum_exists($enumClass)) {
            return $value;
        }

        // Handle backed enums
        if (method_exists($enumClass, 'from')) {
            try {
                return $enumClass::from($value);
            } catch (\ValueError) {
                return $enumClass::tryFrom($value);
            }
        }

        // Handle unit enums
        foreach ($enumClass::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        return $value;
    }

    /**
     * Cast to DTO
     *
     * @param mixed $value
     * @param string|null $dtoClass
     * @param array $options
     * @return mixed
     */
    protected function castToDto(mixed $value, ?string $dtoClass = null, array $options = []): mixed
    {
        if (!$dtoClass || !class_exists($dtoClass)) {
            return $value;
        }

        if (is_string($value)) {
            $data = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $this->hydrateObject($dtoClass, $data, $options);
            }
        }

        if (is_array($value)) {
            return $this->hydrateObject($dtoClass, $value, $options);
        }

        return $value;
    }

    /**
     * Cast to collection
     *
     * @param mixed $value
     * @param string|null $itemType
     * @return mixed
     */
    protected function castToCollection(mixed $value, ?string $itemType = null): mixed
    {
        if (is_string($value)) {
            $data = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return arrayz([]);
            }
            $value = $data;
        }

        if (!is_array($value)) {
            return arrayz([]);
        }

        // If item type is specified, cast each item
        if ($itemType && class_exists($itemType)) {
            $items = array_map(function ($item) use ($itemType) {
                return $this->castToClass('', $item, $itemType);
            }, $value);
            return arrayz($items);
        }

        return arrayz($value);
    }

    /**
     * Cast to date with custom format
     *
     * @param mixed $value
     * @param string $format
     * @return DateTime|null
     */
    protected function castToDate(mixed $value, string $format = 'Y-m-d H:i:s'): ?DateTime
    {
        if (empty($value)) {
            return null;
        }

        try {
            return DateTime::createFromFormat($format, $value) ?: new DateTime($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Cast to money value
     *
     * @param mixed $value
     * @param string $currency
     * @return array
     */
    protected function castToMoney(mixed $value, string $currency = 'USD'): array
    {
        return [
            'amount' => (float) $value,
            'currency' => $currency,
            'formatted' => number_format((float) $value, 2) . ' ' . $currency
        ];
    }

    /**
     * Cast to coordinates
     *
     * @param mixed $value
     * @return array|null
     */
    protected function castToCoordinates(mixed $value): ?array
    {
        if (is_string($value)) {
            $data = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $value = $data;
            } else {
                // Handle "lat,lng" format
                $parts = explode(',', $value);
                if (count($parts) === 2) {
                    $value = ['lat' => (float) trim($parts[0]), 'lng' => (float) trim($parts[1])];
                }
            }
        }

        if (is_array($value) && isset($value['lat'], $value['lng'])) {
            return [
                'lat' => (float) $value['lat'],
                'lng' => (float) $value['lng'],
                'formatted' => $value['lat'] . ', ' . $value['lng']
            ];
        }

        return null;
    }

    /**
     * Cast to address object
     *
     * @param mixed $value
     * @return array|null
     */
    protected function castToAddress(mixed $value): ?array
    {
        if (is_string($value)) {
            $data = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $data;
            }
        }

        if (is_array($value)) {
            return [
                'street' => $value['street'] ?? '',
                'city' => $value['city'] ?? '',
                'state' => $value['state'] ?? '',
                'zip' => $value['zip'] ?? '',
                'country' => $value['country'] ?? '',
                'formatted' => $this->formatAddress($value)
            ];
        }

        return null;
    }

    /**
     * Cast to phone number
     *
     * @param mixed $value
     * @param string|null $format
     * @return array
     */
    protected function castToPhone(mixed $value, ?string $format = null): array
    {
        $cleaned = preg_replace('/[^0-9]/', '', $value);
        
        return [
            'raw' => (string) $value,
            'cleaned' => $cleaned,
            'formatted' => $this->formatPhone($cleaned, $format)
        ];
    }

    /**
     * Cast to hashed value
     *
     * @param mixed $value
     * @param string $algorithm
     * @return string
     */
    protected function castToHashed(mixed $value, string $algorithm = 'bcrypt'): string
    {
        return match ($algorithm) {
            'bcrypt' => password_hash($value, PASSWORD_BCRYPT),
            'md5' => md5($value),
            'sha1' => sha1($value),
            'sha256' => hash('sha256', $value),
            default => (string) $value,
        };
    }

    /**
     * Decrypt value
     *
     * @param mixed $value
     * @param string|null $key
     * @return string
     */
    protected function decryptValue(mixed $value, ?string $key = null): string
    {
        // Placeholder for encryption/decryption logic
        // You would implement your preferred encryption method here
        return base64_decode($value);
    }

    /**
     * Hydrate object from array data
     *
     * @param string $class
     * @param array $data
     * @param array $options
     * @return mixed
     */
    protected function hydrateObject(string $class, array $data, array $options = []): mixed
    {
        $reflection = new \ReflectionClass($class);
        
        // Try constructor injection
        $constructor = $reflection->getConstructor();
        if ($constructor) {
            $parameters = $constructor->getParameters();
            $args = [];
            
            foreach ($parameters as $param) {
                $paramName = $param->getName();
                if (array_key_exists($paramName, $data)) {
                    $args[] = $data[$paramName];
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    $args[] = null;
                }
            }
            
            $instance = $reflection->newInstanceArgs($args);
        } else {
            $instance = $reflection->newInstance();
        }

        // Set properties
        foreach ($data as $property => $value) {
            if ($reflection->hasProperty($property)) {
                $prop = $reflection->getProperty($property);
                if ($prop->isPublic()) {
                    $prop->setValue($instance, $value);
                }
            }
        }

        return $instance;
    }

    /**
     * Format address as string
     *
     * @param array $address
     * @return string
     */
    protected function formatAddress(array $address): string
    {
        $parts = array_filter([
            $address['street'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['zip'] ?? '',
            $address['country'] ?? ''
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Format phone number
     *
     * @param string $number
     * @param string|null $format
     * @return string
     */
    protected function formatPhone(string $number, ?string $format = null): string
    {
        if (strlen($number) === 10) {
            return sprintf('%s-%s-%s', 
                substr($number, 0, 3),
                substr($number, 3, 3),
                substr($number, 6, 4)
            );
        }
        
        return $number;
    }

    /**
     * Register a custom cast handler
     *
     * @param string $type
     * @param callable $handler
     * @return void
     */
    public static function registerCastHandler(string $type, callable $handler): void
    {
        static::$customCastHandlers[$type] = $handler;
    }

    /**
     * Get value for database storage (reverse casting)
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function getValueForDatabase(string $key, mixed $value): mixed
    {
        if (is_null($value)) {
            return $value;
        }

        if (!array_key_exists($key, $this->casts)) {
            return $value;
        }

        $cast = $this->casts[$key];

        // Handle array-based casting
        if (is_array($cast)) {
            $castType = $cast[0];
            return $this->convertForDatabase($value, $castType);
        }

        // Handle class-based casting
        if (class_exists($cast)) {
            return $this->convertObjectForDatabase($value);
        }

        // Handle built-in types
        return match ($cast) {
            'array', 'json', 'object' => json_encode($value),
            'collection' => json_encode($value->toArray()),
            'date', 'datetime' => $value instanceof DateTime ? $value->format('Y-m-d H:i:s') : $value,
            'encrypted' => $this->encryptValue($value),
            'serialized' => serialize($value),
            'base64' => base64_encode($value),
            default => $value,
        };
    }

    /**
     * Convert value for database storage
     *
     * @param mixed $value
     * @param string $castType
     * @return mixed
     */
    protected function convertForDatabase(mixed $value, string $castType): mixed
    {
        return match ($castType) {
            'enum' => $value instanceof \BackedEnum ? $value->value : (string) $value,
            'dto', 'coordinates', 'address' => json_encode($value),
            'collection' => json_encode(is_object($value) && method_exists($value, 'toArray') ? $value->toArray() : $value),
            'money' => is_array($value) ? $value['amount'] : $value,
            'phone' => is_array($value) ? $value['raw'] : $value,
            default => $value,
        };
    }

    /**
     * Convert object for database storage
     *
     * @param mixed $value
     * @return mixed
     */
    protected function convertObjectForDatabase(mixed $value): mixed
    {
        if (method_exists($value, 'toDatabase')) {
            return $value->toDatabase();
        }

        if (method_exists($value, 'toArray')) {
            return json_encode($value->toArray());
        }

        if (method_exists($value, 'toJson')) {
            return $value->toJson();
        }

        if ($value instanceof \JsonSerializable) {
            return json_encode($value);
        }

        return json_encode($value);
    }

    /**
     * Encrypt value for storage
     *
     * @param mixed $value
     * @return string
     */
    protected function encryptValue(mixed $value): string
    {
        // Placeholder for encryption logic
        return base64_encode($value);
    }

    // ----------------- RELATIONSHIP METHODS -----------------

    /**
     * Define a one-to-one relationship
     *
     * @param string|object $related
     * @param string $foreignKey
     * @param string $localKey
     * @return array
     */
    public function hasOne(string|object $related, ?string $foreignKey = null, ?string $localKey = null): array
    {
        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey = $localKey ?? $this->getKeyName();

        return [
            'type' => 'hasOne',
            'related' => $related,
            'foreign_key' => $foreignKey,
            'local_key' => $localKey
        ];
    }

    /**
     * Define a one-to-many relationship
     *
     * @param string|object $related
     * @param string $foreignKey
     * @param string $localKey
     * @return array
     */
    public function hasMany(string|object $related, ?string $foreignKey = null, ?string $localKey = null): array
    {
        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey = $localKey ?? $this->getKeyName();

        return [
            'type' => 'hasMany',
            'related' => $related,
            'foreign_key' => $foreignKey,
            'local_key' => $localKey
        ];
    }

    /**
     * Define an inverse one-to-one or one-to-many relationship
     *
     * @param string|object $related
     * @param string $foreignKey
     * @param string $ownerKey
     * @return array
     */
    public function belongsTo(string|object $related, ?string $foreignKey = null, ?string $ownerKey = null): array
    {
        $foreignKey = $foreignKey ?? $this->getRelatedForeignKey($related);
        $ownerKey = $ownerKey ?? 'id';

        return [
            'type' => 'belongsTo',
            'related' => $related,
            'foreign_key' => $foreignKey,
            'owner_key' => $ownerKey
        ];
    }

    /**
     * Define a many-to-many relationship
     *
     * @param string|object $related
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @return array
     */
    public function belongsToMany(string|object $related, ?string $table = null, ?string $foreignPivotKey = null, ?string $relatedPivotKey = null): array
    {
        $table = $table ?? $this->joiningTable($related);
        $foreignPivotKey = $foreignPivotKey ?? $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?? $this->getRelatedForeignKey($related);

        return [
            'type' => 'belongsToMany',
            'related' => $related,
            'table' => $table,
            'foreign_pivot_key' => $foreignPivotKey,
            'related_pivot_key' => $relatedPivotKey
        ];
    }

    /**
     * Load a relationship
     *
     * @param string $relation
     * @param mixed $data
     * @return mixed
     */
    public function with(string $relation, mixed $data = null): mixed
    {
        if (!method_exists($this, $relation)) {
            throw new \Exception("Relationship {$relation} does not exist");
        }

        $relationship = $this->{$relation}();
        
        if (!$data) {
            return $this;
        }

        return $this->loadRelationship($relationship, $data);
    }

    /**
     * Load relationship data
     *
     * @param array $relationship
     * @param mixed $data
     * @return mixed
     */
    protected function loadRelationship(array $relationship, mixed $data): mixed
    {
        $relatedModel = new $relationship['related']();
        
        return match ($relationship['type']) {
            'hasOne' => $relatedModel->where($relationship['foreign_key'], $data[$relationship['local_key']])->first(),
            'hasMany' => $relatedModel->where($relationship['foreign_key'], $data[$relationship['local_key']])->findAll(),
            'belongsTo' => $relatedModel->where($relationship['owner_key'], $data[$relationship['foreign_key']])->first(),
            'belongsToMany' => $this->loadBelongsToMany($relationship, $data),
            default => null,
        };
    }

    /**
     * Load belongs to many relationship
     *
     * @param array $relationship
     * @param mixed $data
     * @return mixed
     */
    protected function loadBelongsToMany(array $relationship, mixed $data): mixed
    {
        $this->db->select($relationship['related'] . '.*')
                 ->from($relationship['table'])
                 ->join($relationship['related'], $relationship['table'] . '.' . $relationship['related_pivot_key'] . ' = ' . $relationship['related'] . '.id')
                 ->where($relationship['table'] . '.' . $relationship['foreign_pivot_key'], $data[$this->primaryKey]);

        return $this->getResult($this->db->get());
    }

    /**
     * Get foreign key for relationship
     *
     * @return string
     */
    protected function getForeignKey(): string
    {
        return strtolower(class_basename(get_class($this))) . '_id';
    }

    /**
     * Get related foreign key
     *
     * @param string $related
     * @return string
     */
    protected function getRelatedForeignKey(string $related): string
    {
        return strtolower(class_basename($related)) . '_id';
    }

    /**
     * Get joining table name for many-to-many
     *
     * @param string $related
     * @return string
     */
    protected function joiningTable(string $related): string
    {
        $models = [
            strtolower(class_basename(get_class($this))),
            strtolower(class_basename($related))
        ];

        sort($models);

        return implode('_', $models);
    }

    // ----------------- QUERY SCOPES -----------------

    /**
     * Apply a scope to the query
     *
     * @param string $scope
     * @param mixed ...$parameters
     * @return $this
     */
    public function scope(string $scope, mixed ...$parameters): static
    {
        $method = 'scope' . ucfirst($scope);
        
        if (method_exists($this, $method)) {
            $this->{$method}(...$parameters);
        }

        return $this;
    }

    /**
     * Add a global scope
     *
     * @param string $scope
     * @return $this
     */
    public function addGlobalScope(string $scope): static
    {
        if (!in_array($scope, $this->globalScopes)) {
            $this->globalScopes[] = $scope;
        }

        return $this;
    }

    /**
     * Remove a global scope
     *
     * @param string $scope
     * @return $this
     */
    public function removeGlobalScope(string $scope): static
    {
        $this->globalScopes = array_filter($this->globalScopes, function($s) use ($scope) {
            return $s !== $scope;
        });

        return $this;
    }

    /**
     * Apply the soft delete scope
     */
    public function scopeWithoutSoftDeletes(): void
    {
        if ($this->useSoftDelete) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }
    }

    /**
     * Include soft deleted records
     *
     * @return $this
     */
    public function withTrashed(): static
    {
        $this->temporaryWithDeleted = true;
        return $this;
    }

    /**
     * Only soft deleted records
     *
     * @return $this
     */
    public function onlyTrashed(): static
    {
        $this->temporaryOnlyDeleted = true;
        return $this;
    }

    // ----------------- STATIC METHODS -----------------

    /**
     * Create a new instance and save it to database
     *
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes): static
    {
        $instance = new static();

        $userId = $instance->getCurrentUserId();

        if ($userId !== null) {
            $instance->asUser($userId);
        }

        $instance->fill($attributes);
        $instance->save();
        return $instance;
    }

    /**
     * Find a model by its primary key
     *
     * @param mixed $id
     * @return static|null
     */
    public static function find(mixed $id): ?static
    {
        $instance = new static();
        $instance->db->where($instance->primaryKey, $id);
        
        if ($instance->useSoftDelete) {
            $instance->db->where($instance->useSoftDeleteKey, $instance->softDeleteFalseValue);
        }
        
        $query = $instance->db->get($instance->table);
        
        if ($query->num_rows() > 0) {
            $data = $query->row_array();
            $instance->attributes = $data;
            $model = new static($data);
            $model->exists = true;
            $model->attributes = $data;
            $model->wasRecentlyCreated = false;
            $model->syncOriginal();
            // return $model;
            return $model;
        }
        
        return null;
    }

    /**
     * Find a model by its primary key or throw an exception
     *
     * @param mixed $id
     * @return static
     * @throws \Exception
     */
    public static function findOrFail(mixed $id): static
    {
        $result = static::find($id);

        if (!$result) {
            throw new \Exception("No record found with ID: {$id}");
        }

        return $result;
    }

    /**
     * First or create - find first record or create new one
     *
     * @param array $attributes
     * @param array $values
     * @return static
     */
    public static function firstOrCreate(array $attributes, array $values = []): static
    {
        $instance = new static();
        
        // Build where clause
        foreach ($attributes as $key => $value) {
            $instance->db->where($key, $value);
        }
        
        if ($instance->useSoftDelete) {
            $instance->db->where($instance->useSoftDeleteKey, $instance->softDeleteFalseValue);
        }
        
        $query = $instance->db->get($instance->table);
        
        if ($query->num_rows() > 0) {
            $data = $query->row_array();
            $model = new static($data);
            $model->exists = true;
            $model->wasRecentlyCreated = false;
            $model->syncOriginal();
            return $model;
        }

        return static::create(array_merge($attributes, $values));
    }

    /**
     * Update or create - update existing record or create new one
     *
     * @param array $attributes
     * @param array $values
     * @return static
     */
    public static function updateOrCreate(array $attributes, array $values = []): static
    {
        $instance = static::firstOrNew($attributes);
        $instance->fill($values);
        $instance->save();
        return $instance;
    }

    /**
     * Get the first record matching the attributes or instantiate it
     *
     * @param array $attributes
     * @param array $values
     * @return static
     */
    public static function firstOrNew(array $attributes, array $values = []): static
    {
        $instance = new static();
        
        // Build where clause
        foreach ($attributes as $key => $value) {
            $instance->db->where($key, $value);
        }
        
        if ($instance->useSoftDelete) {
            $instance->db->where($instance->useSoftDeleteKey, $instance->softDeleteFalseValue);
        }
        
        $query = $instance->db->get($instance->table);
        
        if ($query->num_rows() > 0) {
            $data = $query->row_array();
            $model = new static($data);
            $model->exists = true;
            $model->wasRecentlyCreated = false;
            $model->syncOriginal();
            return $model;
        }

        $model = new static();
        $model->fill(array_merge($attributes, $values));
        return $model;
    }

    // ----------------- ENHANCED CRUD OPERATIONS -----------------

    /**
     * Create a new record with automatic timestamps
     *
     * @param array $data
     * @return object|array
     */
    public function createBy(array $data): object|array
    {
        $data = $this->addTimestamps($data);
        
        $this->db->insert($this->table, $data);
        $insertId = $this->db->insert_id();
        
        $result = $this->find($insertId);

        if (!empty($this->protected)) {
            return $this->protectFields($result);
        }

        return $result;
    }

        /**
     * Update the model in the database
     *
     * @param array $attributes
     * @param array $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
        
        return $this->save($options);
    }

    /**
     * Update a record
     *
     * @usage   updateBy('field_name', field_value, data);
     *          updateBy('field_name', field_value, ['username' => 'akonic']);
     * 
     *          updateBy(integer(PK), ['username' => 'akonic']);
     *          
     *          ->set(data)->updateBy(PK)
     *          $this->model->set('last_name', 'Bonsu')->updateBy([
     *	        	'username' => 'akonic'
     *	        ]);
     *
     * @param  mixed  $idOrRow
     * @param  mixed  $optionalValue (Optional)
     * @param  array  $data
     * @param  array  $attributes key/value pair to update
     *
     * @return bool result
     */
    public function updateBy($idOrRow = null, $optionalValue = null, $attributes = [])
    {

        // if (!empty($idOrRow)) {
        //     static::fill($idOrRow);
        // }
// dd($idOrRow, $optionalValue, $attributes);
        // if (is_array($idOrRow) || is_array($optionalValue)) {
            
        //     if (!empty($attributes)) {
        //         static::fill($attributes);
        //     }
        //     dd('ss');
        //     return static::save($optionalValue);
        // }
// dd('ss');
        if ($optionalValue == null) {
            if (is_array($idOrRow)) {
                $this->db->where($idOrRow);
            } else {
                $this->db->where([$this->primaryKey => $idOrRow]);
            }
        } else {
            if (is_array($optionalValue)) {
                $this->db->where([$this->primaryKey => $idOrRow]);
                $data = $this->filterFillable($optionalValue);
                return $this->db->update($this->table, $data);
            } else {
                 $this->db->where([$idOrRow => $optionalValue]);
            }
        }

        $attributes = $this->filterFillable($attributes);

        if (is_null($idOrRow) && empty($attributes)) {
            return $this->db->update($this->table);  
        }

        return $this->db->update($this->table, $attributes);
    }

    /**
     * Update records with automatic timestamps
     *
     * @param mixed $idOrRow
     * @param mixed $optionalValue
     * @param array $data
     * @return bool
     */
    public function updateWith(mixed $idOrRow = null, mixed $optionalValue = null, array $attributes = []): bool
    {
        if ($this->timestamps && !array_key_exists($this->updatedAt, $attributes)) {
            $attributes[$this->updatedAt] = $this->getCurrentTimestamp();
        }

        if ($optionalValue == null) {
            if (is_array($idOrRow)) {
                $this->db->where($idOrRow);
            } else {
                $this->db->where([$this->primaryKey => $idOrRow]);
            }
        } else {
            $this->db->where([$idOrRow => $optionalValue]);
        }

        if (is_null($idOrRow) && empty($attributes)) {
            return $this->db->update($this->table);
        }

        return $this->db->update($this->table, $attributes);
    }

    /**
     * First or create - find first record or create new one
     *
     * @param array $attributes
     * @param array $values
     * @return object|array
     */
    public function firstOrCreateWith(array $attributes, array $values = []): object|array
    {
        $record = $this->where($attributes)->first();

        if ($record) {
            return $record;
        }

        return $this->create(array_merge($attributes, $values));
    }

    /**
     * Update or create - update existing record or create new one
     *
     * @param array $attributes
     * @param array $values
     * @return object|array
     */
    public function updateOrCreateWith(array $attributes, array $values = []): object|array
    {
        $record = $this->where($attributes)->first();

        if ($record) {
            $id = is_object($record) ? $record->{$this->primaryKey} : $record[$this->primaryKey];
            $this->updateWith($id, null, $values);
            return $this->find($id);
        }

        return $this->create(array_merge($attributes, $values));
    }

    /**
     * Find or fail - throw exception if not found
     *
     * @param mixed $id
     * @return object|array
     * @throws \Exception
     */
    public function findOrFailBy(mixed $id): object|array
    {
        $result = $this->find($id);

        if (!$result) {
            throw new \Exception("No record found with ID: {$id}");
        }

        return $result;
    }

        /**
     * Soft delete a record
     *
     * @param mixed $id
     * @return bool
     */
    public function destroy(mixed $id): bool
    {
        if ($this->useSoftDelete) {
            return $this->updateWith($id, null, [
                $this->useSoftDeleteKey => $this->getCurrentTimestamp()
            ]);
        }

        return $this->deleteBy($id);
    }

    /**
     * Restore a soft deleted record
     *
     * @param mixed $id
     * @return bool
     */
    public function restore(mixed $id): bool
    {
        if ($this->useSoftDelete) {
            return $this->withTrashed()->updateWith($id, null, [
                $this->useSoftDeleteKey => null
            ]);
        }

        return false;
    }

    /**
     * Force delete a record (permanent delete)
     *
     * @param mixed $id
     * @return bool
     */
    public function forceDelete(mixed $id): bool
    {
        return $this->deleteBy($id);
    }

    // ----------------- QUERY BUILDER ENHANCEMENTS -----------------

    /**
     * When condition - apply callback if condition is true
     *
     * @param bool $condition
     * @param callable $callback
     * @param callable|null $default
     * @return $this
     */
    public function when(bool $condition, callable $callback, ?callable $default = null): static
    {
        if ($condition) {
            $callback($this);
        } elseif ($default) {
            $default($this);
        }

        return $this;
    }

    /**
     * Unless condition - apply callback if condition is false
     *
     * @param bool $condition
     * @param callable $callback
     * @param callable|null $default
     * @return $this
     */
    public function unless(bool $condition, callable $callback, ?callable $default = null): static
    {
        if (!$condition) {
            $callback($this);
        } elseif ($default) {
            $default($this);
        }

        return $this;
    }

    /**
     * Where between values
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereBetween(string $column, array $values): static
    {
        $this->db->where("{$column} >=", $values[0]);
        $this->db->where("{$column} <=", $values[1]);
        return $this;
    }

    /**
     * Where not between values
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereNotBetween(string $column, array $values): static
    {
        $this->db->where("{$column} <", $values[0]);
        $this->db->or_where("{$column} >", $values[1]);
        return $this;
    }

    /**
     * Where null
     *
     * @param string $column
     * @return $this
     */
    public function whereNull(string $column): static
    {
        $this->db->where("{$column} IS NULL");
        return $this;
    }

    /**
     * Where not null
     *
     * @param string $column
     * @return $this
     */
    public function whereNotNull(string $column): static
    {
        $this->db->where("{$column} IS NOT NULL");
        return $this;
    }

    /**
     * Where date
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function whereDate(string $column, string $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->db->where("DATE({$column}) {$operator}", $value);
        return $this;
    }

    /**
     * Where year
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function whereYear(string $column, string $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->db->where("YEAR({$column}) {$operator}", $value);
        return $this;
    }

    /**
     * Where month
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function whereMonth(string $column, string $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->db->where("MONTH({$column}) {$operator}", $value);
        return $this;
    }

    /**
     * Where day
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function whereDay(string $column, string $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->db->where("DAY({$column}) {$operator}", $value);
        return $this;
    }

    // ----------------- COLLECTION METHODS -----------------

    /**
     * Get a collection of results
     *
     * @param mixed $idOrRow
     * @param mixed $optionalValue
     * @param mixed $orderBy
     * @return array|object
     */
    public function collect(mixed $idOrRow = null, mixed $optionalValue = null, mixed $orderBy = null): array|object
    {
        $results = $this->findAll($idOrRow, $optionalValue, $orderBy);
        
        if ($this->returnAs === 'array') {
            return $results;
        }

        return (object) $results;
    }

    /**
     * Chunk results for large datasets
     *
     * @param int $count
     * @param callable $callback
     * @return bool
     */
    public function chunk(int $count, callable $callback): bool
    {
        $page = 1;

        do {
            $results = $this->setLimitStart($count, ($page - 1) * $count)->findAll();
            
            $countResults = count($results);

            if ($countResults == 0) {
                break;
            }

            if ($callback($results, $page) === false) {
                return false;
            }

            $page++;
        } while ($countResults == $count);

        return true;
    }

    /**
     * Get count of records
     *
     * @param string $column
     * @return int
     */
    public function count(string $column = '*'): int
    {
        $this->db->from($this->table);
        
        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        return $this->db->count_all_results();
    }

    /**
     * Get minimum value
     *
     * @param string $column
     * @return mixed
     */
    public function min(string $column): mixed
    {
        $this->db->select_min($column);
        $this->db->from($this->table);
        
        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        $result = $this->db->get()->row_array();
        return $result[$column] ?? null;
    }

    /**
     * Get maximum value
     *
     * @param string $column
     * @return mixed
     */
    public function max(string $column): mixed
    {
        $this->db->select_max($column);
        $this->db->from($this->table);
        
        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        $result = $this->db->get()->row_array();
        return $result[$column] ?? null;
    }

    /**
     * Get average value
     *
     * @param string $column
     * @return mixed
     */
    public function avg(string $column): mixed
    {
        $this->db->select_avg($column);
        $this->db->from($this->table);
        
        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        $result = $this->db->get()->row_array();
        return $result[$column] ?? null;
    }

    /**
     * Get sum value
     *
     * @param string $column
     * @return mixed
     */
    public function sum(string $column): mixed
    {
        $this->db->select_sum($column);
        $this->db->from($this->table);
        
        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        $result = $this->db->get()->row_array();
        return $result[$column] ?? null;
    }

    // ----------------- UTILITY METHODS -----------------

    /**
     * Add timestamps to data
     *
     * @param array $data
     * @param bool $update
     * @return array
     */
    protected function addTimestamps(array $data, bool $update = false): array
    {
        if (!$this->timestamps) {
            return $data;
        }

        $timestamp = $this->getCurrentTimestamp();

        if (!$update && !array_key_exists($this->createdAt, $data)) {
            $data[$this->createdAt] = $timestamp;
        }

        if (!array_key_exists($this->updatedAt, $data)) {
            $data[$this->updatedAt] = $timestamp;
        }

        return $data;
    }

    /**
     * Convert model to array
     *
     * @return array
     */
    public function toArray(): array
    {
        $attributes = $this->getAttributes();

        // Apply accessors
        foreach ($this->appends as $key) {
            $attributes[$key] = $this->getAttribute($key);
        }

        // Hide attributes
        if (!empty($this->hidden)) {
            foreach ($this->hidden as $key) {
                unset($attributes[$key]);
            }
        }

        // Show only visible attributes
        if (!empty($this->visible)) {
            $attributes = array_intersect_key($attributes, array_flip($this->visible));
        }

        return $attributes;
    }

    /**
     * Convert model to JSON
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    // ----------------- LEGACY METHODS (MAINTAINED FOR BACKWARD COMPATIBILITY) -----------------

    /**
     * This function is used to 
     * escape data in codeigniter
     *
     * @param string $string
     * @return mixed
     */
    public function escape(string $string)
    {
        return $this->db->escape($string);
    }

    /**
     * Get table name.
     *
     * @param string $tablename
     * @return string The name of the table used by this class.
     */
    public function table($tablename = null)
    {
        if ($tablename) {
            $this->table = $tablename;
            return $this;
        }

        return $this->table;
    }

    /**
     * Retrieves the current 
     * sql query made and displayed to user
     *
     * @return string
     */
    public function showQuery()
    {
        return $this->db->last_query();
    }

    /**
     * Alias to the above 
     * sql query made and displayed to user
     *
     * @param array $where Use for extra where queries
     * @return string
     */
    public function toSql($where = null)
    {
        $this->get($where);
        return $this->db->last_query();
    }

    /**
     * Debug query made and get it's results
     *
     * @return void
     */
    public function dd()
    {
        $result = $this->get(null);
        $query = $this->db->last_query();

        dd($result, $query);
    }

    //--------- All functions below are used for retrieving information from the database -----

    /**
     * Make a query for filling datatables
     *
     * @return mixed
     */
    public function makeDatableQuery()
    {

        $this->db->from($this->table);
        $this->db->where($this->where);

        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }
        
        $this->load->helper('security');

        $_POST = xss_clean($_POST);
        $i = 0;

        foreach ($this->columnSearch as $item) // loop column
        {
            if ($_POST['search']['value']) // if datatable send POST for search
            {
                if ($i === 0) // first loop
                {
                    $this->db->group_start(); // open bracket. query Where with OR clause better with bracket. because maybe can combine with other WHERE with AND.
                    $this->db->like($item, $_POST['search']['value']);
                } else {
                    $this->db->or_like($item, $_POST['search']['value']);
                }

                if (count($this->columnSearch) - 1 == $i) //last loop
                {
                    $this->db->group_end();
                }
                //close bracket
            }
            $i++;
        }

        if (isset($_POST['order'])) // here order processing
        {
            $this->db->order_by($this->columnOrder[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        } else if (isset($this->order)) {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    /**
     * Use queried data to make a datatable
     *
     * @return object|array
     */
    public function makeDatatable()
    {
        $this->makeDatableQuery();

        if ($_POST['length'] != -1) {
            $this->db->limit($_POST['length'], $_POST['start']);
        }

        $query = $this->db->get();

        return $this->getResult($query);
    }

    /**
     * Count retrieved data for datatable 
     *
     * @return mixed
     */
    public function countFilteredWhere()
    {
        $this->makeDatableQuery();
        $query = $this->db->get($this->where);
        return $query->num_rows();
    }

    /**
     * Count filtered data for datatable
     *
     * @return mixed
     */
    public function countFiltered()
    {
        $this->makeDatableQuery();
        $query = $this->db->get($this->where);
        return $query->num_rows();
    }

    /**
     * Count data from table  
     *
     * @return mixed
     */
    public function countAll()
    {
        $this->db->from($this->table);

        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        return $this->db->count_all_results();
    }

    /**
     * Get the total records in the table
     *
     * @param  string|array  $where
     * @return integer
     */
    public function getTotal($where = null)
    {
        if ($where != null) {
            $this->db->where($where);
        }

        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    /**
     * Limit number of data to retrieve from table
     *
     * @param integer $limit
     * @return mixed
     */
    public function setLimit($limit)
    {
        $this->db->limit($limit);
        return $this;
    }

    /**
     * Limit number of data to retrieve from table
     * by setting limit offsets
     *
     * @param integer $limit
     * @return mixed
     */
    public function setLimitStart($limit, $start)
    {
        $this->db->limit($limit, $start);
        return $this;
    }

    /**
     * Return last insert id or column
     * 
     * @param string|int $primaryKey 
     * 
     * @return mixed
     */
    public function lastInsertKey($primaryKey = null)
    {
        if ($this->primaryKey != null && $primaryKey == null) {
            $primaryKey = $this->primaryKey;
        }

        $this->db->select_max("{$primaryKey}");

        $result = $this->db->get($this->table)->row_array();
        return $result[$primaryKey];
    }

    /**
     * Return last inserted id
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->db->insert_id();
    }

    /**
     * Allow raw query 
     *
     * @param string $query
     * @return mixed
     */
    public function query($query, $binds = false, $returnObject = null)
    {
        return $this->db->query($query, $binds, $returnObject);
    }

    /**
     * Get results as Array
     *
     * @return static
     */
    public function asArray()
    {
        $this->returnAs = 'array';

        return $this;
    }

    /**
     * Get results as Json
     *
     * @return static
     */
    public function asJson()
    {
        $this->returnAs = 'json';

        return $this;
    }

    /**
     * Get results as Object
     *
     * @return static
     */
    public function asObject()
    {
        $this->returnAs = 'object';

        return $this;
    }

    /**
     * Return the method name for the current return type
     *
     * @param bool $multi
     * @return mixed
     */
    protected function returnType($multi = false)
    {
        $method = ($multi) ? 'result' : 'row';
        
		//if a custom object return type
		if (($this->temporaryReturnType == 'object') && ($this->customReturnObject != '')) {
		  return 'custom_'.$method.'_object';
		} else {
			// If our type is either 'array' or 'json', we'll simply use the array version
			// of the function, since the database library doesn't support json.
			return $this->temporaryReturnType == 'array' ? $method . '_array' : $method;
		}	
    }

    /**
     * Return the return data for configured type
    *
    * @param mixed $data
    * @param bool $multi
    * @return mixed
    */
    protected function returnData($data, $multi = false)
    {
		$returnTypeMethod = $this->returnType($multi);

		// if a object return type
		if (($this->temporaryReturnType == 'object') && ($this->customReturnObject != '')) {
			if ($returnTypeMethod == 'custom_row_object') {
				$data = $data->{$returnTypeMethod}(0,$this->customReturnObject);
			} else {	
			 $data = $data->{$returnTypeMethod}($this->customReturnObject);
			}
		} else {
			$data = $data->{$returnTypeMethod}();
		}

		return  $data;
    }

    /**
     * Chained on $this->query();
     *
     * @return object
     */
    public function result()
    {
        return $this->getResult($this->query);
    }

    /**
     * Chained on $this->query();
     *
     * @return object
     */
    public function row($last = false)
    {
        return $this->getRowResult($this->query, $last);
    }

    /**
     * Get query results function
     *
     * @param object $query
     * @return mixed
     */
    public function getResult($query)
    {
        if ($this->returnAs == 'json') {
            return json_encode($query->result_array());
        }

        if ($this->returnAs == 'array') {
            return $query->result_array();
        }

        return $query->result();
    }

    /**
     * Get query row function
     *
     * @param object $query
     * @param boolean $last
     * @return mixed
     */
    public function getRowResult($query, $last = false)
    {

        $mode = 'first_row';

        if ($last === true) {
            $mode = 'last_row';
        }

        if ($this->returnAs == 'json') {
            $result['data'] = $query->{$mode}('array');
            return json_encode($result['data']);
        }

        if ($this->returnAs == 'array') {
            return $query->{$mode}('array');
        }

        return $query->{$mode}('object');
    }

    /**
     * Protect fields by removing them from $row
     *
     * @param array|object $row
     * @return mixed
     */
    public function protectFields($row)
    {
        foreach ($this->protected as $attr) {
            if (is_object($row)) {
                unset($row->$attr);
            } else {
                unset($row[$attr]);
            }
        }

        return $row;
    }

    /**
     * Grabs data from a table
     *       OR a single record by passing $id,
     *       OR a different field than the primaryKey by passing two paramters
     *       OR by passing an array
     *
     * @param mixed $idOrRow      (Optional)
     *                             null    = Fetch all table records
     *                             number  = Fetch where primary key = $id
     *                             string  = Fetch based on a different column name
     *                             array   = Fetch based on array criteria
     *
     * @param mixed   $optionalValue (Optional)
     * @param string  $orderBy (Optional)
     *
     * @return array|object database results
     */
    public function get($idOrRow = null, $optionalValue = null, $orderBy = null)
    {

        // Custom order by if desired
        if ($orderBy != null) {
            $this->db->order_by($orderBy);
        }

        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        // Fetch all records for a table
        if ($idOrRow == null) {
            $query = $this->db->get($this->table);
        } elseif (is_array($idOrRow)) {
            $query = $this->db->get_where($this->table, $idOrRow);
        } else {
            if ($optionalValue == null) {
                $query = $this->db->get_where($this->table, [$this->primaryKey => $idOrRow]);
            } else {
                $query = $this->db->get_where($this->table, [$idOrRow => $optionalValue]);
            }
        }

        return $this->getResult($query);
    }

    /**
     * A simple way to grab the first 
     * result of a search only.
     *
     * @return array|object
     */
    public function first()
    {
        $rows = $this->limit(1)->findAll();

        if (is_array($rows) && count($rows) == 1) {
            return $rows[0];
        }

        return $rows;
    }

    /**
     * Find with id
     *
     * @param string|integer|array $idOrRow
     * @return array|object|null
     */
    public function findOne($idOrRow = null)
    {
        $row = $this->get($idOrRow, null, null);

        if ($row) {
            return $row[0];
        }

        return null;
    }

    /**
     * Find all
     *
     * @param string|integer|array $idOrRow
     * @param mixed $optionalValue
     * @param mixed $orderBy
     * @return array|object
     */
    public function findAll($idOrRow = null, $optionalValue = null, $orderBy = null)
    {
        return $this->get($idOrRow, $optionalValue, $orderBy);
    }

    /**
     * Alias to findAll()
     * @param string|integer|array $idOrRow
     * @param mixed $optionalValue
     * @param mixed $orderBy
     * @return array|object
     * @return object or false
     */
    public function all($idOrRow = null, $optionalValue = null, $orderBy = null)
    {
        return $this->findAll($idOrRow, $optionalValue, $orderBy);
    }

    /**
     * A simple way to paginate records
     *
     * @param int $limit
     * @param int $offset
     * @return mixed
     */
    public function paginate($limit = 10, $offset = 0)
    {
        $this->db->limit($limit, $offset);

        $query = $this->db->get($this->table);

        return $this->getResult($query);
    }

    /**
     * Find where
     *
     * @param string $fields
     * @param array $where
     * @param integer $limit
     * @param mixed $orderBy
     * @return array|object
     */
    public function findWhere($fields, $where = null, $limit = null, $orderBy = null)
    {

        // Custom order by if desired
        if ($orderBy != null) {
            $this->db->order_by($orderBy);
        }

        if ($limit != null) {
            $this->db->select($fields)->from($this->table)->where($where)->limit($limit);
        } else if ($where != null) {
            $this->db->select($fields)->from($this->table)->where($where);
        } else {
            $this->db->select($fields)->from($this->table);
        }

        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        $query = $this->db->get();

        return $this->getResult($query);
    }

    /**
     * Find with where or orWhere
     * 
     * @param string  $field
     * @param array  $where
     * @param array $orwhere
     * @param integer $limit
     * @param mixed $orderBy
     * @return array|object
     */
    public function findOrWhere($fields, $where = null, $orWhere = null, $limit = null, $orderBy = null)
    {

        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        // Custom order by if desired
        if ($orderBy != null) {
            $this->db->order_by($orderBy);
        }

        if ($limit != null) {
            $this->db->select($fields)->from($this->table)->where($where)->limit($limit);
        } else if ($where != null) {
            $this->db->select($fields)->from($this->table)->where($where);
        } else {
            $this->db->select($fields)->from($this->table);
        }

        if ($orWhere != null) {
            $this->db->or_where($orWhere);
        }

        $query = $this->db->get();

        return $this->getResult($query);
    }

    /**
     * Find limit where
     *
     * @param string $fields
     * @param integer $limit
     * @param array $where
     * @return array|object
     */
    public function findLimitWhere($fields, $limit = null, $where = null)
    {

        if ($where != null) {
            $this->db->select($fields)->from($this->table)->limit($limit);
            $this->db->where($where);
        } else if ($limit != null) {
            $this->db->select($fields)->from($this->table)->limit($limit);
        }

        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        $query = $this->db->get();

        return $this->getResult($query);
    }

    /**
     * Get data by a single where field or 
     * where many fields an alternative to 
     * the above function
     * 
     * @see get(...param)
     * @param array $where
     * @return array|object
     */
    public function whereBy($where)
    {
        $this->db->select()->from($this->table)->where($where);

        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        $query = $this->db->get();

        return $this->getRowResult($query);
    }

    /**
     * Get data by selecting a field or many fields
     * where the values are provided
     *
     * @param string $field
     * @param array $value
     * @return object|array
     */
    public function selectWhere($field, $value)
    {

        $this->db->select($field)->from($this->table)->where($value);

        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        $query = $this->db->get();

        return $this->getRowResult($query);
    }

    /**
     * Get first data by selecting a field or many fields
     * singlely
     *
     * @param array $field
     * @return object|array
     */
    public function selectSingle($field)
    {
        $this->db->select($field)->from($this->table)->limit(1);

        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        $query = $this->db->get();

        return $this->getRowResult($query);
    }

    /**
     * Get last data by selecting a field or many fields
     * singlely
     *
     * @param array|string $field
     * @return object|array
     */
    public function selectLast($field, $orderBy = '')
    {
        if (empty($orderBy)) {
            $orderBy = $this->primaryKey;
        }

        $this->db->select($field)->from($this->table);

        if ($this->useSoftDelete && $this->temporaryWithDeleted !== true) {
            $this->db->where($this->useSoftDeleteKey, $this->softDeleteFalseValue);
        }

        $this->db->limit(1)->order_by($orderBy, 'DESC');

        $query = $this->db->get();

        return $this->getRowResult($query, true);
    }

    /**
     * Insert a record and might return the last inserted field value;
     * 
     * @param  array $data
     * @param  bool $return = false
     * @return boolean|object|array
     * @deprecated This method is deprecated and will be removed in a future version. 
     * Use insert() for inserting data and find() for retrieving the new record, 
     * or insert() for just inserting and getting the ID.
     */
    public function saveOnly($data, $return = true)
    {
        if ($return) {
            $this->db->insert($this->table, $data);
            $insertId = $this->db->insert_id();
            return $this->find($insertId);
        }

        return $this->db->insert($this->table, $data);
    }

    /**
     * Insert a record
     * 
     * @param  array $data
     * @return integer
     */
    public function setSave($data)
    {
        $this->db->set($data)->insert($this->table);
        return $this->db->insert_id();
    }

    /**
     * Creates a record
     *
     * @usage  insert(['name' => 'jesse', 'age' => 28])
     *
     * @param     array   $data key value pair of mySQL fields
     *
     * @return    integer  insert id
     */
    public function insert($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * This method replaces existing 
     * value with new array data
     * 
     * @param  array $data
     * @return int
     */
    public function replace($data)
    {
        $this->db->replace($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Insert Batch data into table
     *
     * @param array $data
     * @param boolean $escape
     * @param integer $size
     * @return integer
     */
    public function insertBatch($data, $escape = null, $size = 100)
    {
        return $this->db->insert_batch($this->table, $data, $escape, $size);
    }

    /**
     * Insert if not exists, if exists Update
     *
     * @usage   upsert(['item' => 10], 25)
     *          upsert(['item' => 10], 'other_key' => 25)
     *
     * @param array $data Associative array [column => value]
     *
     * @param   integer|string $idOrRow (Optional)
     *           null    = Fetch all table records
     *           number  = Fetch where primary key = $id
     *           string  = Fetch based on a different column name
     *
     * @param integer|string $optionalValue (Optional)
     *
     * @return integer InsertID|Update Result
     */
    public function upsert($idOrRow, $optionalValue = null, $data = [])
    {
        // First check to see if the field exists
        $this->db->select($this->primaryKey);

        if ($optionalValue == null) {
            $query = $this->db->get_where($this->table, [$this->primaryKey => $idOrRow]);
        } else {
            $query = $this->db->get_where($this->table, [$idOrRow => $optionalValue]);
        }

        // Count how many records exist with this ID
        $result = $query->num_rows();

        // INSERT
        if ($result == 0) {
            $this->db->insert($this->table, $data);
            return $this->db->insert_id();
        }

        // UPDATE
        if ($optionalValue == null) {
            $this->db->where($this->primaryKey, $idOrRow);
        } else {
            $this->db->where($idOrRow, $optionalValue);
        }

        return $this->db->update($this->table, $data);
    }

    /**
     * update a record
     */
    public function simpleUpdate($where, $data)
    {
        $this->db->where($where);
        return $this->db->update($this->table, $data);
    }

    /**
     * update a record
     */
    public function simpleSetUpdate($where, $data)
    {
        $this->db->where($where);
        $this->db->set($data, false);
        return $this->db->update($this->table);
    }

    /**
     * update a record in string mode
     */
    public function updateByString($where, $data)
    {
        return $this->db->query(
            $this->db->update_string($this->table, $data, $where)
        );
    }

    /**
     * Update batch data into table
     * 
     * @param  array $data
     * @return boolean
     */
    public function updateBatch($data, $by_field, $size = 100)
    {
        return $this->db->update_batch($this->table, $data, $by_field, $size);
    }

    /**
     * Truncate table function
     *
     * @param string $table
     * @return mixed
     */
    public function truncate($table = '')
    {
        if ($this->table && $table === '') {
            $table = $this->table;
        }

        return $this->db->truncate($table);
    }

    /**
     * Delete a record
     *
     * @usage   deleteBy(12)
     *          deleteBy('email', 'test@test.com')
     *          deleteBy([
     *              'name' => 'ted',
     *              'age' => 25
     *          ]);
     *
     * @param   integer|string|array $idOrRow (Optional)
     *          number  = Delete primary key ID
     *          string  = Column Name
     *          array   = key/value pairs
     *
     * @param integer|string|array $optionalValue
     *              (Optional) Use when first param is string
     *
     * @return boolean result
     */
    public function deleteBy($idOrRow, $optionalValue = null)
    {
        if ($optionalValue == null) {
            if (is_array($idOrRow)) {
                $this->db->where($idOrRow);
            } else {
                $this->db->where([$this->primaryKey => $idOrRow]);
            }
        } else {
            $this->db->where($idOrRow, $optionalValue);
        }

        return $this->db->delete($this->table);
    }

    /**
     * Use soft delete
     *
     * @param array $data
     * @param array  $where
     * @return mixed
     */
    public function softDelete($where, $data)
    {
        $this->db->where($where);
        return $this->db->update($this->table, $data);
    }

    //------------------ Custom Method Functionalities --------------------------------------------------

    /**
     * Checks whether a field/value pair exists within the table.
     *
     * @param string $field The field to search for.
     * @param string $value The value to match $field against.
     *
     * @return bool true/false
     */
    public function isUnique($field, $value)
    {
        $this->db->where($field, $value);
        $query = $this->db->get($this->table);

        if ($query && $query->num_rows() == 0) {
            return true;
        }

        return false;
    }

    /**
     * Left Join
     *
     * Do left join portion of the query
     *
     * @param	string  table to do join with
     * @param	string	the join condition
     * @param	string	whether not to try to escape identifiers
     * @return	static
     */
    public function leftJoin($table, $condition, $escape = null)
    {
        $this->db->join($table, $condition, 'left', $escape);
        return $this;
    }

    /**
     * Right Join
     *
     * Do left join portion of the query
     *
     * @param	string  table to do join with
     * @param	string	the join condition
     * @param	string	whether not to try to escape identifiers
     * @return	static
     */
    public function rightJoin($table, $condition, $escape = null)
    {
        $this->db->join($table, $condition, 'right', $escape);
        return $this;
    }

    /**
     * Inner Join
     *
     * Do left join portion of the query
     *
     * @param	string  table to do join with
     * @param	string	the join condition
     * @param	string	whether not to try to escape identifiers
     * @return	static
     */
    public function innerJoin($table, $condition, $escape = null)
    {
        $this->db->join($table, $condition, 'inner', $escape);
        return $this;
    }

    /**
     * Outer Join
     *
     * Do left join portion of the query
     *
     * @param	string  table to do join with
     * @param	string	the join condition
     * @param	string	whether not to try to escape identifiers
     * @return	static
     */
    public function outerJoin($table, $condition, $escape = null)
    {
        $this->db->join($table, $condition, 'outer', $escape);
        return $this;
    }

    //------------------ CodeIgniter Database  Wrappers ------------------

    /*
     |   To allow for more expressive syntax, we provide 
     |   wrapper functions for most of the query 
     |   builder methods here and also some 
     |   custom methods.
     |
     |   This allows for calls such as:
     |   $result = $this->model->select('...')
     |                         ->where('...')
     |                         ->having('...')
     |                         ->findAll() or ->get();
     |
     */

    //--------------------------------------------------------------------

    /**
     * Pick function
     * A sugared way of using select()
     * 
     * @param string $select
     * @param mixed $escape
     * @return static
     */
    public function pick($select = '*', $escape = null)
    {
        $this->db->select($select, $escape);
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * Increments the value of fields or multiple fields 
     * and their values by primary key of a specific table.
     * 
     * @param mixed $id
     * @param string|array $fields
     * @param integer $value
     * @param array $columns
     * @return bool|static
     */
    public function increment(
        mixed $id,
        string|array $fields,
        int|array $value = 1,
        array $columns = []
    ) {
        
        if (is_numeric($value)) {
            $value = (int) abs($value);
        }
       
        $columns = is_array($value) ? $value : $columns;

        $fieldsArray = is_array($fields);

        $this->db->where($this->primaryKey, $id);

        if ($fieldsArray) {
            foreach ($fields as $field => $number) {
                $this->db->set($field, "{$field}+{$number}", false);
            }
        }

        if (!$fieldsArray && is_string($fields)) {
            $this->db->set($fields, "{$fields}+{$value}", false);
        }

        if (!empty($columns) ) {
            $this->db->set($columns, false);
        }

        $result = $this->db->update($this->table);

        return ($fieldsArray) ? $result : $this;
       
    }

    //--------------------------------------------------------------------

    /**
     * Decrements the value of fields or multiple fields 
     * and their values by primary key of a specific table.
     * 
     * @param mixed $id
     * @param string|array $fields
     * @param integer $value
     * @param array $columns
     * @return bool|static
     */
    public function decrement(
        mixed $id,
        string|array $fields,
        int|array $value = 1,
        array $columns = []
    ) {
        if (is_numeric($value)) {
            $value = (int) abs($value);
        }
       
        $columns = is_array($value) ? $value : $columns;

        $fieldsArray = is_array($fields);

        $this->db->where($this->primaryKey, $id);

        if ($fieldsArray) {
            foreach ($fields as $field => $number) {
                $this->db->set($field, "{$field}-{$number}", false);
            }
        }

        if (!$fieldsArray && is_string($fields)) {
            $this->db->set($fields, "{$fields}-{$value}", false);
        }

        if (!empty($columns) ) {
            $this->db->set($columns, false);
        }

        $result = $this->db->update($this->table);

        return ($fieldsArray) ? $result : $this;
       
    }

    //--------------------------------------------------------------------

    /**
     * Select function
     *
     * @param string $select
     * @param mixed $escape
     * @return static
     */
    public function select($select = '*', $escape = null)
    {
        $this->db->select($select, $escape);
        return $this;
    }

    /**
     * Select Maximum function
     *
     * @param string $select
     * @param string $alias
     * @return static
     */
    public function selectMax($select = '', $alias = '')
    {
        $this->db->select_max($select, $alias);
        return $this;
    }

    /**
     * Select Minimum function
     *
     * @param string $select
     * @param string $alias
     * @return static
     */
    public function selectMin($select = '', $alias = '')
    {
        $this->db->select_min($select, $alias);
        return $this;
    }

    /**
     * Select Average function
     *
     * @param string $select
     * @param string $alias
     * @return static
     */
    public function selectAvg($select = '', $alias = '')
    {
        $this->db->select_avg($select, $alias);
        return $this;
    }

    /**
     * Select Sum function
     *
     * @param string $select
     * @param string $alias
     * @return static
     */
    public function selectSum($select = '', $alias = '')
    {
        $this->db->select_sum($select, $alias);
        return $this;
    }

    /**
     * Distinct function
     *
     * @param boolean $value
     * @return static
     */
    public function distinct($value = true)
    {
        $this->db->distinct($value);
        return $this;
    }

    /**
     * From function
     *
     * @param  string $from
     * @return static
     */
    public function from($from)
    {
        $this->db->from($from);
        return $this;
    }

    /**
     * Join function
     *
     * Generates the JOIN portion of the query
     *
     * @param   string $table table name
     * @param   string $condition the join condition
     * @param   string $type the type of join
     * @param	string $escape whether not to try to escape identifiers
     * @return	static
     */
    public function join($table, $condition, $type = '', $escape = null)
    {
        $this->db->join($table, $condition, $type, $escape);
        return $this;
    }

    /**
     * Where function
     *
     * @param mixed $key
     * @param mixed $value
     * @param boolean $escape
     * @return static
     */
    public function where($key, $value = null, $escape = true)
    {
        $this->db->where($key, $value, $escape);
        return $this;
    }

    /**
     * orWhere function
     *
     * @param mixed $key
     * @param mixed $value
     * @param boolean $escape
     * @return static
     */
    public function orWhere($key, $value = null, $escape = true)
    {
        $this->db->or_where($key, $value, $escape);
        return $this;
    }

    /**
     * whereIn function
     *
     * @param mixed $key
     * @param mixed $values
     * @return static
     */
    public function whereIn($key = null, $values = null)
    {
        $this->db->where_in($key, $values);
        return $this;
    }

    /**
     * orWhereIn function
     *
     * @param mixed $key
     * @param mixed $values
     * @return static
     */
    public function orWhereIn($key = null, $values = null)
    {
        $this->db->or_where_in($key, $values);
        return $this;
    }

    /**
     * whereNotIn function
     *
     * @param mixed $key
     * @param mixed $values
     * @return static
     */
    public function whereNotIn($key = null, $values = null)
    {
        $this->db->where_not_in($key, $values);
        return $this;
    }

    /**
     * orWhereNotIn function
     *
     * @param mixed $key
     * @param mixed $values
     * @return static
     */
    public function orWhereNotIn($key = null, $values = null)
    {
        $this->db->or_where_not_in($key, $values);
        return $this;
    }

    /**
     * Like function
     *
     * @param string $field
     * @param string $match
     * @param string $side
     * @return static
     */
    public function like($field, $match = '', $side = 'both')
    {
        $this->db->like($field, $match, $side);
        return $this;
    }

    /**
     * notLike function
     *
     * @param string $field
     * @param string $match
     * @param string $side
     * @return static
     */
    public function notLike($field, $match = '', $side = 'both')
    {
        $this->db->not_like($field, $match, $side);
        return $this;
    }

    /**
     * orLike function
     *
     * @param string $field
     * @param string $match
     * @param string $side
     * @return static
     */
    public function orLike($field, $match = '', $side = 'both')
    {
        $this->db->or_like($field, $match, $side);
        return $this;
    }

    /**
     * orNotLike function
     *
     * @param string $field
     * @param string $match
     * @param string $side
     * @return static
     */
    public function orNotLike($field, $match = '', $side = 'both')
    {
        $this->db->or_not_like($field, $match, $side);
        return $this;
    }

    /**
     * groupBy function
     *
     * @param string $by
     * @return static
     */
    public function groupBy($by, $escape = null)
    {
        $this->db->group_by($by, $escape);
        return $this;
    }

    /**
     * Having function
     *
     * @param mixed $key
     * @param string $value
     * @param boolean $escape
     * @return static
     */
    public function having($key, $value = '', $escape = true)
    {
        $this->db->having($key, $value, $escape);
        return $this;
    }

    /**
     * orHaving function
     *
     * @param mixed $key
     * @param string $value
     * @param boolean $escape
     * @return static
     */
    public function orHaving($key, $value = '', $escape = true)
    {
        $this->db->or_having($key, $value, $escape);
        return $this;
    }

    /**
     * orderBy function
     *
     * @param string $orderby
     * @param string $direction
     * @return static
     */
    public function orderBy($orderby, $direction = '')
    {
        $this->db->order_by($this->table . '.' . $orderby, $direction);
        return $this;
    }

    /**
     * Retrieve previous record in 
     * a given table
     *
     * @param int $currentId
     * @param string $fields
     * @return array|object
     */
    public function previous($currentId, $fields = '*')
    {
        $this->db->select($fields)
            ->from($this->table)
            ->where('id <', $currentId)
            ->order_by('id', 'desc')
            ->limit(1);
        
        $query = $this->db->get();
        
        return $this->getRowResult($query);

    }

    /**
     * Retrieve next record in 
     * a given table
     *
     * @param int $currentId
     * @param string $fields
     * @return array|object
     */
    public function next($currentId, $fields = '*')
    {
        $this->db->select($fields)
            ->from($this->table)
            ->where('id >', $currentId)
            ->order_by('id', 'asc')
            ->limit(1);
        
        $query = $this->db->get();
        
        return $this->getRowResult($query);

    }

    /**
     * latest function
     *
     * @param string $column
     * @return static
     */
    public function latest($column = 'created_at')
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * oldest function
     *
     * @param string $column
     * @return static
     */
    public function oldest($column = 'created_at')
    {
        return $this->orderBy($column, 'asc');
    }

    /**
     * Limit function
     *
     * @param int $value
     * @param int $offset
     * @return static
     */
    public function limit($value, $offset = 0)
    {
        $this->db->limit($value, $offset);
        return $this;
    }

    /**
     * Offset function
     *
     * @param mixed $offset
     * @return static
     */
    public function offset($offset)
    {
        $this->db->offset($offset);
        return $this;
    }

    /**
     * Set key value
     *
     * @param mixed $key
     * @param string $value
     * @param boolean $escape
     * @return static
     */
    public function set($key, $value = '', $escape = true)
    {
        $this->db->set($key, $value, $escape);
        return $this;
    }

    /**
	 * Start Transaction
	 *
	 * @param	bool	$test_mode = false
	 * @return	bool
	 */
    public function startTransaction($testMode = false)
    {
        return $this->db->trans_start($testMode);
    }

    /**
	 * Begin Transaction
	 *
	 * @param	bool	$test_mode = false
	 * @return	bool
	 */
    public function beginTransaction($testMode = false)
    {
        return $this->db->trans_begin($testMode);
    }

    /**
	 * Complete Transaction
	 *
	 * @return	bool
	 */
    public function completeTransaction()
    {
        return $this->db->trans_complete();
    }

    /**
	 * Lets you retrieve the transaction flag 
     * to determine if it has failed
	 *
	 * @return	bool
	 */
    public function transactionStatus()
    {
        return $this->db->trans_status();
    }

    /**
	 * Rollback Transaction
	 *
	 * @return	bool
	 */
    public function rollbackTransaction()
    {
        return $this->db->trans_rollback();
    }

    /**
	 * Commit Transaction
	 *
	 * @return	bool
	 */
    public function commitTransaction()
    {
        return $this->db->trans_commit();
    }

    // ----------------- MODEL EVENTS -----------------

    /**
     * Boot the model
     */
    protected function boot(): void
    {
        // Override in child classes for custom boot logic
    }

    /**
     * Handle creating event
     *
     * @param array $data
     * @return array
     */
    protected function creating(array $data): array
    {
        return $data;
    }

    /**
     * Handle created event
     *
     * @param mixed $result
     */
    protected function created(mixed $result): void
    {
        // Override in child classes
    }

    /**
     * Handle updating event
     *
     * @param array $data
     * @return array
     */
    protected function updating(array $data): array
    {
        return $data;
    }

    /**
     * Handle updated event
     *
     * @param mixed $result
     */
    protected function updated(mixed $result): void
    {
        // Override in child classes
    }

    /**
     * Handle deleting event
     *
     * @param mixed $id
     * @return bool
     */
    protected function deleting(mixed $id): bool
    {
        return true;
    }

    /**
     * Handle deleted event
     *
     * @param mixed $result
     */
    protected function deleted(mixed $result): void
    {
        // Override in child classes
    }

    // ----------------- MAGIC METHODS -----------------

    /**
     * Handle dynamic method calls
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public function __call(string $method, array $parameters): mixed
    {
        // Handle scope methods
        if (str_starts_with($method, 'scope')) {
            $scope = lcfirst(substr($method, 5));
            return $this->scope($scope, ...$parameters);
        }

        // Handle where methods (whereEmail, whereStatus, etc.)
        if (str_starts_with($method, 'where')) {
            $attribute = snake_case(substr($method, 5));
            return $this->where($attribute, $parameters[0] ?? null);
        }

        // Handle relationship methods
        if (method_exists($this, $method)) {
            return $this->{$method}(...$parameters);
        }

        throw new \Exception("Method {$method} does not exist on " . get_class($this));
    }

    /**
     * Handle dynamic property access
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {

        if (property_exists(get_instance(), $key)) {
            
            return parent::__get($key);
        }

        return $this->getAttribute($key);

        //Exception
        // throw new \Exception("Property `{$key}` does not exist", 500);
    }

    /**
     * Handle dynamic property setting
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Handle dynamic property existence check
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {

        if (isset($this->attributes[$key])) {
            
            return true;
        }
        else if (isset($this->attributes[$key])) {

            return true;
        }
        else if (method_exists($this, $method = $key)) {
            
            // return ($this->getRelationshipProperty($method));
        }

        return false;

        // return !is_null($this->getAttribute($key));
    }

    /**
     * Handle dynamic property unsetting
     *
     * @param string $key
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * Convert model to string (JSON)
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * ArrayAccess offsetSet
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        
        $this->__set($offset, $value);
    }

    /**
     * ArrayAccess offsetExists
     *
     * @param string $offset
     * @return bool Result
     */
    public function offsetExists(mixed $offset): bool {

        return $this->__isset($offset);
    }

    /**
     * ArrayAccess offsetUnset
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void {

        $this->__unset($offset);
    }

    /**
     * ArrayAccess offsetGet
     *
     * @param mixed $offset
     * @return mixed Value of property
     */
    public function offsetGet(mixed $offset): mixed {

        return $this->$offset;
    }

}
/* end of file Base/Models/BaseModel.php */

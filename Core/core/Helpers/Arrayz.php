<?php

/**
 * Arrayz Collection Class for WebbyPHP or Modern CodeIgniter 3 
 * 
 * A powerful, fluent, beginner-friendly collection class that extends
 * the original Arrayz functionality with modern collection patterns.
 * 
 * Inspired by Arrayz and Laravel Collections
 * Credit: https://github.com/nobitadore/Arrayz
 * 
 * Features:
 * - Fluent method chaining
 * - Immutable operations (returns new instances)
 * - Beginner-friendly API
 * - Implements standard interfaces
 * 
 * @author Kwame Oteng Appiah-Nti
 * @version 1.0.0
 */

namespace Base\Helpers;

use Base\Cache\Cache;
use ArrayAccess;
use Iterator;
use Countable;
use JsonSerializable;
use Generator;
use InvalidArgumentException;

class Arrayz implements ArrayAccess, Iterator, Countable, JsonSerializable
{
    /**
     * The items contained in the collection
     */
    private array $items = [];

    /**
     * Cache instance
     */
    private Cache $cache;

    /**
     * Cache serialization type
     */
    private string $cacheAs = 'serialize';

    /**
     * Cache path
     */
    private string $cachePath = 'arrayz';

    /**
     * Cache item name
     */
    private ?string $cacheItem = null;

    /**
     * Keys to be censored
     */
    public array $keys = [];

    /**
     * Replacement value for censored data
     */
    public mixed $ink = null;

    /**
     * Pagination results
     */
    public mixed $results = null;

    /**
     * Pagination pager
     */
    public mixed $pager = null;

    public function __construct(mixed $items = [])
    {

        $this->items = [];

        if ($this->isValidSource($items)) {
            $this->items = $this->normalizeSource($items);
        }

        // $this->items = $this->arrayfy($items);

        $this->cache = new Cache;
    }

    /**
     * Create a new collection instance
     * @return self
     */
    public static function make(mixed $items = []): self
    {
        return new static($items);
    }

    /**
     * Create collection from various data types
     */
    public static function from(mixed $data): self
    {
        if (is_array($data)) {
            return new static($data);
        }

        if (is_object($data)) {

            // Handle query results as array
            if (method_exists($data, 'result_array')) {
                return new static($data->result_array());
            }

            // Handle query results as object
            if (method_exists($data, 'result')) {
                return new static($data->result());
            }

            // Handle other objects
            return new static((array) $data);
        }

        if (is_string($data) && is_json($data)) {
            return new static(json_decode($data, true));
        }

        return new static([$data]);
    }

    /**
     * Make object callable
     */
    public function __invoke(mixed $item = []): self
    {
        return new static($item);
    }

    /**
     * Get iterator for IteratorAggregate interface
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Check if source is valid 
     * (supports arrays, iterables, generators)
     */
    private function isValidSource($source): bool
    {
        return is_array($source) ||
            is_iterable($source) ||
            $source instanceof Generator ||
            (is_object($source) && method_exists($source, 'result_array')) ||
            (is_object($source) && method_exists($source, 'result')) ||
            (is_object($source) && method_exists($source, 'toArray'));
    }

    /**
     * Normalize source to handle 
     * generators and iterables efficiently
     */
    private function normalizeSource($source)
    {
        if (is_array($source)) {
            return $source;
        }

        if ($source instanceof Generator || is_iterable($source)) {
            // Only convert to array when necessary for memory efficiency
            return $source;
        }

        if (is_object($source) && method_exists($source, 'result_array')) {
            return $source->result_array();
        }

        if (is_object($source) && method_exists($source, 'result')) {
            return $source->result();
        }

        if (is_object($source) && method_exists($source, 'toArray')) {
            return $source->toArray();
        }

        return [];
    }

    /**
     * Convert source to array when needed (lazy conversion)
     */
    private function ensureArray()
    {
        if (! is_array($this->items)) {
            if (is_iterable($this->items)) {
                $this->items = iterator_to_array($this->items, true);
            } else {
                $this->items = [];
            }
        }
        return $this->items;
    }

    // ------------------ CACHING METHODS ------------------

    /**
     * Set cache serialization type
     */
    public function use(string $type = 'serialize'): self
    {
        $allowedTypes = ['serialize', 'json', 'igbinary'];

        if (! in_array($type, $allowedTypes)) {
            throw new InvalidArgumentException(
                "Invalid cache type. Allowed: " . implode(', ', $allowedTypes)
            );
        }

        $clone = clone $this;
        $clone->cacheAs = $type;
        return $clone;
    }

    /**
     * Use JSON serialization
     */
    public function json(): self
    {
        return $this->use('json');
    }

    /**
     * Use Igbinary serialization
     */
    public function igbinary(): self
    {
        return $this->use('igbinary');
    }

    /**
     * Get data from cache or set source
     */
    public function cache(string $item): self
    {

        if (!is_string($item)) {
            throw new InvalidArgumentException("cache() only expects a string to be used");
        }

        $clone = clone $this;
        $clone->cache->serializeWith = $this->cacheAs;
        $clone->cache->setCachePath($this->cachePath);
        $clone->cacheItem = $item;

        if ($clone->cache->isCached($item)) {
            $data = $clone->cache->getCacheItem($item);
            $clone->items = is_object($data) ? $this->arrayfy($data) : $data;
        }

        return $clone;
    }

    /**
     * Set cache data with TTL
     */
    public function set(mixed $data, int $ttl = 1800): self
    {
        if (!empty($ttl)) {
            $this->cache->ttl = $ttl;
        }

        $this->cache->serializeWith = $this->cacheAs;
        $this->cache->setCacheItem($this->cacheItem, $data, $ttl);
        return $this;
    }

    /**
     * Delete cache item
     */
    public function delete(): self
    {
        if ($this->cache->isCached($this->cacheItem)) {
            $this->cache->deleteCacheItem($this->cacheItem);
        }

        return $this;
    }

    /**
     * Check if cache item is available
     */
    public function available(string $item = '', ?int $ttl = null): bool
    {
        $this->cache->setCachePath($this->cachePath);
        return $this->cache->isCached($item, $ttl);
    }

    // ------------------ CORE COLLECTION METHODS ------------------

    /**
     * Apply callback to each item and return new collection
     */
    public function map(callable $callback): self
    {
        return new static(array_map($callback, $this->items));
    }

    /**
     * Filter items using callback and return new collection
     */
    public function filter(?callable $callback = null): self
    {
        if ($callback === null) {
            return new static(array_filter($this->items));
        }

        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Filter by key-value pair
     */
    public function where(string $key, mixed $operator = null, mixed $value = null): self
    {
        // Handle where('key', 'value') syntax
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->filter(function ($item) use ($key, $operator, $value) {
            $itemValue = $this->getValue($item, $key);

            return match ($operator) {
                '=', '==' => $itemValue == $value,
                '===' => $itemValue === $value,
                '!=' => $itemValue != $value,
                '!==' => $itemValue !== $value,
                '>' => $itemValue > $value,
                '>=' => $itemValue >= $value,
                '<' => $itemValue < $value,
                '<=' => $itemValue <= $value,
                'like' => str_contains(strtolower($itemValue), strtolower($value)),
                'in' => in_array($itemValue, (array) $value),
                'not_in' => !in_array($itemValue, (array) $value),
                default => $itemValue == $value,
            };
        });
    }

    /**
     * Filter where key value is in given array
     */
    public function whereIn(string $key, array $values): self
    {
        return $this->where($key, 'in', $values);
    }

    /**
     * Filter where key value is not in given array
     */
    public function whereNotIn(string $key, array $values): self
    {
        return $this->where($key, 'not_in', $values);
    }

    /**
     * Filter where key value is null
     */
    public function whereNull(string $key): self
    {
        return $this->filter(fn($item) => $this->getValue($item, $key) === null);
    }

    /**
     * Filter where key value is not null
     */
    public function whereNotNull(string $key): self
    {
        return $this->filter(fn($item) => $this->getValue($item, $key) !== null);
    }

    /**
     * Get values from a specific key (like SQL SELECT)
     */
    public function pluck(string $key, ?string $keyBy = null): self
    {
        $result = [];

        foreach ($this->items as $item) {
            $value = $this->getValue($item, $key);

            if ($keyBy === null) {
                $result[] = $value;
            } else {
                $keyValue = $this->getValue($item, $keyBy);
                $result[$keyValue] = $value;
            }
        }

        return new static($result);
    }

    /**
     * Extract values from multiple keys
     */
    public function pick(string ...$keys): self
    {
        if (empty($keys)) {
            return new static([]);
        }

        // If only one key provided, use original pick behavior
        if (count($keys) === 1) {
            return $this->pluck($keys[0]);
        }

        // Multiple keys - select specific columns
        return $this->map(function ($item) use ($keys) {
            $result = [];
            foreach ($keys as $key) {
                $result[$key] = $this->getValue($item, $key);
            }
            return $result;
        });
    }

    /**
     * Group items by key or callback
     */
    public function groupBy(string|callable $groupBy): self
    {
        $groups = [];

        foreach ($this->items as $item) {
            if (is_callable($groupBy)) {
                $key = $groupBy($item);
            } else {
                $key = $this->getValue($item, $groupBy);
            }

            $groups[$key] = $groups[$key] ?? [];
            $groups[$key][] = $item;
        }

        return new static(array_map(fn($group) => new static($group), $groups));
    }

    /**
     * Sort collection by key or callback
     */
    public function sortBy(string|callable $sortBy, string $direction = 'asc'): self
    {
        $sorted = $this->items;

        if (is_callable($sortBy)) {
            usort($sorted, $sortBy);
        } else {
            usort($sorted, function ($a, $b) use ($sortBy, $direction) {
                $valueA = $this->getValue($a, $sortBy);
                $valueB = $this->getValue($b, $sortBy);

                $result = $valueA <=> $valueB;

                return $direction === 'desc' ? -$result : $result;
            });
        }

        return new static($sorted);
    }

    /**
     * Sort collection in descending order
     */
    public function sortByDesc(string|callable $sortBy): self
    {
        return $this->sortBy($sortBy, 'desc');
    }

    /**
     * Reverse the collection
     */
    public function reverse(): self
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * Shuffle the collection
     */
    public function shuffle(): self
    {
        $items = $this->items;
        shuffle($items);
        return new static($items);
    }

    /**
     * Take first n items
     */
    public function take(int $limit, int $offset = 0): self
    {
        if ($limit < 0) {
            return new static(array_slice($this->items, $limit));
        }

        return new static(array_slice($this->items, $offset, $limit));
    }

    /**
     *  Limit method with better bounds checking
     */
    public function limit(int $limit, int $offset = 0): Arrayz
    {
        if ($limit <= 0) {
            $this->items = [];
            return $this;
        }

        $this->ensureArray();
        $this->items = array_slice($this->items, $offset, $limit, true);
        return $this;
    }

    /**
     * Skip first n items
     */
    public function skip(int $offset): self
    {
        return new static(array_slice($this->items, $offset));
    }

    /**
     * Get slice of collection
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return new static(array_slice($this->items, $offset, $length));
    }

    /**
     * Split collection into chunks
     */
    public function chunk(int $size): self
    {
        $chunks = array_chunk($this->items, $size, true);
        return new static(array_map(fn($chunk) => new static($chunk), $chunks));
    }

    /**
     * Get unique items
     */
    public function unique(?string $key = null): self
    {
        if ($key === null) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }

        $seen = [];
        $result = [];

        foreach ($this->items as $item) {
            $value = $this->getValue($item, $key);
            if (!in_array($value, $seen, true)) {
                $seen[] = $value;
                $result[] = $item;
            }
        }

        return new static($result);
    }

    /**
     * Flatten multi-dimensional array
     */
    public function flatten(int $depth = INF): self
    {
        return new static($this->flattenArray($this->items, $depth));
    }

    /**
     * Merge with other collections or arrays
     */
    public function merge(array|self ...$collections): self
    {
        $merged = $this->items;

        foreach ($collections as $collection) {
            if ($collection instanceof self) {
                $merged = array_merge($merged, $collection->toArray());
            } else {
                $merged = array_merge($merged, $collection);
            }
        }

        return new static($merged);
    }

    /**
     * Combine with another array as keys
     */
    public function combine(array $keys): self
    {
        return new static(array_combine($keys, $this->items));
    }

    /**
     * Get the intersection with another collection
     */
    public function intersect(array|self $items): self
    {
        $compareArray = $items instanceof self ? $items->toArray() : $items;
        return new static(array_intersect($this->items, $compareArray));
    }

    /**
     * Get the difference with another collection
     */
    public function diff(array|self $items): self
    {
        $compareArray = $items instanceof self ? $items->toArray() : $items;
        return new static(array_diff($this->items, $compareArray));
    }

    /**
     * Reduce collection to single value
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    // ------------------ RETRIEVAL METHODS ------------------

    /**
     * Get first item
     */
    public function first(?callable $callback = null): mixed
    {

        if ($callback === null && is_array($this->items)) {
            return empty($this->items) ? null : reset($this->items);
        }

        if ($callback === null && is_iterable($this->items)) {
            foreach ($this->items as $item) {
                return $item;
            }
        }

        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Get last item
     */
    public function last(?callable $callback = null): mixed
    {
        if ($callback === null) {
            return empty($this->items) ? null : end($this->items);
        }

        $items = array_reverse($this->items, true);
        foreach ($items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Get item at specific index
     */
    public function get(int|string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    /**
     * Check if collection has specific key
     */
    public function has(int|string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Check if collection contains specific value
     */
    public function contains(mixed $value, ?string $key = null): bool
    {
        if ($key !== null) {
            return $this->pluck($key)->contains($value);
        }

        return in_array($value, $this->items, true);
    }

    /**
     * Search for value and return key
     */
    public function search(mixed $value, bool $strict = true): int|string|false
    {
        return array_search($value, $this->items, $strict);
    }

    /**
     * Get collection keys
     */
    public function keys(): self
    {
        return new static(array_keys($this->items));
    }

    /**
     * Get collection values
     */
    public function values(): self
    {
        return new static(array_values($this->items));
    }

    // ------------------ AGGREGATION METHODS ------------------

    /**
     * Count items
     */
    public function count(): int
    {
        if (is_array($this->items)) {
            return count($this->items);
        }

        if ($this->items instanceof Countable) {
            return $this->items->count();
        }

        if (is_iterable($this->items)) {
            return iterator_count($this->items);
        }

        return 0;
    }

    /**
     * Check if collection is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Check if collection is not empty
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get sum of values
     */
    public function sum(?string $key = null): int|float
    {
        if ($key !== null) {
            return $this->pluck($key)->sum();
        }

        return array_sum($this->items);
    }

    /**
     * Get average of values
     */
    public function avg(?string $key = null): int|float
    {
        $count = $this->count();
        return $count > 0 ? $this->sum($key) / $count : 0;
    }

    /**
     * Get median of values
     */
    public function median(?string $key = null): int|float|null
    {
        $values = $key !== null ? $this->pluck($key)->toArray() : $this->items;
        $count = count($values);

        if ($count === 0) {
            return null;
        }

        sort($values);
        $middle = floor($count / 2);

        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }

        return $values[$middle];
    }

    /**
     * Get minimum value
     */
    public function min(?string $key = null): mixed
    {
        if ($key !== null) {
            return $this->pluck($key)->min();
        }

        return empty($this->items) ? null : min($this->items);
    }

    /**
     * Get maximum value
     */
    public function max(?string $key = null): mixed
    {
        if ($key !== null) {
            return $this->pluck($key)->max();
        }

        return empty($this->items) ? null : max($this->items);
    }

    // ------------------ UTILITY METHODS ------------------

    /**
     * Execute callback for each item
     */
    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Transform collection using callback
     */
    public function transform(callable $callback): self
    {
        $this->items = array_map($callback, $this->items);
        return $this;
    }

    /**
     * Zip arrays together
     */
    public function zip(array ...$arrays): self
    {
        return new static(array_map(null, $this->items, ...$arrays));
    }

    /**
     * Determine if array is associative
     */
    public function isAssocArray(?array $array = null): bool
    {
        $array = $array ?? $this->items;

        if ([] === $array) {
            return true;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Determine if array is multidimensional
     */
    public function isMultiArray(?array $array = null): bool
    {
        $array = $array ?? $this->items;

        foreach ($array as $value) {
            if (is_array($value)) {
                return true;
            }
        }

        return false;
    }

    // ------------------ CONVERSION METHODS ------------------

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Convert to object
     */
    public function toObject($recursive = false)
    {
        return to_object($this->items, $recursive);
    }

    /**
     * Convert to JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->items, $options);
    }

    /**
     * Convert to query string
     */
    public function toQuery(): string
    {
        return http_build_query($this->items);
    }

    /**
     * JSON serialize interface
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }

    // ------------------ HELPER METHODS ------------------

    /**
     * Get value from item using dot notation
     */
    public function getValue(mixed $item, string $key): mixed
    {
        if (str_contains($key, '.')) {
            $keys = explode('.', $key);
            $value = $item;

            foreach ($keys as $k) {
                if (is_array($value) && array_key_exists($k, $value)) {
                    $value = $value[$k];
                } elseif (is_object($value) && property_exists($value, $k)) {
                    $value = $value->$k;
                } else {
                    return null;
                }
            }

            return $value;
        }

        if (is_array($item)) {
            return $item[$key] ?? null;
        }

        if (is_object($item)) {
            return $item->$key ?? null;
        }

        return null;
    }

    /**
     * Ensure data is array
     */
    protected function arrayfy(mixed $data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_object($data)) {
            if (method_exists($data, 'result_array')) {
                return $data->result_array();
            }
            return (array) $data;
        }

        if (is_string($data) && function_exists('is_json') && is_json($data)) {
            return json_decode($data, true);
        }

        return (array) $data;
    }

    /**
     * Flatten array recursively
     */
    protected function flattenArray(array $array, int $depth): array
    {
        if ($depth === 0) {
            return $array;
        }

        $result = [];

        foreach ($array as $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $depth - 1));
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }

    // ------------------ INTERFACE IMPLEMENTATIONS ------------------

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    public function current(): mixed
    {
        return current($this->items);
    }

    public function key(): mixed
    {
        return key($this->items);
    }

    public function next(): void
    {
        next($this->items);
    }

    public function rewind(): void
    {
        reset($this->items);
    }

    public function valid(): bool
    {
        return key($this->items) !== null;
    }

    // ------------------ MAGIC METHODS ------------------

    public function __toString(): string
    {
        return $this->toJson();
    }

    public function __debugInfo(): array
    {
        return $this->items;
    }
}

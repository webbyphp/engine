<?php

namespace Base\Http;

/**
 * HTTP Method Enum
 * 
 * Provides type-safe HTTP method constants for API controllers
 * 
 * @author Developer Kwame
 * @since 1.0.0
 */
class HttpMethod
{
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const PATCH = 'PATCH';
    const HEAD = 'HEAD';
    const OPTIONS = 'OPTIONS';

    /**
     * Get all supported HTTP methods
     * Returns an array containing all the HTTP 
     * methods supported by the framework.
     *
     * @return array An array of strings, each representing a HTTP method.
     */
    public static function getAllMethods(): array
    {
        return [
            self::GET,
            self::POST,
            self::PUT,
            self::DELETE,
            self::PATCH,
            self::HEAD,
            self::OPTIONS
        ];
    }

    /**
     * Check if a method is valid
     *
     * @param string $method
     * @return bool
     */
    public static function isValid(string $method): bool
    {
        return in_array(strtoupper($method), self::getAllMethods(), true);
    }

    /**
     * Normalize method name to uppercase
     *
     * @param string $method
     * @return string
     */
    public static function normalize(string $method): string
    {
        return strtoupper($method);
    }

    /**
     * Get method for lowercase usage
     *
     * @param string $method
     * @return string
     */
    public static function lower(string $method): string
    {
        return strtolower($method);
    }
}

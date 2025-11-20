<?php

namespace Base\Taps;

use Base\Models\BaseModel;
use Base\Taps\ToTap;

class Orm
{
    protected static $instance = BaseModel::class;

    /**
     * Holds the User ID for the next 
     * static ORM operation.
     * @var mixed
     */
    public static $currentUserId = null;

    /**
     * Set the user ID to be used for created_by/updated_by 
     * on the next static call.
     *
     * This ID takes precedence over session and 
     * global providers for this operation.
     * @param mixed $userId The User ID (int, string, or UUID).
     * @return static
     */
    public static function asUser(mixed $userId): static
    {
        self::$currentUserId = $userId;
        return new static;
    }

    public static function use(string $model = '')
    {
        self::$instance = $model;
        return new static;
    }

    public static function __callStatic(string $method, array $arguments)
    {
        return ToTap::make(self::$instance, $method, $arguments);
    }
}

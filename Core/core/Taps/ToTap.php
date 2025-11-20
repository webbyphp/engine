<?php

namespace Base\Taps;

use Base\Taps\MakeTap;

/**
 * The ToTap class extends 
 * the MakeTap Abstract class.
 *
 * @author Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 */
class ToTap extends MakeTap
{

    /**
     * Holds the instances of the classes.
     * @var array
     */
    protected static $classInstances = [];

    /**
     * Set constructor visibility to private
     */
    private function __construct() {}

    /**
     * Returns the fully qualified class name.
     *
     * @return string
     */
    public static function getFullyQualifiedClass()
    {
        return '';
    }

    /**
     * Get the constructor arguments.
     *
     * @return array
     */
    public static function getConstructorArguments()
    {
        return [];
    }

    /**
     * Create Static Method
     *
     * @param string|object $instance The instance to call the method on
     * @param string $method The method to call
     * @param array $arguments The arguments to pass to the method
     * @param bool $isService Indicates whether $instance is a service identifier
     * @return mixed The result of the method call
     */
    public static function make(
        string|object $instance,
        string $method,
        array $arguments,
        bool $isService = false
    ): mixed {

        $instance = ($isService) ? service($instance) : app($instance);

        if ($instance instanceof \Base\Models\BaseModel && Orm::$currentUserId !== null) {
            $instance->asUser(Orm::$currentUserId);
            Orm::$currentUserId = null;
        }

        if (count($arguments) <= 5) {
            return $instance->$method(...$arguments);
        }

        return call_user_func_array([$instance, $method], $arguments);
    }

    /**
     * Calls root class with `$arguments` 
     * as parameters and returns its result
     *
     * @param  string $method
     * @param  mixed  $arguments
     *
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic(string $method, mixed $arguments): mixed
    {
        $classInstance = static::getFullyQualifiedClass();

        if (!isset(self::$classInstances[$classInstance])) {
            self::$classInstances[$classInstance] = new $classInstance(static::getConstructorArguments());
        }

        $objectMethod = array(self::$classInstances[$classInstance], $method);

        if (is_callable($objectMethod)) {
            return call_user_func_array($objectMethod, $arguments);
        }

        throw new \BadMethodCallException("Method $method does not exist");
    }
}

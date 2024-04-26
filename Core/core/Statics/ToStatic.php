<?php

namespace Base\Statics;

use Base\Statics\AbstractToStatic;

/**
 * The ToStatic class extends the AbstractToStatic class.
 *
 * This class is responsible for providing static method calls on instances,
 * getting the fully qualified class name, getting constructor arguments, and
 * calling methods on the root class with given arguments. It also handles the
 * case where the method does not exist by throwing a BadMethodCallException.
 *
 * @author Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 */
class ToStatic extends AbstractToStatic
{
    
    /**
     * Holds the instances of the classes.
     * 
     * Each class is associated with an instance, which is stored in this array.
     * 
     * Keys are fully qualified class names and values are instances of the classes.
     * 
     * @var array Array of instances of the classes.
     */
    protected static $classInstances = [];

    /**
     * Since we don't need a constructor for the 
     * proxy class, we just set its visibility to private
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
     * @param string|object $instance The instance to call the method on, 
     * or the service identifier if $isService is true
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

        // Get the instance from the service container 
        // if $isService is true, otherwise use the app container
        $instance = ($isService) ? service($instance) : app($instance);

        // If the number of arguments is less than or equal to 5, 
        // use the spread operator to call the method
        if (count($arguments) <= 5) {
            return $instance->$method(...$arguments);
        }

        // If the number of arguments is greater than 5, 
        // use call_user_func_array to call the method
        return call_user_func_array([$instance, $method], $arguments);
    }

    /**
     * This method calls the root class with `$arguments` 
     * as parameters and returns its result
     *
     * @param  string $method
     * @param  mixed  $arguments
     *
     * @return mixed
     *
     * @throws \BadMethodCallException If an undefined method is called
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

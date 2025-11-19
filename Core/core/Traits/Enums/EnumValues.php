<?php

namespace Base\Traits\Enums;

/**
 * Trait to support all enums
 */
trait EnumValues
{

    /**
     * Get Values
     *
     * @return object
     */
    public static function getValues()
    {

        $cases = [];

        if (method_exists(self::class, 'cases')) {
            // Handle PHP 8.1+ Enums
            foreach (self::cases() as $case) {
                $cases[] = ['name' => $case->name, 'value' => $case->value];
            }
        } else {
            // Handle class constants
            $reflection = new \ReflectionClass(self::class);
            $constants = $reflection->getConstants();
            foreach ($constants as $name => $value) {
                $cases[] = ['name' => $name, 'value' => $value];
            }
        }

        return arrayz($cases)->pluck('value')->get();
    }

    /**
     * Has Value
     *
     * @param string $value
     * @return bool
     */
    public static function hasValue(string $value)
    {
        return arrayz(self::getValues())->contains('value', $value);
    }
}

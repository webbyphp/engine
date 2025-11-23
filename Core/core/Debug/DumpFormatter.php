<?php

namespace Base\Debug;

use ReflectionClass;

class DumpFormatter
{
    private static $maxDepth = 10;
    private static $seen = [];

    public static function getTypeDisplay($var)
    {
        $type = gettype($var);

        switch ($type) {
            case 'object':
                return get_class($var);
            case 'array':
                return 'array(' . count($var) . ')';
            case 'string':
                return 'string(' . strlen($var) . ')';
            case 'integer':
                return 'int';
            case 'double':
                return 'float';
            case 'boolean':
                return 'bool';
            case 'NULL':
                return 'null';
            case 'resource':
                return 'resource';
            default:
                return $type;
        }
    }

    public static function format($var, $depth = 0, $isProperty = false)
    {
        if ($depth > self::$maxDepth) {
            return '<span class="pp-keyword">...</span>';
        }

        $type = gettype($var);
        $indent = str_repeat('<span class="pp-indent"></span>', $depth);

        switch ($type) {
            case 'NULL':
                return '<span class="pp-null">null</span>';

            case 'boolean':
                return '<span class="pp-bool">' . ($var ? 'true' : 'false') . '</span>';

            case 'integer':
            case 'double':
                return '<span class="pp-number">' . $var . '</span>';

            case 'string':
                $escaped = htmlspecialchars($var);
                if (strlen($var) > 100) {
                    $escaped = htmlspecialchars(substr($var, 0, 100)) .
                        '<span class="pp-keyword">...</span>';
                }
                return '<span class="pp-string">"' . $escaped . '"</span>';

            case 'array':
                return self::formatArray($var, $depth);

            case 'object':
                return self::formatObject($var, $depth);

            case 'resource':
                return '<span class="pp-keyword">resource(' . get_resource_type($var) . ')</span>';

            default:
                return '<span class="pp-keyword">' . htmlspecialchars(print_r($var, true)) . '</span>';
        }
    }

    private static function formatArray($array, $depth)
    {
        if (empty($array)) {
            return '<span class="pp-array-bracket">[</span><span class="pp-array-bracket">]</span>';
        }

        $output = '<span class="pp-array-bracket">[</span>' . "\n";
        $indent = str_repeat('<span class="pp-indent"></span>', $depth + 1);
        $count = 0;

        foreach ($array as $key => $value) {
            if ($count >= 100) {
                $output .= $indent . '<span class="pp-keyword">... ' . (count($array) - $count) . ' more items</span>' . "\n";
                break;
            }

            $output .= $indent;
            $output .= '<span class="pp-array-key">';
            $output .= is_string($key) ? '"' . htmlspecialchars($key) . '"' : $key;
            $output .= '</span>';
            $output .= ' <span class="pp-operator">=></span> ';
            $output .= self::format($value, $depth + 1);
            $output .= "\n";
            $count++;
        }

        $output .= str_repeat('<span class="pp-indent"></span>', $depth) . '<span class="pp-array-bracket">]</span>';
        return $output;
    }

    private static function formatObject($object, $depth)
    {
        $hash = spl_object_hash($object);

        // Check for circular reference
        if (in_array($hash, self::$seen)) {
            return '<span class="pp-object">' . get_class($object) . '</span> <span class="pp-keyword">*RECURSION*</span>';
        }

        self::$seen[] = $hash;

        $className = get_class($object);
        $reflection = new ReflectionClass($object);
        $properties = [];

        // Get all properties (public, protected, private)
        foreach ($reflection->getProperties() as $prop) {
            // $prop->setAccessible(true);
            $visibility = $prop->isPublic() ? 'public' : ($prop->isProtected() ? 'protected' : 'private');
            $properties[] = [
                'name' => $prop->getName(),
                'value' => $prop->getValue($object),
                'visibility' => $visibility
            ];
        }

        $output = '<span class="pp-object">' . htmlspecialchars($className) . '</span> <span class="pp-operator">{</span>' . "\n";
        $indent = str_repeat('<span class="pp-indent"></span>', $depth + 1);
        $count = 0;

        foreach ($properties as $prop) {
            if ($count >= 50) {
                $output .= $indent . '<span class="pp-keyword">... ' . (count($properties) - $count) . ' more properties</span>' . "\n";
                break;
            }

            $output .= $indent;
            $output .= '<span class="pp-visibility">' . $prop['visibility'] . '</span> ';
            $output .= '<span class="pp-property">$' . htmlspecialchars($prop['name']) . '</span>';
            $output .= ' <span class="pp-operator">=</span> ';
            $output .= self::format($prop['value'], $depth + 1, true);
            $output .= "\n";
            $count++;
        }

        $output .= str_repeat('<span class="pp-indent"></span>', $depth) . '<span class="pp-operator">}</span>';

        // Remove from seen after processing
        array_pop(self::$seen);

        return $output;
    }

    public static function formatOutput($variable_name, $exported, $original)
    {
        // Detect the type
        $type = gettype($original);

        // Apply syntax highlighting
        $output = $exported;

        // Highlight strings
        $output = preg_replace("/'([^']*?)'/", '<span class="dump-string">\'$1\'</span>', $output);

        // Highlight numbers
        $output = preg_replace('/\b(\d+\.?\d*)\b/', '<span class="dump-number">$1</span>', $output);

        // Highlight keywords (true, false, null, array, object)
        $output = preg_replace('/\b(true|false|null|NULL|TRUE|FALSE)\b/', '<span class="dump-keyword">$1</span>', $output);
        $output = preg_replace('/\b(array|Array)\s*\(/', '<span class="dump-keyword">$1</span> <span class="dump-operator">(</span>', $output);

        // Highlight object classes
        $output = preg_replace('/\b([A-Z][a-zA-Z0-9_\\\\]*)::/m', '<span class="dump-object">$1</span><span class="dump-operator">::</span>', $output);
        $output = preg_replace('/\b([A-Z][a-zA-Z0-9_\\\\]*)\s+Object/m', '<span class="dump-object">$1</span> Object', $output);

        // Highlight operators and symbols
        $output = str_replace('=>', '<span class="dump-operator">=></span>', $output);
        $output = str_replace('(', '<span class="dump-operator">(</span>', $output);
        $output = str_replace(')', '<span class="dump-operator">)</span>', $output);
        $output = str_replace('[', '<span class="dump-operator">[</span>', $output);
        $output = str_replace(']', '<span class="dump-operator">]</span>', $output);

        // Highlight property names (word followed by =>)
        $output = preg_replace('/(\w+)\s*<span class="dump-operator">=&gt;<\/span>/', '<span class="dump-property">$1</span> <span class="dump-operator">=></span>', $output);

        // Add variable name at the top
        $formatted = '<span class="dump-variable">' . htmlspecialchars($variable_name) . '</span> <span class="dump-operator">=</span>' . "\n\n" . $output;

        return $formatted;
    }
}

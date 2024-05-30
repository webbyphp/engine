<?php

/**
 * Check Class File and get it's content 
 * 
 * Credit: https://stackoverflow.com/questions/7153000/get-class-name-from-file
 * 
 * Version - 1.0.0
 */

namespace Base\Helpers;

class TraverseClassFile
{
    /**
     * Get the full class name (name \ namespace) of a class from its file path
     * result example: (string) "I\Am\The\Namespace\Of\This\Class"
     *
     * @param $filePathName
     *
     * @return  string
     * @todo fix namespace resolving issue
     */
    public function getClassFullNameFromFile($filePathName)
    {
        return $this->getClassNamespaceFromFile($filePathName) . '' . $this->getClassNameFromFile($filePathName);
    }

    /**
     * Build and return an object of a 
     * class from its file path
     *
     * @param $filePathName
     *
     * @return  mixed
     */
    public function getClassObjectFromFile($filePathName)
    {
        $classString = $this->getClassFullNameFromFile($filePathName);

        $object = new $classString;

        return $object;
    }

    /**
     * Get the class namespace form file path using token
     *
     * @param $filePathName
     *
     * @return  null|string
     */
    protected function getClassNamespaceFromFile($filePathName)
    {
        $src = file_get_contents($filePathName);

        $tokens = token_get_all($src);
        $count = count($tokens);
        $i = 0;
        $namespace = '';
        $namespaceOk = false;

        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                // Found namespace declaration
                while (++$i < $count) {
                    if ($tokens[$i] === ';') {
                        $namespaceOk = true;
                        $namespace = trim($namespace);
                        break;
                    }
                    $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }
                break;
            }
            $i++;
        }

        if (!$namespaceOk) {
            return null;
        } else {
            return $namespace;
        }
    }

    /**
     * Get the class name form file path using token
     * if class is anonymous include the file path
     * 
     * @param $filePathName
     *
     * @return  mixed
     */
    protected function getClassNameFromFile($filePathName)
    {
        $src = file_get_contents($filePathName);

        $classes = array();
        $tokens = token_get_all($src);
        $count = count($tokens);

        for ($i = 2; $i < $count; $i++) {
            if (
                $tokens[$i - 2][0] == T_CLASS
                && $tokens[$i - 1][0] == T_WHITESPACE
                && $tokens[$i][0] == T_STRING
            ) {
                $className = $tokens[$i][1];
                $classes[] = $className;
            }
            // Anonymous class found
            else if ($tokens[$i - 1][0] == T_NEW) {
                $className = include $filePathName;
                $classes[] = $className::class;
            }
        }

        return $classes[0];
    }

}

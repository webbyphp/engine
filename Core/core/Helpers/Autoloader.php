<?php

namespace Base\Helpers;

/**
 * PSR-4 Autoloader for Webby App Directory
 * 
 * @author  Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 * Inspired by Nick Tsai <myintaer@gmail.com>
 * @see https://github.com/yidas/codeigniter-psr4-autoload
 * 
 */
class Autoloader
{
    /**
     * @var string Nampsapce prefix refered to App Root
     */
    const string DEFAULT_PREFIX = "App";

    public static function autoload($classe): void
    {
        $namespace = static::getPrincipalNamespace($classe);

        $namespace = APPROOT . $namespace . '.php';
        $namespace = str_replace('\\', DS, $namespace);

        if (! file_exists($namespace) || is_dir($namespace)) {
            throw new \Exception("******* This Class don't exists!! --->>>> " . $namespace . "*********\n");
        }

        require $namespace;
    }

    protected static function getPrincipalNamespace($namespace): ?string
    {
        $configs = (array) static::getConfigIfExists();

        if (!isset($configs)) {
            return  $namespace;
        }

        $principalNamespace = explode('\\', $namespace)[0];

        foreach ($configs as $key => $value) {
            $keyReplaced = str_replace('\\', '', $key);

            if ($principalNamespace == $keyReplaced) {
                return str_replace($key, $value, $namespace);
            }
        }

        return $namespace;
    }

    protected static function getConfigIfExists(): ?object
    {
        $maping = static::getWebbyJsonToObject();
        return $maping->autoload ?? null;
    }

    protected static function getWebbyJsonToObject(): ?object
    {
        $file = ROOTPATH . 'webby.json';
        return (file_exists($file)) ? json_decode(file_get_contents($file)) : null;
    }
}

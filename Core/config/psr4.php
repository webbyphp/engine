<?php

/**
 * Nampspace prefix refered to APPROOT
 */
const APP_PREFIX = "app";

/**
 * App root Psr4 Autoloader
 * 
 */
function app_psr4_autoload()
{
    $prefix = ucfirst(APP_PREFIX);

    spl_autoload_register(function ($classname) use ($prefix) {

        // Prefix check
        if (strpos($classname, "{$prefix}\\")===0) {
            
            // Locate class relative path
            $classname = str_replace("{$prefix}\\", "", $classname);
            $filepath = APPROOT.  str_replace('\\', DIRECTORY_SEPARATOR, ltrim($classname, '\\')) . '.php';
            
            if (file_exists($filepath)) {
                require $filepath;
            }
        }
    });
}

spl_autoload_register('app_psr4_autoload');

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

        include ROOTPATH . '/config/autoload.php';

        if (isset($psr4)) {

            $psr4['App'] = ''; // Default App Namespace

            foreach ($psr4 as $namespace => $classpath) {

                $namespace = rtrim($namespace, '\\') . '\\';
                $classpath = APPROOT . trim($classpath, '/') . '/';

                if (str_starts_with($classname, $namespace)) {

                    $classname = substr($classname, strlen($namespace));
                    $filepath = $classpath . str_replace('\\', '/', $classname) . '.php';

                    if (file_exists($filepath)) {
                        require_once $filepath;
                        return;
                    }
                }
            }
        }
    });
}

app_psr4_autoload();

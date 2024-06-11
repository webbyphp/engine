<?php
defined('COREPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Controller Namespace
|--------------------------------------------------------------------------
|
| This item allows you to set namespace to controllers.
|
|	$config['controller_namespace'] = 'App\\Controllers';
*/
$config['controller_namespace'] = 'App\\Controllers';

/*
|--------------------------------------------------------------------------
| Include Default Configuration file
|--------------------------------------------------------------------------
|
 */
include_once ROOTPATH . 'config/config.php';

/*
|--------------------------------------------------------------------------
| Load Application Configuration files
|--------------------------------------------------------------------------
|
 */
include_once COREPATH . 'config/configurator.php';

/*
|--------------------------------------------------------------------------
| HMVC Configuration File
|--------------------------------------------------------------------------
|
 */
include_once ROOTPATH . 'bootstrap/modular.php';

/*
|--------------------------------------------------------------------------
| Migrations Configuration File
|--------------------------------------------------------------------------
|
 */
include_once COREPATH . 'config/migration.php';

/*
|--------------------------------------------------------------------------
| Json Database Configuration File
|--------------------------------------------------------------------------
|
 */
include_once ROOTPATH . 'database/jsondb.php';

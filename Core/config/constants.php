<?php
defined('COREPATH') or exit('No direct script access allowed');

/*
 * These constants are defined to help make some
 * work arounds simple
 */

if ( ! defined('DS')) define('DS', DIRECTORY_SEPARATOR);

/*
 * --------------------------------------------------------------------
 * Define Webby Version
 * --------------------------------------------------------------------
 */
define('WEBBY_VERSION', '2.12.4');

/*
 * --------------------------------------------------------------------
 * Define Author Name And Alias
 * --------------------------------------------------------------------
 */
define('WEBBY_AUTHOR', 'Kwame Oteng Appiah-Nti');
define('WEBBY_AUTHOR_AKA', 'Developer Kwame');

/*
|--------------------------------------------------------------------------
| App Constants 
|--------------------------------------------------------------------------
|
| A set of constants that Webby uses to define its core
|
*/
require_once ROOTPATH.'config/constants.php';

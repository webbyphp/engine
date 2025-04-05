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
| Base Site URL
|--------------------------------------------------------------------------
|
| URL to your CodeIgniter root. Typically this will be your base URL,
| WITH a trailing slash:
|
|	http://example.com/
|
| WARNING: You MUST set this value!
|
| If it is not set, then CodeIgniter will try guess the protocol and path
| your installation, but due to security concerns the hostname will be set
| to $_SERVER['SERVER_ADDR'] if available, or localhost otherwise.
| The auto-detection mechanism exists only for convenience during
| development and MUST NOT be used in production!
|
| If you need to allow multiple domains, remember that this file is still
| a PHP script and you can easily do that on your own.
|
*/
$config['base_url'] = APP_BASE_URL;

/*
|--------------------------------------------------------------------------
| Index File
|--------------------------------------------------------------------------
|
| Typically this will be your index.php file, unless you've renamed it to
| something else. If you are using mod_rewrite to remove the page set this
| variable so that it is blank.
|
*/
$config['index_page'] = '';

/*
|--------------------------------------------------------------------------
| Class Extension Prefix
|--------------------------------------------------------------------------
|
| This item allows you to set the filename/classname prefix when extending
| native libraries.  For more information please see the user guide:
|
| https://codeigniter.com/user_guide3/general/core_classes.html
| https://codeigniter.com/user_guide3/general/creating_libraries.html
|
*/
$config['subclass_prefix'] = 'Base_';

/*
|--------------------------------------------------------------------------
| Controller Suffix
|--------------------------------------------------------------------------
| This is a quick fix for PHP8.1 on Modules
| Do not change the default value 
| which is an empty string if you 
| do not know what you doing.
|
*/
$config['controller_suffix'] = '';

/*
|--------------------------------------------------------------------------
| Composer auto-loading
|--------------------------------------------------------------------------
|
| Enabling this setting will tell CodeIgniter to look for a Composer
| package auto-loader script in application/vendor/autoload.php.
|
|	$config['composer_autoload'] = true;
|
| Or if you have your vendor/ directory located somewhere else, you
| can opt to set a specific path as well:
|
|	$config['composer_autoload'] = '/path/to/vendor/autoload.php';
|
| For more information about Composer, please visit http://getcomposer.org/
|
| Note: This will NOT disable or override the CodeIgniter-specific
|	autoloading (application/config/autoload.php)
*/
$config['composer_autoload'] = false; //realpath(ROOTPATH . 'vendor/autoload.php');

/*
|--------------------------------------------------------------------------
| Error Logging Threshold
|--------------------------------------------------------------------------
|
| You can enable error logging by setting a threshold over zero. The
| threshold determines what gets logged. Threshold options are:
|
|	0 = Disables logging, Error logging TURNED OFF
|	1 = Error Messages (including PHP errors)
|	2 = Debug Messages
|	3 = Informational Messages
|	4 = All Messages
|
| You can also pass an array with threshold levels to show individual error types
|
| 	[2] = Debug Messages, without Error Messages
|
| For a live site you'll usually only enable Errors (1) to be logged otherwise
| your log files will fill up very fast.
|
*/
$config['log_threshold'] = LOG_LEVEL;

/*
|--------------------------------------------------------------------------
| Error Logging Directory Path
|--------------------------------------------------------------------------
|
| Leave this BLANK unless you would like to set something other than the default
| application/logs/ directory. Use a full server path with trailing slash.
|
*/
$config['log_path'] = LOG_PATH;

/*
|--------------------------------------------------------------------------
| Log File Extension
|--------------------------------------------------------------------------
|
| The default filename extension for log files. The default 'php' allows for
| protecting the log files via basic scripting, when they are to be stored
| under a publicly accessible directory.
|
| Note: Leaving it blank will default to 'php'.
|
*/
$config['log_file_extension'] = LOG_FILE_EXTENSION;

/*
|--------------------------------------------------------------------------
| Log File Permissions
|--------------------------------------------------------------------------
|
| The file system permissions to be applied on newly created log files.
|
| IMPORTANT: This MUST be an integer (no quotes) and you MUST use octal
|            integer notation (i.e. 0700, 0644, etc.)
*/
$config['log_file_permissions'] = LOG_PERMISSION;

/*
|--------------------------------------------------------------------------
| Date Format for Logs
|--------------------------------------------------------------------------
|
| Each item that is logged has an associated date. You can use PHP date
| codes to set your own date formatting
|
*/
$config['log_date_format'] = LOG_DATE_FORMAT;

/*
|--------------------------------------------------------------------------
| Encryption Key
|--------------------------------------------------------------------------
|
| If you use the Encryption class, you must set an encryption key.
| See the user guide for more info.
|
| https://codeigniter.com/user_guide3/libraries/encryption.html
|
*/
$config['encryption_key'] = APP_ENCRYPTION_KEY;

/*
|--------------------------------------------------------------------------
| Session Variables
|--------------------------------------------------------------------------
|
| 'sess_driver'
|
|	The storage driver to use: files, database, redis, memcached
|
| 'sess_cookie_name'
|
|	The session cookie name, must contain only [0-9a-z_-] characters
|
| 'sess_expiration'
|
|	The number of SECONDS you want the session to last.
|	Setting to 0 (zero) means expire when the browser is closed.
|
| 'sess_save_path'
|
|	The location to save sessions to, driver dependent.
|
|	For the 'files' driver, it's a path to a writable directory.
|	WARNING: Only absolute paths are supported!
|
|	For the 'database' driver, it's a table name.
|	Please read up the manual for the format with other session drivers.
|
|	IMPORTANT: You are REQUIRED to set a valid save path!
|
| 'sess_match_ip'
|
|	Whether to match the user's IP address when reading the session data.
|
|	WARNING: If you're using the database driver, don't forget to update
|	         your session table's PRIMARY KEY when changing this setting.
|
| 'sess_time_to_update'
|
|	How many seconds between CI regenerating the session ID.
|
| 'sess_regenerate_destroy'
|
|	Whether to destroy session data associated with the old session ID
|	when auto-regenerating the session ID. When set to false, the data
|	will be later deleted by the garbage collector.
|
| Other session cookie settings are shared with the rest of the application,
| except for 'cookie_prefix' and 'cookie_httponly', which are ignored here.
|
*/
$config['sess_driver']             = SESSION_DRIVER;
$config['sess_cookie_name']        = SESSION_COOKIE_NAME;
$config['sess_samesite']           = SESSION_SAMESITE;
$config['sess_expiration']         = SESSION_EXPIRATION;
$config['sess_save_path']          = SESSION_SAVE_PATH;
$config['sess_match_ip']           = SESSION_MATCH_IP;
$config['sess_time_to_update']     = SESSION_TIME_TO_UPDATE;
$config['sess_regenerate_destroy'] = SESSION_REGENERATE_DESTROY;

/*
|--------------------------------------------------------------------------
| Cookie Related Variables
|--------------------------------------------------------------------------
|
| 'cookie_prefix'   = Set a cookie name prefix if you need to avoid collisions
| 'cookie_domain'   = Set to .your-domain.com for site-wide cookies
| 'cookie_path'     = Typically will be a forward slash
| 'cookie_secure'   = Cookie will only be set if a secure HTTPS connection exists.
| 'cookie_httponly' = Cookie will only be accessible via HTTP(S) (no javascript)
| 'cookie_samesite' = Identify whether or not to allow a cookie to be accessed. 
|					  SameSite attribute include 'Strict', 'Lax', or 'None' (The first character must be an uppercase letter)
|
|    				  'Lax' enables only first-party cookies to be sent/accessed
|					  'Strict' is a subset of 'lax' and won’t fire if the incoming link is from an external site
|    				  'None' signals that the cookie data can be shared with third parties/external sites
|
| Note: These settings (with the exception of 'cookie_prefix' and
|       'cookie_httponly') will also affect sessions.
|
*/
$config['cookie_prefix']    = COOKIE_PREFIX;
$config['cookie_domain']    = COOKIE_DOMAIN;
$config['cookie_path']      = COOKIE_PATH;
$config['cookie_secure']    = COOKIE_SECURE;
$config['cookie_httponly']  = COOKIE_HTTPONLY;
$config['cookie_samesite']  = COOKIE_SAMESITE;

/*
|--------------------------------------------------------------------------
| Cross Site Request Forgery
|--------------------------------------------------------------------------
| Enables a CSRF cookie token to be set. When set to true, token will be
| checked on a submitted form. If you are accepting user data, it is strongly
| recommended CSRF protection be enabled.
|
| 'csrf_token_name' = The token name
| 'csrf_cookie_name' = The cookie name
| 'csrf_expire' = The number in seconds the token should expire.
| 'csrf_regenerate' = Regenerate token on every submission
| 'csrf_exclude_uris' = Array of URIs which ignore CSRF checks
*/
$config['csrf_protection']   = CSRF_PROTECTION;
$config['csrf_token_name']   = CSRF_TOKEN_NAME;
$config['csrf_cookie_name']  = CSRF_COOKIE_NAME;
$config['csrf_expire']       = CSRF_EXPIRE;
$config['csrf_regenerate']   = CSRF_REGENERATE;
$config['csrf_exclude_uris'] = CSRF_EXCLUDE_URIS;

/*
|--------------------------------------------------------------------------
| Output Compression
|--------------------------------------------------------------------------
|
| Enables Gzip output compression for faster page loads.  When enabled,
| the output class will test whether your server supports Gzip.
| Even if it does, however, not all browsers support compression
| so enable only if you are reasonably sure your visitors can handle it.
|
| Only used if zlib.output_compression is turned off in your php.ini.
| Please do not use it together with httpd-level output compression.
|
| VERY IMPORTANT:  If you are getting a blank page when compression is enabled it
| means you are prematurely outputting something to your browser. It could
| even be a line of whitespace at the end of one of your scripts.  For
| compression to work, nothing can be sent before the output buffer is called
| by the output class.  Do not 'echo' any values with compression enabled.
|
*/
$config['compress_output'] = false;

/*
|--------------------------------------------------------------------------
| Rewrite PHP Short Tags
|--------------------------------------------------------------------------
|
| If your PHP installation does not have short tag support enabled CI
| can rewrite the tags on-the-fly, enabling you to utilize that syntax
| in your view files.  Options are true or false (boolean)
|
| Note: You need to have eval() enabled for this to work.
|
*/
$config['rewrite_short_tags'] = false;

/*
|--------------------------------------------------------------------------
| Cache Path
|--------------------------------------------------------------------------
|
| Main directory for all cache files
|
*/
$config['cache_path'] = CACHE_PATH;

/*
|--------------------------------------------------------------------------
| Web Catch Path 
|--------------------------------------------------------------------------
|
| Main directory for web cache files
|
*/
$config['web_cache_path'] = WEB_CACHE_PATH . 'app';

/*
|--------------------------------------------------------------------------
| Plates Cache Path
|--------------------------------------------------------------------------
|
| Main directory for plate template cache files
|
*/
$config['plates_cache_path'] = WEB_CACHE_PATH . 'plates';

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

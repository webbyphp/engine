<?php

/**
 * WebbyInstance class
 *
 * Creates a CodeIgniter 3 or WebbyPHP instance without overhead like URL processing, etc.
 * Suitable for accessing session or another native Webby's or CI's resources
 * in the same manner as using $this variable.
 */
class WebbyInstance
{
    
    /**
     * Version of the library
     */
    const VERSION = '1.0.0';
    
    /**
     * Internal storage of CodeIgniter 3 
     * super object instance
     * 
     * @static
     * @access protected
     */
    protected static $instance;
    
    /**
     * Initializes the CodeIgniter 3 
     * super object instance
     * 
     * @static
     * @access public
     * @param string $cipath       Absolute path to CodeIgniter Framework directory
     * @param string $corepath     Absolute path to Webby Core directory
     * @param string $environment  Optional environment of the Webby or CodeIginiter instance
     * @param mixed $configs       Optional config from index.php if it is used
     * @throws WebbyInstanceException if the instance is already initialized or unreachable paths provided
     * @return object CodeIgniter or Webby instance
     */
    public static function init(
        $basepath = '', 
        $apppath = '', 
        $environment = 'testing', 
        $configs = []
    ){

        if (self::$instance) {
            throw new WebbyInstanceException(message: 'Webby instance is already initialized');
        }
        
        if (!is_dir($basepath)) {
            throw new WebbyInstanceException('Supplied base path is not a directory');
        } else {
            $basepath = rtrim($basepath, '/').'/';
        }
        
        if (!is_dir($apppath)) {
            throw new WebbyInstanceException('Supplied application path is not a directory');
        } else {
            $apppath = rtrim($apppath, '/').'/';
        }

        define('BASEPATH', $basepath);
        define('APPPATH', $apppath);
        define('CIPATH', BASEPATH);
        define('COREPATH', APPPATH);
        define('ROOTPATH', __DIR__ . '/../../../../../../');
        define('APPROOT', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
        define('VIEWPATH', APPROOT . 'Views' . DIRECTORY_SEPARATOR);
        define('THIRDPARTYPATH', APPROOT . 'ThirdParty' . DIRECTORY_SEPARATOR);
        define('WRITABLEPATH', ROOTPATH . 'writable' . DIRECTORY_SEPARATOR);
        define('CONSOLEPATH', APPROOT . 'Console' . DIRECTORY_SEPARATOR);
        define('WEBPATH', APPROOT . 'Web' . DIRECTORY_SEPARATOR);
        define('APIPATH', APPROOT . 'Api' . DIRECTORY_SEPARATOR);
        define('PACKAGEPATH', APPROOT . 'Packages' . DIRECTORY_SEPARATOR);
        define('COMPOSERPATH', ROOTPATH . 'vendor' . DIRECTORY_SEPARATOR);
        define('ASSETS', ROOTPATH . 'public/assets' . DIRECTORY_SEPARATOR);
        define('UPLOADPATH', WRITABLEPATH . 'uploads' . DIRECTORY_SEPARATOR);

        define('ICONV_ENABLED', extension_loaded('iconv'));

        define('EXT', '.php');

        define('ENVIRONMENT', $environment ? $environment : 'development');

        require(BASEPATH.'core/Common.php');

        if (file_exists(ROOTPATH.'config/'.ENVIRONMENT.'/constants.php')) {
            require(ROOTPATH.'config/'.ENVIRONMENT.'/constants.php');
        } else {
            require(ROOTPATH.'config/constants.php');
        }

        // Load environment settings from .env files
        // into $_SERVER and $_ENV
        require_once COREPATH . 'DotEnv.php';

        $env = new DotEnv(ROOTPATH);
        $env->load();

        $GLOBALS['CFG'] = load_class('Config', 'core');
        // $GLOBALS['CFG']->_assign_to_config($assign_to_config);
        $GLOBALS['UNI'] = load_class('Utf8', 'core');

        $GLOBALS['MARK'] = load_class('Benchmark', 'core');

        if (file_exists($basepath.'core/Security.php')) {
            $GLOBALS['SEC'] = load_class('Security', 'core');
        }

        load_class('Loader', 'core');

        load_class('Router', 'core');

        load_class('Input', 'core');

        load_class('Lang', 'core');
        
        load_class('Output', 'core');

        if ($configs['core_directory']) {
            $GLOBALS['core_directory'] = $configs['core_directory'];
        }

        if ($configs['ci_directory']) {
            $GLOBALS['ci_directory'] = $configs['ci_directory'];
        }

        if ($configs['view_directory']) {
            $GLOBALS['view_directory'] = $configs['view_directory'];
        }

        if ($configs['composer_directory']) {
            $GLOBALS['composer_directory'] = $configs['composer_directory'];
        }

        // Create an array of strings to be removed
        $protocols = ['http://', 'https://'];
        $host = trim($_ENV['app.baseURL'] ?? '') ?: 'http://localhost:8085';

        // Replace all occurrences with an empty string
        $host = str_replace($protocols, '', $host);

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['HTTP_HOST']   =  $host;
        
        require(BASEPATH.'core/Controller.php');

        function get_instance() {
            return CI_Controller::get_instance();
        }
        
        if (file_exists(APPPATH.'core/'.$GLOBALS['CFG']->config['subclass_prefix'].'Controller.php')) {
            require APPPATH.'core/'.$GLOBALS['CFG']->config['subclass_prefix'].'Controller.php';
            $class = $GLOBALS['CFG']->config['subclass_prefix'].'Controller';
        } else {
            $class = 'CI_Controller';
        }
        
        self::$instance = new $class();
        
        return self::$instance;
    }
    
    /**
     * Getter for the CodeIgniter instance object
     * 
     * @static
     * @access public
     * @throws WebbyInstanceException if the instance is not yet initialized
     * @return object Webby instance
     */
    public static function instance()
    {
        if (!self::$instance) {
            throw new WebbyInstanceException('CodeIgniter instance is not initialized yet');
        }
        return self::$instance;
    }
    
}

/**
 * Custom exception for WebbyInstanceException
 */
class WebbyInstanceException extends \Exception {}

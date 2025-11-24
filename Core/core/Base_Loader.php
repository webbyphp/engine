<?php
defined('COREPATH') or exit('No direct script access allowed');

use Base\HMVC\Modules;

class Base_Loader extends \CI_Loader
{

	/**
	 * Summary of _module
	 * @var array
	 */
	protected $_module;

	/**
	 * Summary of _ci_plugins
	 * @var array
	 */
	public $_ci_plugins = [];

	/**
	 * Summary of _ci_cached_vars
	 * @var array
	 */
	public $_ci_cached_vars = [];

	/**
	 * List of loaded services
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_services = [];

	/**
	 * List of paths to load services from
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_service_paths = [];

	/**
	 * List of loaded actions
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_actions = [];

	/**
	 * List of paths to load actions from
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_action_paths = [];

	/**
	 * List of loaded rules
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_rules = [];

	/**
	 * List of rules arrays
	 *
	 * @var array
	 * @access protected
	 */
	public $rules = [];

	/**
	 * List of paths to load rules from
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_rules_paths = [];

	/**
	 * List of loaded forms
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_forms = [];

	/**
	 * List of paths to load forms from
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_form_paths = [];

	/**
	 * Holds current module controller
	 * 
	 * @var 
	 */
	protected $controller;

	/**
	 * Constructor
	 *
	 * Set the path to the Service/Action files
	 */
	public function __construct()
	{

		parent::__construct();

		load_class('Service', 'core'); // Load service core class
		load_class('Action', 'core'); // Load action core class

		// $this->_ci_service_paths = [COREPATH];
	}

	/** Initialize the loader variables **/
	public function initialize($controller = null)
	{
		/* set the module name */
		$this->_module = ci()->router->fetch_module();

		if ($controller instanceof Base_Controller) {
			/* reference to the module controller */
			$this->controller = $controller;

			/* references to ci loader variables */
			foreach (get_class_vars('CI_Loader') as $var => $val) {
				if ($var != '_ci_ob_level') {
					$this->$var = &ci()->load->$var;
				}
			}
		} else {
			parent::initialize();

			/* autoload module items */
			$this->_autoloader([]);
		}

		/* add this module path to the loader variables */
		$this->_add_module_paths($this->_module);
	}

	/** Add a module path loader variables **/
	public function _add_module_paths($module = '')
	{
		if (empty($module)) return;

		foreach (Modules::$locations as $location => $offset) {
			/* only add a module path if it exists */
			if (is_dir($module_path = $location . $module . '/') && ! in_array($module_path, $this->_ci_model_paths)) {
				array_unshift($this->_ci_model_paths, $module_path);
			}
		}
	}

	/** Load a module config file **/
	public function config($file, $use_sections = false, $fail_gracefully = false)
	{
		return ci()->config->load($file, $use_sections, $fail_gracefully, $this->_module);
	}

	/** Load the database drivers **/
	public function database($params = '', $return = false, $query_builder = null)
	{
		if (
			$return === false && $query_builder === null &&
			isset(ci()->db) && is_object(ci()->db) && ! empty(ci()->db->conn_id)
		) {
			return false;
		}

		require_once CIPATH . 'database/DB' . PHPEXT;

		if ($return === true) return DB($params);

		ci()->db = DB($params);

		return $this;
	}

	/** 
	 * Load a module helper 
	 * @return mixed
	 */
	public function helper($helper = [])
	{
		if (is_array($helper)) return $this->helpers($helper);

		if (isset($this->_ci_helpers[$helper]))	return;

		list($path, $_helper) = Modules::find(ucfirst($helper) . '_helper', $this->_module, 'Helpers/');

		if ($path === false) return parent::helper($helper);

		Modules::load_file($_helper, $path);
		$this->_ci_helpers[$_helper] = true;
		return $this;
	}

	/** Load an array of helpers **/
	public function helpers($helpers = [])
	{
		foreach ($helpers as $_helper) $this->helper($_helper);
		return $this;
	}

	/** Load a module language file **/
	public function language($langfile, $idiom = '', $return = false, $add_suffix = true, $alt_path = '')
	{
		ci()->lang->load($langfile, $idiom, $return, $add_suffix, $alt_path, $this->_module);
		return $this;
	}

	public function languages($languages)
	{
		foreach ($languages as $_language) $this->language($_language);
		return $this;
	}

	/** Load a module library **/
	public function library($library, $params = null, $object_name = null)
	{
		if (is_array($library)) return $this->libraries($library);

		$class = basename($library);

		if (isset($this->_ci_classes[$class]) && $_alias = $this->_ci_classes[$class])
			return $this;

		// Quick fix for PHP8.1
		$object_name = !is_null($object_name) ? $object_name : '';

		($_alias = strtolower($object_name)) or $_alias = $class;

		list($path, $_library) = Modules::find($library, $this->_module, 'Libraries/');

		/* load library config file as params */
		if ($params == null) {
			list($path2, $file) = Modules::find($_alias, $this->_module, 'Config/');
			($path2) && $params = Modules::load_file($file, $path2, 'config');
		}

		if ($path === false) {
			$this->_ci_load_library($library, $params, $object_name);
		} else {
			Modules::load_file($_library, $path);

			$library = $_library;
			ci()->$_alias = new $library($params);

			$this->_ci_classes[$class] = $_alias;
		}
		return $this;
	}

	/** Load an array of libraries **/
	public function libraries($libraries)
	{
		foreach ($libraries as $library => $alias) {
			(is_int($library)) ? $this->library($alias) : $this->library($library, null, $alias);
		}
		return $this;
	}

	/**
	 * Overriding model function
	 *
	 * @param string|array $model
	 * @param string $object_name
	 * @param bool $connect
	 * @return object
	 */
	public function model($model, $object_name = null, $connect = false)
	{
		if (is_array(value: $model)) {
			return $this->models($model);
		}

		($_alias = $object_name) or $_alias = basename($model);

		if (in_array($_alias, $this->_ci_models, true)) {
			return $this;
		}

		// Check module
		// This line allows CamelCasing names for models in modules
		[$path, $_model] = Modules::find($model, $this->_module, 'Models/');

		/*
		 * Compare the two and know the differences. If you want to revert back use the one below
		*/
		// list($path, $_model) = Modules::find(strtolower($model), $this->_module, 'models/');

		if ($path == false) {
			// check corepath & packages and default locations 
			parent::model($model, $object_name, $connect);
		} else {
			class_exists('CI_Model', false) or load_class('Model', 'core');

			if ($connect !== false && ! class_exists('CI_DB', false)) {
				if ($connect === true) $connect = '';
				$this->database($connect, false, true);
			}

			Modules::load_file($_model, $path);

			$model = ucfirst($_model);
			ci()->$_alias = new $model();

			$this->_ci_models[] = $_alias;
		}

		return $this;
	}

	/** Load an array of models **/
	public function models($models)
	{
		foreach ($models as $model => $alias) {
			(is_int($model)) ? $this->model($alias) : $this->model($model, $alias);
		}
		return $this;
	}

	/** Load a module controller **/
	public function module($module, $params = null)
	{
		if (is_array($module)) return $this->modules($module);

		$_alias = strtolower(basename($module));
		ci()->$_alias = Modules::load([$module => $params]);
		return $this;
	}

	/** Load an array of controllers **/
	public function modules($modules)
	{
		foreach ($modules as $_module) $this->module($_module);
		return $this;
	}

	/** Load a module plugin **/
	public function plugin($plugin)
	{
		if (is_array($plugin)) return $this->plugins($plugin);

		if (isset($this->_ci_plugins[$plugin]))
			return $this;

		list($path, $_plugin) = Modules::find($plugin . '_pi', $this->_module, 'Plugins/');

		if ($path === false && ! is_file($_plugin = APPPATH . 'plugins/' . $_plugin . PHPEXT)) {
			show_error("Unable to locate the plugin file: {$_plugin}");
		}

		Modules::load_file($_plugin, $path);
		$this->_ci_plugins[$plugin] = true;
		return $this;
	}

	/** Load an array of plugins **/
	public function plugins($plugins)
	{
		foreach ($plugins as $_plugin) $this->plugin($_plugin);
		return $this;
	}

	/** Load a module view **/
	public function view($view, $vars = [], $return = false)
	{
		list($path, $_view) = Modules::find($view, $this->_module, 'Views/');

		if ($path != false) {
			$this->_ci_view_paths = [$path => true] + $this->_ci_view_paths;
			$view = $_view;
		}

		return $this->_ci_load(['_ci_view' => $view, '_ci_vars' => $this->_ci_prepare_view_vars($vars), '_ci_return' => $return]);

		// return (method_exists($this, '_ci_object_to_array') ? $this->_ci_load(['_ci_view' => $view, '_ci_vars' => $this->_ci_object_to_array($vars), '_ci_return' => $return]) : $this->_ci_load(['_ci_view' => $view, '_ci_vars' => $this->_ci_prepare_view_vars($vars), '_ci_return' => $return]));
	}

	/**
	 * Load a Package Path from Third Party directory
	 * 
	 * Make sure Third Party directory exists
	 *
	 * @param string $path Path to add
	 * @param bool $view_cascade (default: true)
	 * @return mixed
	 */
	public function thirdparty($path, $file = '', $show_content = false, $view_cascade = true)
	{
		$path = APPROOT . 'ThirdParty' . DIRECTORY_SEPARATOR . $path;

		if (!empty($file)) {
			return $this->load->file($path . DIRECTORY_SEPARATOR . $file, $show_content);
		}

		$this->add_package_path($path, $view_cascade);
	}

	/**
	 * Removes a Third Party Package from 
	 * packages third party list
	 *
	 * @param string $path
	 * @return mixed
	 */
	public function removeThirdparty($path = '')
	{
		if (empty($path)) {
			return $this;
		}

		$path = APPROOT . 'ThirdParty' . DIRECTORY_SEPARATOR . $path;

		return $this->removePackage($path);
	}

	/**
	 * Add a Package Path
	 *
	 * Prepends a parent path to the library, 
	 * model, helper and config path arrays.
	 * 
	 * @param string $path Path to add
	 * @param bool $view_cascade (default: true)
	 * @return void
	 */
	public function package($path, $view_cascade = true)
	{
		$this->add_package_path($path, $view_cascade);
	}

	/**
	 * Get Package Paths
	 *
	 * Return a list of all package paths.
	 *
	 * @param	bool	$include_base	Whether to include CIPATH (default: false)
	 * @return	array
	 */
	public function packages($include_base = false)
	{
		return $this->get_package_paths($include_base);
	}

	/**
	 * Remove Package Path
	 *
	 * Remove a path from the library, model, helper and/or config
	 * path arrays if it exists. If no path is provided, the most recently
	 * added path will be removed.
	 *
	 * @param	string	$path	Path to remove
	 * @return	object
	 */
	public function removePackage($path = '')
	{
		return $this->remove_package_path($path);
	}

	/**
	 * Override CI_Loader::_ci_load_stock_library to add support for Base_* libraries
	 * This method extends the parent functionality to also search for Base_* extensions
	 * that extend core CI_* libraries.
	 *
	 * @param	string	$library_name	Library name to load
	 * @param	string	$file_path		Path to the library filename, relative to libraries/
	 * @param	mixed	$params			Optional parameters to pass to the class constructor
	 * @param	string	$object_name	Optional object name to assign to
	 * @return	void
	 */
	protected function _ci_load_stock_library($library_name, $file_path, $params, $object_name)
	{
		$prefix = 'CI_';

		if (class_exists($prefix . $library_name, false)) {
			// Check for Base_* extension first (our custom priority)
			if (class_exists('Base_' . $library_name, false)) {
				$prefix = 'Base_';
			}
			// Then check for subclass_prefix extension (MY_*)
			elseif (class_exists(config_item('subclass_prefix') . $library_name, false)) {
				$prefix = config_item('subclass_prefix');
			}

			$property = $object_name;
			if (empty($property)) {
				$property = strtolower($library_name);
				isset($this->_ci_varmap[$property]) && $property = $this->_ci_varmap[$property];
			}

			$CI = get_instance();
			if (! isset($CI->$property)) {
				return $this->_ci_init_library($library_name, $prefix, $params, $object_name);
			}

			log_message('debug', $library_name . ' class already loaded. Second attempt ignored.');
			return;
		}

		$paths = $this->_ci_library_paths;
		array_pop($paths); // BASEPATH
		array_pop($paths); // COREPATH (needs to be the first path checked)
		array_unshift($paths, COREPATH);

		// First, try to find Base_* extensions
		$base_class = 'Base_' . $library_name;
		foreach ($paths as $path) {
			if (file_exists($path = $path . 'libraries/' . $file_path . $base_class . '.php')) {
				// Override
				include_once($path);
				if (class_exists($base_class, false)) {
					// Load the stock library first
					include_once(BASEPATH . 'libraries/' . $file_path . $library_name . '.php');
					return $this->_ci_init_library($library_name, 'Base_', $params, $object_name);
				}

				log_message('debug', $path . ' exists, but does not declare ' . $base_class);
			}
		}

		// If no Base_* extension found, fall back to the original logic
		foreach ($paths as $path) {
			if (file_exists($path = $path . 'libraries/' . $file_path . $library_name . '.php')) {
				// Override
				include_once($path);
				if (class_exists($prefix . $library_name, false)) {
					return $this->_ci_init_library($library_name, $prefix, $params, $object_name);
				}

				log_message('debug', $path . ' exists, but does not declare ' . $prefix . $library_name);
			}
		}

		include_once(BASEPATH . 'libraries/' . $file_path . $library_name . '.php');

		// Check for Base_* extensions after loading stock library
		$base_class = 'Base_' . $library_name;
		foreach ($paths as $path) {
			if (file_exists($path = $path . 'libraries/' . $file_path . $base_class . '.php')) {
				include_once($path);
				if (class_exists($base_class, false)) {
					$prefix = 'Base_';
					break;
				}

				log_message('debug', $path . ' exists, but does not declare ' . $base_class);
			}
		}

		// Check for subclass_prefix extensions (MY_*)
		$subclass = config_item('subclass_prefix') . $library_name;
		foreach ($paths as $path) {
			if (file_exists($path = $path . 'libraries/' . $file_path . $subclass . '.php')) {
				include_once($path);
				if (class_exists($subclass, false)) {
					$prefix = config_item('subclass_prefix');
					break;
				}

				log_message('debug', $path . ' exists, but does not declare ' . $subclass);
			}
		}

		return $this->_ci_init_library($library_name, $prefix, $params, $object_name);
	}

	protected function &_ci_get_component($component)
	{
		return ci()->$component;
	}

	public function __get($class)
	{
		return (isset($this->controller)) ? $this->controller->$class : ci()->$class;
	}

	public function _ci_load($_ci_data)
	{
		extract($_ci_data);

		$_is_view = false;
		$ext = (!empty(config_item('view')['view_engine']))
			? ltrim(config_item('view_extension'), '.')
			: ltrim(PHPEXT, '.');

		if (isset($_ci_view)) {
			$_ci_path = '';
			$_is_view = true;

			/* add file extension if not provided */
			$_ci_file = (pathinfo($_ci_view, PATHINFO_EXTENSION)) ? $_ci_view : $_ci_view . '.' . $ext;

			foreach ($this->_ci_view_paths as $path => $cascade) {
				if (file_exists($view = $path . $_ci_file)) {
					$_ci_path = $view;
					break;
				}
				if (!$cascade) break;
			}
		} elseif (isset($_ci_path)) {

			$_ci_file = basename($_ci_path);
			if (!file_exists($_ci_path)) $_ci_path = '';
		}

		if (empty($_ci_path)) {

			$ext = pathinfo($_ci_file, PATHINFO_EXTENSION);

			$msg = 'Unable to load the requested file: ' . $_ci_file . ' make sure it really exists.';

			if ($_is_view) {
				$_ci_file = pathinfo($_ci_file, PATHINFO_FILENAME);
				$msg = $_ci_file . ' view was not found, Are you sure the view exists and is a `.' . $ext . '` file? ';
			}

			show_error($msg);
		}

		if (isset($_ci_vars))
			$this->_ci_cached_vars = array_merge($this->_ci_cached_vars, (array) $_ci_vars);

		extract($this->_ci_cached_vars);

		ob_start();

		if ((bool) @ini_get('short_open_tag') === false && ci()->config->item('rewrite_short_tags') == true) {
			echo eval('?>' . preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', file_get_contents($_ci_path))));
		} else {
			include($_ci_path);
		}

		log_message('debug', 'File loaded: ' . $_ci_path);

		if ($_ci_return == true) return ob_get_clean();

		if (ob_get_level() > $this->_ci_ob_level + 1) {
			ob_end_flush();
		} else {
			ci()->output->append_output(ob_get_clean());
		}
	}

	/** Autoload module items **/
	public function _autoloader($autoload)
	{
		$path = false;

		if ($this->_module) {
			list($path, $file) = Modules::find('Constants', $this->_module, 'Config/');

			/* module constants file */
			if ($path != false) {
				include_once $path . $file . PHPEXT;
			}

			list($path, $file) = Modules::find('Autoload', $this->_module, 'Config/');

			/* module autoload file */
			if ($path != false) {
				$autoload = array_merge(Modules::load_file($file, $path, 'autoload'), $autoload);
			}
		}

		/* nothing to do */
		if (count($autoload) == 0) return;

		/* autoload package paths */
		if (isset($autoload['packages'])) {
			foreach ($autoload['packages'] as $package_path) {
				$this->add_package_path($package_path);
			}
		}

		/* autoload config */
		if (isset($autoload['config'])) {
			foreach ($autoload['config'] as $config) {
				$this->config($config);
			}
		}

		/* autoload helpers, plugins, languages */
		foreach (['helper', 'plugin', 'language'] as $type) {
			if (isset($autoload[$type])) {
				foreach ($autoload[$type] as $item) {
					$this->$type($item);
				}
			}
		}

		// Autoload drivers
		if (isset($autoload['drivers'])) {
			foreach ($autoload['drivers'] as $item => $alias) {
				(is_int($item)) ? $this->driver($alias) : $this->driver($item, $alias);
			}
		}

		/* autoload database & libraries */
		if (isset($autoload['libraries'])) {
			if (in_array('database', $autoload['libraries'])) {
				/* autoload database */
				if (! $db = ci()->config->item('database')) {
					$this->database();
					$autoload['libraries'] = array_diff($autoload['libraries'], ['database']);
				}
			}

			/* autoload libraries */
			foreach ($autoload['libraries'] as $library => $alias) {
				(is_int($library)) ? $this->library($alias) : $this->library($library, null, $alias);
			}
		}

		/* autoload models */
		if (isset($autoload['model'])) {
			foreach ($autoload['model'] as $model => $alias) {
				(is_int($model)) ? $this->model($alias) : $this->model($model, $alias);
			}
		}

		/* autoload module controllers */
		if (isset($autoload['modules'])) {
			foreach ($autoload['modules'] as $controller) {
				($controller != $this->_module) && $this->module($controller);
			}
		}
	}

	/**
	 * Action Loader
	 *
	 * This function lets users load and instantiate actions.
	 * It is designed to be called in a module's controllers.
	 *
	 *
	 * @param string $action the name of the class
	 * @return mixed
	 */
	public function action($action)
	{

		if (is_array($action)) return $this->actions($action);

		$_action = basename($action);

		if (isset($this->_ci_actions[$_action]) && $_alias = $this->_ci_actions[$_action]) {
			return $this;
		}

		if ($action == '' or isset($this->_ci_actions[$_action])) {
			return false;
		}

		$subdir = '';

		// Is the action in a sub-folder? If so, parse out the filename and path.
		if (($last_slash = strrpos($action, '/')) !== false) {
			// The path is in front of the last slash
			$subdir = substr($action, 0, $last_slash + 1);

			// And the action name behind it
			$action = substr($action, $last_slash + 1);
		}

		// Quick fix for PHP8.1
		$_alias = $action;

		$action_path = $subdir . $action . PHPEXT;

		list($path, $_action) = Modules::find($action_path, $this->_module, 'Actions/');

		if (!file_exists($path . $_action)) {
			show_error($_action . ' was not found, Are you sure the action file exists?');
		}

		Modules::load_file($_action, $path);

		$action = ucfirst($action);
		ci()->$_alias = new $action;

		$this->_ci_actions[$action] = $_alias;

		return $this;
	}

	/**
	 * Load an array of actions
	 *
	 * @param array $actions
	 * @return mixed
	 */
	public function actions(array $actions)
	{
		foreach ($actions as $action => $alias) {
			(is_int($action)) ? $this->action($alias) : $this->action($action);
		}
		return $this;
	}

	/**
	 * Service Loader
	 *
	 * This function lets users load and instantiate services.
	 * It is designed to be called a module's controllers.
	 *
	 *
	 * @param string $service the name of the class
	 * @param mixed $params the optional parameters
	 * @param string $object_name an optional object name
	 * @return mixed
	 */
	public function service($service, $params = null, $object_name = null)
	{

		if (is_array($service)) return $this->services($service);

		$_service = basename($service);

		if (isset($this->_ci_services[$_service]) && $_alias = $this->_ci_services[$_service]) {
			return $this;
		}

		if ($service == '' or isset($this->_ci_services[$_service])) {
			return false;
		}

		if (!is_null($params) && !is_array($params)) {
			$params = null;
		}

		$subdir = '';

		// Is the service in a sub-folder? If so, parse out the filename and path.
		if (($last_slash = strrpos($service, '/')) !== false) {
			// The path is in front of the last slash
			$subdir = substr($service, 0, $last_slash + 1);

			// And the service name behind it
			$service = substr($service, $last_slash + 1);
		}

		// Quick fix for PHP8.1
		$object_name = !is_null($object_name) ? $object_name : '';

		($_alias = strtolower($object_name)) or $_alias = $service;

		$service_path = $subdir . $service . PHPEXT;

		list($path, $_service) = Modules::find($service_path, $this->_module, 'Services/');

		// load service config file as params 
		if ($params == null) {
			list($path2, $file) = Modules::find($_alias, $this->_module, 'Config/');
			($path2) && $params = Modules::load_file($file, $path2, 'config');
		}

		if (!file_exists($path . $_service)) {
			show_error($_service . ' was not found, Are you sure the service file exists?');
		}

		Modules::load_file($_service, $path);

		$service = ucfirst($service);
		ci()->$_alias = new $service($params);

		$this->_ci_services[$service] = $_alias;
		return $this;
	}

	/**
	 * Load an array of services
	 *
	 * @param array $services
	 * @return mixed
	 */
	public function services(array $services)
	{
		foreach ($services as $service => $alias) {
			(is_int($service)) ? $this->service($alias) : $this->service($service, null, $alias);
		}
		return $this;
	}

	/**
	 * Rule Loader
	 *
	 * This function lets users load rules.
	 * That can used when validating forms 
	 * It is designed to be called from a user's app
	 * It can be controllers or models
	 *
	 * @param string $rule
	 * @return mixed
	 */
	public function rule($rule = [], $return_array = false)
	{
		if (is_array($rule)) return $this->rules($rule);

		if (isset($this->_ci_rules[$rule]))	return;

		list($path, $_rule) = Modules::find($rule, $this->_module, 'Rules/');

		if ($path === false) /*return parent::helper($rule);*/

			show_error($_rule . 'was not found, Are you sure the rule file exists');

		Modules::load_file($_rule, $path);

		$this->_ci_rules[$_rule] = true;

		if ($return_array === true) {
			include($path . $_rule . PHPEXT);
			$this->rules = $rules;
		}

		return $this;
	}

	/**
	 * Load an array of rules
	 *
	 * @param array $rules
	 * @return mixed
	 */
	public function rules($rules = [])
	{
		foreach ($rules as $_rule) $this->rule($_rule);
		return $this;
	}

	/**
	 * Form Loader
	 *
	 * This function lets users load forms
	 * That can used when validating forms 
	 * 
	 * It works the same as the rule method
	 * It is designed to be called from a user's app
	 * It can be controllers or models
	 *
	 * @param string $form
	 * @return mixed
	 */
	public function form($form = [])
	{
		if (is_array($form)) return $this->forms($form);

		if (isset($this->_ci_forms[$form]))	return;

		list($path, $_form) = Modules::find($form, $this->_module, 'Forms/');

		if ($path === false)

			show_error($_form . 'was not found, Are you sure the form file exists');

		Modules::load_file($_form, $path);

		$this->_ci_forms[$_form] = true;

		return $this;
	}

	/**
	 * Load an array of forms
	 *
	 * @param array $forms
	 * @return mixed
	 */
	public function forms($forms = [])
	{
		foreach ($forms as $_form) $this->form($_form);
		return $this;
	}
}
/* end of file Base_Loader.php */

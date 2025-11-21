<?php

/**
 * A Template engine for WebbyPHP
 * 
 * It is based on Laravel's Blade templating engine 
 * Initially developed by Gustavo Martins and named Slice as
 * a CodeIgniter Library.
 * 
 * @author		Gustavo Martins <gustavo_martins92@hotmail.com>
 * @link		https://github.com/GustMartins/Slice-Library
 * 
 * Expanded to work efficiently with WebbyPHP
 * 
 * @author  Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 * @license MIT
 * @version 1.0.0
 * 
 */

namespace Base\View;

use Exception;
use ParseError;
use Base\HMVC\Modules;

class Plates
{

	/**
	 *  The file extension for the plates template
	 *
	 *  @var   string
	 */
	public $plateExtension	= '.php';

	/**
	 *  The amount of time to keep the file in cache
	 *
	 *  @var   int
	 */
	public $cacheTime		= 3600;

	/**
	 *  Autoload CodeIgniter Libraries and Helpers
	 *
	 *  @var   boolean
	 */
	public $enableAutoload	= false;

	/**
	 *  Default language
	 *
	 *  @var   string
	 */
	public $locale			= 'english';

	// --------------------------------------------------------------------------

	/**
	 *  Reference to CodeIgniter instance
	 *
	 *  @var   object
	 */
	protected $ci;

	/**
	 *  Global array of data for Plates Template
	 *
	 *  @var   array
	 */
	protected $plateData	= [];

	/**
	 *  The content of each section
	 *
	 *  @var   array
	 */
	protected $sections		= [];

	/**
	 *  The stack of current sections being buffered
	 *
	 *  @var   array
	 */
	protected $buffer		= [];

	/**
	 *  Custom compile functions by the user
	 *
	 *  @var   array
	 */
	protected $directives 	= [];

	/**
	 *  Libraries to autoload
	 *
	 *  @var   array
	 */
	protected $libraries 	= [];

	/**
	 *  Helpers to autoload
	 *
	 *  @var   array
	 */
	protected $helpers 		= [];

	/**
	 *  Language strings to use with translation
	 *
	 *  @var   array
	 */
	protected $language		= [];

	/**
	 *  List of languages loaded
	 *
	 *  @var   array
	 */
	protected $i18nLoaded 	= [];

	/**
	 * if true then, if the operation fails, 
	 * and it is critic, then it throws an error
	 *
	 * @var bool
	 */
	public $throwOnError = false;
	
	// --------------------------------------------------------------------------
	/**
	 *  All of the compiler methods used by Plates to simulate
	 *  Laravel Plates Template
	 */
	private array $compilers 		= [
		'directive',
		'comment',
		'multiline_html_comment',
		'html_comment',
		'ternary',
		'preserved',
		'unescaped_echo',
		'echo',
		'variable',
		'forelse',
		'empty',
		'endforelse',
		'opening_statements',
		'else',
		'continueIf',
		'continue',
		'breakIf',
		'break',
		'closing_statements',
		'each',
		'unless',
		'endunless',
		'includeIf',
		'include',
		'head',
		'partial',
		'section',
		'component',
		'extends',
		'yield',
		'show',
		'start_section',
		'close_section',
		'php',
		'endphp',
		'json',
		'endhtml',
		'doctype',
		'script',
		'endscript',
		'javascript',
		'lang',
		'choice',
		'csrf',
		'honeypot',
	];

	private string $cacheExtension = '.plates';

	/**
	 * Current View Path
	 *
	 * @var string
	 */
	public $viewPath;

	// --------------------------------------------------------------------------

	/**
	 *  Plates Class Constructor
	 *
	 *  @param   array   $params = []
	 *  @return	 void
	 */
	public function __construct(array $params = [])
	{
		// Set the super object to a local variable for use later
		$this->ci = ci();
		$this->ci->benchmark->mark('plate_execution_time_start');	//	Start the timer

		$this->ci->load->driver('cache');	//	Load ci cache driver

		if (config_item('enable_helper')) {
			$this->ci->load->helper('view');	//	Load View Helper
		}

		$this->initialize($params);

		//	Autoload Libraries and Helpers
		if ($this->enableAutoload) {
			//	Autoload Libraries
			if (!empty($this->libraries)) {
				$this->ci->load->library($this->libraries);
			}

			//	Autoload Helpers
			if (!empty($this->helpers)) {
				$this->ci->load->helper($this->helpers);
			}
		}

		log_message('info', 'Plates Template Class Initialized');
	}

	// --------------------------------------------------------------------------

	/**
	 *  __set magic method
	 *
	 *  Handles writing to the data property
	 *
	 *  @param   string   $name
	 *  @param   mixed    $value
	 */
	public function __set($name, mixed $value)
	{
		$this->plateData[$name] = $value;
	}

	// --------------------------------------------------------------------------

	/**
	 *  __unset magic method
	 *
	 *  Handles unseting to the data property
	 *
	 *  @param   string   $name
	 */
	public function __unset($name)
	{
		unset($this->plateData[$name]);
	}

	// --------------------------------------------------------------------------

	/**
	 *  __get magic method
	 *
	 *  Handles reading of the data property
	 *
	 *  @param    string   $name
	 *  @return   mixed
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->plateData)) {
			return $this->plateData[$name];
		}

		return $this->ci->$name;
	}

	// --------------------------------------------------------------------------

	/**
	 * Initializes preferences
	 */
	public function initialize(array $params = []): static
	{
		$this->clear();

		foreach ($params as $key => $val) {
			if (isset($this->$key)) {
				$this->$key = $val;
			}
		}

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Initializes some important variables
	 */
	public function clear(): static
	{
		$this->plateExtension   = config_item('view_extension') ?? PHPEXT;
		$this->cacheTime		= config_item('cache_time');
		$this->enableAutoload	= config_item('enable_autoload');
		$this->locale			= config_item('language');
		$this->libraries	    = config_item('libraries');
		$this->helpers		    = config_item('helpers');
		$this->plateData		= [];

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Sets one single data to Plates Template
	 */
	public function with(string $name, mixed $value = ''): static
	{
		$this->plateData[$name] = $value;
		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Sets one or more data to Plates Template
	 */
	public function set(mixed $data, mixed $value = ''): static
	{
		if (is_array($data)) {
			$this->plateData = array_merge($this->plateData, $data);
		} else {
			$this->plateData[$data] = $value;
		}

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Appends or concatenates a value to a data in Plates Template
	 *
	 *  If data type is array it will append
	 *  If data type is string it will concatenate
	 *
	 *  @param    string   $name
	 *  @param    mixed    $value
	 */
	public function append(string $name, mixed $value): static
	{
		if (is_array($this->plateData[$name])) {
			$this->plateData[$name][] = $value;
		} else {
			$this->plateData[$name] .= $value;
		}

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Outputs template content
	 *
	 *  @param    array     $data
	 * 
	 *  @return   string
	 */
	public function view(string $template, $data = null, bool $return = false)
	{
		if (isset($data)) {
			$this->set($data);
		}

		//	Compile and execute the template
		$content = $this->run($this->compile($template), $this->plateData);

		if (config_item('compress_content')) {
			$content = $this->minifyHtml($content);
		}

		if (!$return) {
			$this->ci->output->append_output($content);
		}

		return $content;
	}

	/**
	 * Minify compiled html
	 *
	 * @return string
	 */
	protected function minifyHtml(string $content, bool $removeComments = true)
	{
		$commentCount = null;
		$commentMatches = [];
		$key = md5(random_int(0, mt_getrandmax())) . '-';

		// processing pre tag (saving its contents)
		$preCount = preg_match_all('|(<pre[^>]*>.*?</pre>)|is', $content, $preMatches);
		for ($i = 0; $i < $preCount; $i++) $content = str_replace($preMatches[0][$i], '<PRE|' . $i . '|' . $key . '>', $content);

		// processing code tag
		$codeCount = preg_match_all('|(<code[^>]*>.*?</code>)|is', $content, $codeMatches);
		for ($i = 0; $i < $codeCount; $i++) $content = str_replace($codeMatches[0][$i], '<CODE|' . $i . '|' . $key . '>', $content);

		// processing script tag
		$scriptCount = preg_match_all('|(<script[^>]*>.*?</script>)|is', $content, $scriptMatches);
		for ($i = 0; $i < $scriptCount; $i++) $content = str_replace($scriptMatches[0][$i], '<SCRIPT|' . $i . '|' . $key . '>', $content);

		// processing textarea tag
		$textareaCount = preg_match_all('|(<textarea[^>]*>.*?</textarea>)|is', $content, $textareaMatches);
		for ($i = 0; $i < $textareaCount; $i++) $content = str_replace($textareaMatches[0][$i], '<TEXTAREA|' . $i . '|' . $key . '>', $content);

		// processing comments if they not to be removed
		if (!$removeComments) {
			$commentCount = preg_match_all('|(<!--.*?-->)|s', $content, $commentMatches);
			for ($i = 0; $i < $commentCount; $i++) $content = str_replace($commentMatches[0][$i], '<COMMENT|' . $i . '|' . $key . '>', $content);
		}

		// removing comments if need
		if ($removeComments) {
			$content = preg_replace('|(<!--.*?-->)|s', '', $content);
		}

		// replacing html entities
		$content = preg_replace('|&nbsp;|', ' ', $content); // replacing with non-breaking space (symbol 160 in Unicode)
		$content = preg_replace('|&mdash;|', '—', $content);
		$content = preg_replace('|&ndash;|', '–', $content);
		$content = preg_replace('|&laquo;|', '«', $content);
		$content = preg_replace('|&raquo;|', '»', $content);
		$content = preg_replace('|&bdquo;|', '„', $content);
		$content = preg_replace('|&ldquo;|', '“', $content);

		$content = preg_replace('|(</?\w+[^>]+?)\s+(/?>)|s', '$1$2', $content); // removing all contunous spaces

		while (preg_match('|<(/?\w+[^>]+/?)>\s+<(/?\w+?)|s', $content)) {
			$content = preg_replace('|<(/?\w+[^>]+/?)>\s+<(/?\w+?)|s', '<$1><$2', $content); // removing all spaces and newlines between tags
		}

		$content = preg_replace('|\s\s+|s', ' ', $content); // removing all contunous spaces

		// restoring processed comments
		if (!$removeComments) {
			for ($i = 0; $i < $commentCount; $i++) $content = str_replace('<COMMENT|' . $i . '|' . $key . '>', $commentMatches[0][$i], $content);
		}
		// restoring textarea tag
		for ($i = 0; $i < $textareaCount; $i++) $content = str_replace('<TEXTAREA|' . $i . '|' . $key . '>', $textareaMatches[0][$i], $content);
		// restoring script tag
		for ($i = 0; $i < $scriptCount; $i++) $content = str_replace('<SCRIPT|' . $i . '|' . $key . '>', $scriptMatches[0][$i], $content);
		// restoring code tag
		for ($i = 0; $i < $codeCount; $i++) $content = str_replace('<CODE|' . $i . '|' . $key . '>', $codeMatches[0][$i], $content);
		// restoring pre tag
		for ($i = 0; $i < $preCount; $i++) $content = str_replace('<PRE|' . $i . '|' . $key . '>', $preMatches[0][$i], $content);

		return $content;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Verifies if a file exists!
	 *
	 *  This function verifies if a file exists even if you are using
	 *  Modular Extensions
	 *
	 *  @param    string    $filename
	 *  @param    boolean   $showError
	 *  @return   mixed
	 */
	public function exists(string $filename, bool $showError = false)
	{
		$viewName = preg_replace('/([a-z]\w+)\./', '$1/', $filename);

		//	The default path to the file
		$defaultPath = VIEWPATH . $viewName . $this->plateExtension;

		//	If you are using Modular Extensions it will be detected
		if (method_exists($this->ci->router, 'fetch_module')) {
			$module = $this->ci->router->fetch_module();
			[$path, $view] = Modules::find($viewName . $this->plateExtension, $module, 'Views/');

			if ($path) {
				$defaultPath = $path . $view;
			}
		}

		//	Verify if the page really exists
		if (is_file($defaultPath)) {
			if ($showError) {
				return $defaultPath;
			}

			return true;
		}

		if ($showError) {
			show_error($viewName . ' view was not found, Are you sure the view exists and is a `' . $this->plateExtension . '` file? ');
		} else {
			return false;
		}
	}

	// --------------------------------------------------------------------------

	/**
	 *  Alters the language to use with translation strings
	 */
	public function locale(string $locale): static
	{
		$this->locale = (string) $locale;
		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Sets custom compilation function
	 */
	public function directive(string $compilator): static
	{
		$this->directives[] = $compilator;
		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Compiles a template and saves it in the cache
	 */
	protected function compile(string $template): string
	{
		$viewPath	= $this->exists($template, true);
		$cacheName	= md5((string) $viewPath) . $this->cacheExtension;
		$platesPath = $this->ci->config->item('plates_cache_path') . DIRECTORY_SEPARATOR;

		$this->viewPath = $viewPath;
		$this->cacheName = $cacheName;
		$this->platesPath = $platesPath;

		// Save cached files to cache/web/plates folder
		$this->ci->config->set_item('cache_path', $platesPath);

		//	Verifies if a cached version of the file exists
		if ($cachedVersion = $this->ci->cache->file->get($cacheName)) {
			if (ENVIRONMENT == 'production') {
				return $cachedVersion;
			}

			$cachedMeta = $this->ci->cache->file->get_metadata($cacheName);

			if ($cachedMeta['mtime'] > filemtime($viewPath)) {
				return $cachedVersion;
			}
		}

		$content = file_get_contents($viewPath);

		//	Compile the content
		foreach ($this->compilers as $compiler) {
			$method = sprintf('compile_%s', $compiler);
			$content = $this->$method($content);
		}

		//	Store in the cache
		$this->ci->cache->file->save($cacheName, $content, $this->cacheTime);

		return $content;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Runs the template with its data
	 *
	 *  @param    array    $data
	 *  @return   string
	 */
	protected function run(string $template, $data = null)
	{
		if (is_array($data)) {
			extract($data);
		}

		ob_start();

		$template = $this->replaceBlacklisted($template);

		$templateFile = $this->platesPath . md5((string) $this->viewPath) . PHPEXT;

		file_put_contents($templateFile, $template);

		include $templateFile;

		$content = ob_get_clean();

		$this->ci->benchmark->mark('plate_execution_time_end');	//	Stop the timer

		return $content;
	}

	/**
	 * Blacklist known PHP functions
	 */
	private function replaceBlacklisted(string $template): array|string
	{
		$blacklists = [
			'exec(',
			'shell_exec(',
			'pcntl_exec(',
			'passthru(',
			'proc_open(',
			'system(',
			'posix_kill(',
			'posix_setsid(',
			'pcntl_fork(',
			'posix_uname(',
			'php_uname(',
			'phpinfo(',
			'popen(',
			'file_get_contents(',
			'file_put_contents(',
			'rmdir(',
			'mkdir(',
			'unlink(',
			'highlight_contents(',
			'symlink(',
			'apache_child_terminate(',
			'apache_setenv(',
			'define_syslog_variables(',
			'escapeshellarg(',
			'escapeshellcmd(',
			'eval(',
			'fp(',
			'fput(',
			'ftp_connect(',
			'ftp_exec(',
			'ftp_get(',
			'ftp_login(',
			'ftp_nb_fput(',
			'ftp_put(',
			'ftp_raw(',
			'ftp_rawlist(',
			'highlight_file(',
			'ini_alter(',
			'ini_get_all(',
			'ini_restore(',
			'inject_code(',
			'mysql_pconnect(',
			'openlog(',
			'passthru(',
			'php_uname(',
			'phpAds_remoteInfo(',
			'phpAds_XmlRpc(',
			'phpAds_xmlrpcDecode(',
			'phpAds_xmlrpcEncode(',
			'posix_getpwuid(',
			'posix_kill(',
			'posix_mkfifo(',
			'posix_setpgid(',
			'posix_setsid(',
			'posix_setuid(',
			'posix_uname(',
			'proc_close(',
			'proc_get_status(',
			'proc_nice(',
			'proc_open(',
			'proc_terminate(',
			'syslog(',
			'xmlrpc_entity_decode('
		];

		return str_replace($blacklists, '', $template);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Returns a protected variable
	 */
	protected function untouch(string $variable): string
	{
		return '{{' . $variable . '}}';
	}

	// --------------------------------------------------------------------------

	/**
	 *  Gets the content of a template to use inside the current template
	 *  It will inherit all the Global data
	 *
	 *  @param    array    $data
	 */
	protected function include(string $template, $data = null): string
	{
		$data = isset($data) ? array_merge($this->plateData, $data) : $this->plateData;

		//	Compile and execute the template
		return $this->run($this->compile($template), $data);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Gets the content of a template to use inside the current template
	 *  Mostly templates are used as partials
	 *  It will inherit all the Global data
	 *
	 *  @param    array    $data
	 */
	protected function partial(string $template, $data = null): string
	{
		$data = isset($data) ? array_merge($this->plateData, $data) : $this->plateData;

		//	Compile and execute the template
		return $this->run($this->compile($template), $data);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Gets the content of a template to use inside the current template
	 *  Mostly templates are used as sections
	 *  It will inherit all the Global data
	 *
	 *  @param    array    $data
	 */
	protected function section(string $template, $data = null): string
	{
		$data = isset($data) ? array_merge($this->plateData, $data) : $this->plateData;

		//	Compile and execute the template
		return $this->run($this->compile($template), $data);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Gets the content of a template to use inside the current template
	 * 	This stands in as just a name to use to set contents as components
	 *  It will inherit all the Global data
	 *
	 *  @param    array    $data
	 */
	protected function component(string $template, $data = null): string
	{
		$data = isset($data) ? array_merge($this->plateData, $data) : $this->plateData;

		//	Compile and execute the template
		return $this->run($this->compile($template), $data);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Gets the content of a section
	 *
	 *  @return   string
	 */
	protected function yield(string $section, string $default = '')
	{
		return $this->sections[$section] ?? $default;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Starts buffering the content of a section
	 *
	 *  If the param $value is different of null it will be the content of
	 *  the current section
	 *
	 *  @param string $section
	 *  @param mixed $value
	 */
	protected function start_section(string $section, mixed $value = null): void
	{
		$this->buffer[] = $section;

		if ($value !== null) {
			$this->close_section($value);
		} else {
			ob_start();
		}
	}

	// --------------------------------------------------------------------------

	/**
	 *  Stops buffering the content of a section
	 *
	 *  If the param $value is different of null it will be the
	 *  content of the current section
	 *
	 *   @param    mixed    $value
	 */
	protected function close_section(mixed $value = null): string
	{
		$lastSection = array_pop($this->buffer);

		if ($value !== null) {
			$this->extend_section($lastSection, $value);
		} else {
			$this->extend_section($lastSection, ob_get_clean());
		}

		return $lastSection;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Retrieves a line from the language file loaded
	 *
	 *  @param    string    $line        String line to load
	 *  @param    array     $params      Place-holders to parse in the string
	 */
	public function i18n(string $line, array $params = []): string
	{
		[$file, $string] = array_pad(explode('.', $line), 2, null);

		//	Here tries to get the string with the $file variable...
		$line = $this->language[$file] ?? $file;

		if ($string !== null) {
			if (!isset($this->i18nLoaded[$file]) || $this->i18nLoaded[$file] !== $this->locale) {
				//	Load the file into the language array
				$this->language = array_merge($this->language, $this->ci->lang->load($file, $this->locale, true));
				//	Save the loaded file and idiom
				$this->i18nLoaded[$file] = $this->locale;
			}

			//	... and here, the variable used is $string
			$line = $this->language[$string] ?? $string;
		}

		//	Deals with the place-holders for the string
		if (!empty($params) && is_array($params)) {
			foreach ($params as $name => $content) {
				$line = (str_contains((string) $line, ':' . strtoupper($name)))
					? str_replace(':' . strtoupper($name), strtoupper((string) $content), (string) $line)
					: $line;

				$line = (str_contains((string) $line, ':' . ucfirst($name)))
					? str_replace(':' . ucfirst($name), ucfirst((string) $content), (string) $line)
					: $line;

				$line = (str_contains((string) $line, ':' . $name))
					? str_replace(':' . $name, $content, (string) $line)
					: $line;
			}
		}

		return $line;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Returns a json_encoded string
	 *
	 *  @param    array     $array        Source of javascript file
	 */
	public function jsonEncode(array $array = []): string
	{
		if (empty($array)) {
			return "";
		}

		return ' ' . json_encode($array, JSON_THROW_ON_ERROR);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Returns a script tag with src and given attributes
	 *
	 *  @param    string    $src   Source of javascript file
	 *  @param    string|array    $attributes  Additional attributes in a string
	 */
	public function javascript(string $src = '', string|array $attributes = ''): string
	{
		$line = '';

		if (!empty($src)) {
			$line = 'src="' . $src . '"';
		}

		if (!empty($attributes)) {
			$line = $line . ' ' . $attributes;
		}

		return $line = "\n\n\t<script " . $line . "></script>\n";
	}

	// --------------------------------------------------------------------------

	/**
	 *  Retrieves a line from the language file loaded in singular or plural form
	 *
	 * @param mixed[] $params
	 */
	public function inflector(string $line, int|array $number, array $params = []): ?string
	{
		$lines = explode('|', $this->i18n($line, $params));

		if (is_array($number)) {
			$number = count($number);
		}

		foreach ($lines as $string) {
			//	Searches for a given amount
			preg_match_all('/\{([0-9]{1,})\}/', $string, $matches);
			[$str, $count] = $matches;

			if (isset($count[0]) && $count[0] == $number) {
				return str_replace('{' . $count[0] . '} ', '', $string);
			}

			//	Searches for a range interval
			preg_match_all('/\[([0-9]{1,}),\s?([0-9*]{1,})\]/', $string, $matches);
			[$str, $start, $end] = $matches;

			if (isset($end[0]) && $end[0] !== '*') {
				if (in_array($number, range($start[0], $end[0]))) {
					return preg_replace('/\[.*?\]\s?/', '', $string);
				}
			} elseif (isset($end[0]) && $end[0] === '*') {
				if ($number >= $start[0]) {
					return preg_replace('/\[.*?\]\s?/', '', $string);
				}
			}
		}

		return ($number > 1) ? $lines[1] : $lines[0];
	}

	// --------------------------------------------------------------------------

	/**
	 *  Iterates through a variable to include content
	 *
	 *  @param    string   $default
	 *  @param mixed[] $variable
	 */
	protected function each(string $template, array $variable, string $label, $default = null): string
	{
		$content = '';

		if ((is_countable($variable) ? count($variable) : 0) > 0) {
			foreach ($variable as $item) {
				$dataForInclude = [$label => $item];
				$content .= $this->include($template, $dataForInclude);
			}
		} else {
			$content .= ($default !== null) ? $this->include($default) : '';
		}

		return $content;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites custom directives defined by the user
	 */
	protected function compile_directive(string $value): string
	{
		foreach ($this->directives as $compilator) {
			$value = call_user_func($compilator, $value);
		}

		return $value;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates comment into PHP comment
	 */
	protected function compile_comment(string $content): ?string
	{
		$pattern = '/\{\{--(.+?)(--\}\})?\n/';
		$returnPattern = '/\{\{--((.|\s)*?)--\}\}/';

		$content = preg_replace($pattern, "<?php // $1 ?>", $content);

		return preg_replace($returnPattern, "<?php /* $1 */ ?>\n", $content);
	}

	/**
	 * Compile custom multiline HTML comments !!! ... !!!
	 * Removes any internal '-->' before wrapping.
	 */
	protected function compile_multiline_html_comment(string $content): string
	{
		$pattern = '/\!\!\!\s*(.*?)\s*\!\!\!/s'; // 's' for dotall, capture content in $1
		return preg_replace_callback($pattern, function ($matches) {
			$innerContent = $matches[1];
			// Remove '-->' from innerContent
			$cleanedContent = str_replace('-->', '-- >', $innerContent); // Replace with space to avoid breaking if it was intentional structure
			return '<!--' . $cleanedContent . '-->';
		}, $content);
	}

	// --------------------------------------------------------------------------

	/**
	 * Compile html view comments.
	 */
	protected function compile_html_comment(string $view): ?string
	{
		return preg_replace_callback('/###(.*?)###/', function ($matches) {
			$comment = trim($matches[1]);
			return sprintf('<!-- %s  -->', $comment);
		}, $view);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates conditional echo statement into PHP echo statement
	 */
	protected function compile_ternary(string $content): string|array
	{
		$pattern = '/\{\{\s\$(.\w*)\sor.[\'"]([^\'"]+)[\'"]\s\}\}/';

		preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

		foreach ($matches as $var) {
			$content = isset($this->plateData[$var[1]]) ? str_replace($var[0], sprintf('<?php echo $%s; ?>', $var[1]), $content) : str_replace($var[0], sprintf('<?php echo \'%s\'; ?>', $var[2]), $content);
		}

		return $content;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Preserves an expression to be displayed in the browser
	 */
	protected function compile_preserved(string $content): ?string
	{
		$pattern = '/@(\{\{(.+?)\}\})/';

		return preg_replace($pattern, '<?php echo $this->untouch("$2"); ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates unescaped echo statement {!! $data !!} into PHP echo statement.
	 */
	protected function compile_unescaped_echo(string $content): string
	{
		$pattern = '/\{!!\s*(.+?)\s*!!\}/s';
		return preg_replace($pattern, '<?php echo $1; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates echo statement into PHP echo statement
	 */
	protected function compile_echo(string $content): ?string
	{
		$pattern = '/\{\{(.+?)\}\}/';
		return preg_replace($pattern, '<?php echo esc($1); ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates variable handling function into valid PHP
	 */
	protected function compile_variable(string $content): ?string
	{
		$pattern = '/(\s*)@(isset|empty)(\s*\(.*\))/';

		return preg_replace($pattern, '$1<?php if ($2$3): ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates forelse statement into valid PHP
	 */
	protected function compile_forelse(string $content): string|array
	{
		$pattern = '/(\s*)@forelse(\s*\(.*\))(\s*)/';

		preg_match_all($pattern, $content, $matches);

		foreach ($matches[0] as $forelse) {
			$variablePattern = '/\$[^\s]*/';

			preg_match($variablePattern, (string) $forelse, $variable);

			$ifStatement = sprintf('<?php if (count(%s) > 0): ?>', $variable[0]);
			$searchPattern = '/(\s*)@forelse(\s*\(.*\))/';
			$replacement = '$1' . $ifStatement . '<?php foreach $2: ?>';

			$content = str_replace($forelse, preg_replace($searchPattern, $replacement, (string) $forelse), $content);
		}

		return $content;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates empty statement into valid PHP
	 */
	protected function compile_empty(string $content): array|string
	{
		return str_replace('@empty', '<?php endforeach; ?><?php else: ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates endforelse statement into valid PHP
	 */
	protected function compile_endforelse(string $content): array|string
	{
		return str_replace('@endforelse', '<?php endif; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates opening structures into PHP opening structures
	 */
	protected function compile_opening_statements(string $content): ?string
	{
		$pattern = '/(\s*)@(if|elseif|foreach|for|while)(\s*\(.*\))/';

		return preg_replace($pattern, '$1<?php $2$3: ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates else statement into PHP else statement
	 */
	protected function compile_else(string $content): ?string
	{
		$pattern = '/(\s*)@(else)(\s*)/';

		return preg_replace($pattern, '$1<?php $2: ?>$3', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates continue() statement into PHP continue statement
	 */
	protected function compile_continueIf(string $content): ?string
	{
		$pattern = '/(\s*)@(continue)(\s*\(.*\))/';

		return preg_replace($pattern, '$1<?php if $3: ?>$1<?php $2; ?>$1<?php endif; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates continue statement into PHP continue statement
	 */
	protected function compile_continue(string $content): ?string
	{
		$pattern = '/(\s*)@(continue)(\s*)/';

		return preg_replace($pattern, '$1<?php $2; ?>$3', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates break() statement into PHP break statement
	 */
	protected function compile_breakIf(string $content): ?string
	{
		$pattern = '/(\s*)@(break)(\s*\(.*\))/';

		return preg_replace($pattern, '$1<?php if $3: ?>$1<?php $2; ?>$1<?php endif; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates break statement into PHP break statement
	 */
	protected function compile_break(string $content): ?string
	{
		$pattern = '/(\s*)@(break)(\s*)/';

		return preg_replace($pattern, '$1<?php $2; ?>$3', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates closing structures into PHP closing structures
	 */
	protected function compile_closing_statements(string $content): ?string
	{
		$pattern = '/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/';

		return preg_replace($pattern, '$1<?php $2; ?>$3', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates each statement into valid PHP
	 */
	protected function compile_each(string $content): ?string
	{
		$pattern = '/(\s*)@each(\s*\(.*?\))(\s*)/';

		return preg_replace($pattern, '$1<?php echo $this->each$2; ?>$3', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates unless statement into valid PHP
	 */
	protected function compile_unless(string $content): ?string
	{
		$pattern = '/(\s*)@unless(\s*\(.*\))/';

		return preg_replace($pattern, '$1<?php if ( ! ($2)): ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates endunless, endisset and endempty statements into valid PHP
	 */
	protected function compile_endunless(string $content): ?string
	{
		$pattern = '/(\s*)@(endunless|endisset|endempty)/';

		return preg_replace($pattern, '<?php endif; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @includeIf statement into valid PHP
	 */
	protected function compile_includeIf(string $content): ?string
	{
		$pattern = "/(\s*)@includeIf\s*(\('(.*?)'.*\))/";

		return preg_replace($pattern, '$1<?php echo ($this->exists("$3", false) === true) ? $this->include$2 : ""; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @include statement into valid PHP
	 */
	protected function compile_include(string $content): ?string
	{
		$pattern = '/(\s*)@include(\s*\(.*\))/';

		return preg_replace($pattern, '$1<?php echo $this->include$2; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @head statement into valid PHP
	 */
	protected function compile_head(string $content): ?string
	{
		$pattern = '/(\s*)@head(\s*\(.*\))/';

		return preg_replace($pattern, '$1<?php echo $this->partial$2; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @partial statement into valid PHP
	 */
	protected function compile_partial(string $content): ?string
	{
		$pattern = '/(\s*)@partial(\s*\(.*\))/';

		return preg_replace($pattern, '$1<?php echo $this->partial$2; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @section statement into valid PHP
	 */
	protected function compile_section(string $content): ?string
	{
		$pattern = '/(\s*)@section(\s*\(.*\))/';

		return preg_replace($pattern, '$1<?php echo $this->section$2; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @component statement into valid PHP
	 */
	protected function compile_component(string $content): ?string
	{
		$pattern = '/(\s*)@component(\s*\(.*\))/';

		return preg_replace($pattern, '$1<?php echo $this->component$2; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @extends statement into valid PHP
	 */
	protected function compile_extends(string $content): ?string
	{
		$pattern = '/(\s*)@extends(\s*\(.*\))/';

		// Find and if there is none, just return the content
		if (!preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
			return $content;
		}

		$content = preg_replace($pattern, '', $content);

		// Layouts are included in the end of template
		foreach ($matches as $include) {
			$content .= $include[1] . '<?php echo $this->include' . $include[2] . "; ?>";
		}

		return $content;
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @yield statement into Section statement
	 */
	protected function compile_yield(string $content): ?string
	{
		$pattern = '/(\s*)@yield(\s*\(.*\))/';

		return preg_replace($pattern, '<?php echo $this->yield$2; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates Show statement into valid PHP
	 */
	protected function compile_show(string $content): array|string
	{
		return str_replace('@show', '<?php echo $this->yield($this->close_section()); ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @usesection statement as Section statement
	 */
	protected function compile_start_section(string $content): ?string
	{
		$pattern = '/(\s*)@usesection(\s*\(.*\))/';

		return preg_replace($pattern, '<?php $this->start_section$2; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @endsection statement into Section statement
	 */
	protected function compile_close_section(string $content): array|string
	{
		return str_replace('@endsection', '<?php $this->close_section(); ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @php statement into valid PHP
	 */
	protected function compile_php(string $content): array|string
	{
		return str_replace('@php', '<?php', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @endphp statement into valid PHP
	 */
	protected function compile_endphp(string $content): array|string
	{
		return str_replace('@endphp', '?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @doctype statement into valid PHP
	 */
	protected function compile_doctype(string $content): array|string
	{
		return str_replace('@doctype', '<!DOCTYPE html>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @endhtml statement into valid PHP
	 */
	protected function compile_endhtml(string $content): array|string
	{
		return str_replace('@endhtml', '</html>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @json statement into valid PHP
	 */
	protected function compile_json(string $content): ?string
	{
		$pattern = '/(\s*)@json(\s*\(.*\))/';

		return preg_replace($pattern, '<?php echo $this->jsonEncode$2; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @script statement into valid PHP
	 */
	protected function compile_script(string $content): array|string
	{
		return str_replace('@script', '<script>', $content);
	}

	// --------------------------------------------------------------------------
	/**
	 *  Rewrites Plates @endscript statement into valid PHP
	 */
	protected function compile_endscript(string $content): array|string
	{
		return str_replace('@endscript', '</script>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @script statement into valid PHP
	 */
	protected function compile_javascript(string $content): ?string
	{
		$pattern = '/(\s*)@javascript(\s*\(.*\))/';

		return preg_replace($pattern, '<?php echo $this->javascript$2; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @lang statement into valid PHP
	 */
	protected function compile_lang(string $content): ?string
	{
		$pattern = '/(\s*)@lang(\s*\(.*\))/';

		return preg_replace($pattern, '<?php echo $this->i18n$2; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @choice statement into valid PHP
	 */
	protected function compile_choice(string $content): ?string
	{
		$pattern = '/(\s*)@choice(\s*\(.*\))/';

		return preg_replace($pattern, '<?php echo $this->inflector$2; ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @csrf statement into valid PHP
	 */
	protected function compile_csrf(string $content): array|string
	{
		return str_replace('@csrf', '<?php echo csrf() ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Rewrites Plates @honeypot statement into valid PHP
	 */
	protected function compile_honeypot(string $content): array|string
	{
		return str_replace('@honeypot', '<?php echo honeypot() ?>', $content);
	}

	// --------------------------------------------------------------------------

	/**
	 *  Stores the content of a section
	 *  It also replaces the Plates @parent statement with the previous section
	 */
	private function extend_section(string $section, string $content): void
	{
		if (isset($this->sections[$section])) {
			$this->sections[$section] = str_replace('@parent', $content, (string) $this->sections[$section]);
		} else {
			$this->sections[$section] = $content;
		}
	}
}

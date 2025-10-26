<?php

namespace Base\Http;

use Base\Http\CurlException;

/**
 * An Easy Curl Library
 *
 * Serving as an http client to work with remote servers
 * Built to easily use than the native PHP cURL bindings
 * Borrowed some ideas from Philsturgeon's CodeIgniter-Curl library
 *
 * @package HttpCurl
 * @author Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 * @author Philip Sturgeon
 * @license http://philsturgeon.co.uk/code/dbad-license dbad-license
 */
class HttpCurl
{
    /**
     * Http Base URL
     *
     * @var string
     */
    public $baseUrl = '';

    /**
     * User Agent
     *
     * @var string
     */
    public $userAgent = 'An API Agent';

    /**
     * Curl Connect Timeout
     *
     * Maximum amount of time in seconds that is allowed
     * to make the connection to the API server
     * @var int
     */
    public $curlConnectTimeout = 30;

    /**
     * Curl Timeout
     *
     * Maximum amount of time in seconds to which the
     * execution of cURL call will be limited
     * @var int
     */
    public $curlTimeout = 30;

    /**
     * Last response raw
     *
     * @var string
     */
    private $lastResponseRaw;

    /**
     * Last response
     *
     * @var string|array
     */
    private $lastResponse;

    /**
     * Constant Http Methods
     */
    public const GET    = 'GET';
    public const POST   = 'POST';
    public const PUT    = 'PUT';
    public const PATCH  = 'PATCH';
    public const DELETE = 'DELETE';

    /**
     * Contains the cURL response for debug
     *
     * @var string
     */
    protected $response = '';

    /**
     * Contains the cURL handler for a session
     *
     * @var \CurlHandle|resource|null
     */
    protected $session;

    /**
     * URL of the session
     *
     * @var string
     */
    protected $url = '';

    /**
     * Gathers all curl_setopt_array
     *
     * @var array
     */
    protected $options = [];

    /**
     * Gathers all Http Headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Add file property to the class
     *
     * @var array
     */
    protected $files = [];

    /**
     * Get Error code returned as an int
     *
     * @var int
     */
    protected $errorCode;

    /**
     * Get Error message returned as a string
     *
     * @var string Error message returned as a string
     */
    protected $errorString;

    /**
     * Check Error Status
     *
     * @var bool
     */
    protected $hasError = false;

    /**
     * Get all curl request Information
     *
     * @var array
     */
    public $info = [];

    /**
     * Hold Http Status Code
     *
     * @var int
     */
    protected $status;

    /**
     * @param string $url
     * @param string $userAgent
     * @throws \Base\Http\CurlException if the library failed to initialize
     */
    public function __construct($url = '', $userAgent = '')
    {
        if ($userAgent) {
            $this->userAgent = $userAgent;
        }

        if (function_exists('log_message')) {
            log_message('info', 'cURL Class Initialized');
        }

        if (!$this->isEnabled()) {
            throw new CurlException('cURL Class - PHP was not built with cURL enabled. Rebuild PHP with --with-curl to use cURL.');
        }

        if ($url) {
            $this->baseUrl = $this->formatUrl($url);
        }
    }

    /**
     * Format URL to ensure it has proper protocol
     *
     * @param string $url
     * @return string
     */
    private function formatUrl($url)
    {
        // If no protocol in URL, assume its a local link
        if (!preg_match('!^\w+://!i', $url)) {
            // Using url function from ci_core_helper.php if available
            if (function_exists('url')) {
                $url = url($url);
            } else {
                // Fallback - assume http if no protocol
                $url = 'http://' . ltrim($url, '/');
            }
        }
        return $url;
    }

    /**
     * Start a session from a URL
     *
     * @param string $url
     * @return HttpCurl
     */
    public function create($url)
    {
        $this->url = $this->formatUrl($url);
        $this->session = curl_init($this->url);

        if ($this->session === false) {
            throw new CurlException('Failed to initialize cURL session');
        }

        return $this;
    }

    /**
     * Get the Base Url from the Http Instance
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Add a file to the request
     *
     * @param string $field The form field name
     * @param string $filePath Path to the file
     * @param string|null $mimeType MIME type of the file (optional)
     * @param string|null $filename Custom filename (optional)
     * @return HttpCurl
     * @throws CurlException
     */
    public function addFile($field, $filePath, $mimeType = null, $filename = null)
    {
        if (!file_exists($filePath)) {
            throw new CurlException("File not found: $filePath");
        }

        if (!is_readable($filePath)) {
            throw new CurlException("File is not readable: $filePath");
        }

        // Use CURLFile for PHP 5.5+
        if (class_exists('CURLFile')) {
            $curlFile = new \CURLFile($filePath, $mimeType, $filename);
        } else {
            // Fallback for older PHP versions (though your library targets PHP 8.3)
            $curlFile = '@' . $filePath;
            if ($mimeType) {
                $curlFile .= ';type=' . $mimeType;
            }
            if ($filename) {
                $curlFile .= ';filename=' . $filename;
            }
        }

        // Store the file data - we'll use this in setupPost/setupPut methods
        if (!isset($this->files)) {
            $this->files = [];
        }
        $this->files[$field] = $curlFile;

        return $this;
    }

    /**
     * Add multiple files at once
     *
     * @param array $files Array of files where key is field name and value is file path or array with file details
     * @return HttpCurl
     * @throws CurlException
     */
    public function addFiles($files)
    {
        foreach ($files as $field => $fileData) {
            if (is_string($fileData)) {
                // Simple file path
                $this->addFile($field, $fileData);
            } elseif (is_array($fileData)) {
                // Array with file details
                $filePath = $fileData['path'] ?? $fileData['file'] ?? '';
                $mimeType = $fileData['type'] ?? $fileData['mime'] ?? null;
                $filename = $fileData['name'] ?? $fileData['filename'] ?? null;
                
                $this->addFile($field, $filePath, $mimeType, $filename);
            }
        }
        
        return $this;
    }

    /**
     * Convenience method for uploading files via POST
     *
     * @param string $path
     * @param array $files Array of files to upload
     * @param array $params Additional form parameters
     * @param array $options Additional cURL options
     * @return HttpCurl
     */
    public function upload($path = '', $files = [], $params = [], $options = [])
    {
        $fullUrl = $this->buildUrl($path);
        $this->create($fullUrl);
        
        // Add files to the request
        $this->addFiles($files);
        
        // Setup POST with parameters
        $this->setupPost($params);
        $this->options($options);

        return $this;
    }

    /**
     * HttpCurl Request Method
     *
     * Quickly make a simple and easy cURL call with one line.
     *
     * @param string $method
     * @param string $path
     * @param array $params
     * @param array $options
     * @return mixed
     */
    public function request($method, $path = '', array $params = [], $options = [])
    {
        $method = strtolower($method);
        
        // Build full URL
        $fullUrl = $this->buildUrl($path);
        
        // Create session with full URL
        $this->create($fullUrl);
        
        // Call the appropriate method
        switch ($method) {
            case 'get':
                $this->setupGet($params);
                break;
            case 'post':
                $this->setupPost($params);
                break;
            case 'put':
                $this->setupPut($params);
                break;
            case 'patch':
                $this->setupPatch($params);
                break;
            case 'delete':
                $this->setupDelete($params);
                break;
            default:
                throw new CurlException("Unsupported HTTP method: $method");
        }

        // Add in the specific options provided
        $this->options($options);
        return $this->execute();
    }

    /**
     * Build full URL from base and path
     *
     * @param string $path
     * @return string
     */
    private function buildUrl($path = '')
    {
        if (empty($path)) {
            return $this->baseUrl;
        }

        // If path is already a full URL, use it as is
        if (preg_match('!^\w+://!i', $path)) {
            return $path;
        }

        // Combine base URL with path
        $baseUrl = rtrim($this->baseUrl, '/');
        $path = ltrim($path, '/');
        
        return $baseUrl . '/' . $path;
    }

    /**
     * HttpCurl Get method
     *
     * @param string $path
     * @param array $params
     * @return HttpCurl
     */
    public function get($path = '', $params = [])
    {
        $fullUrl = $this->buildUrl($path);
        
        // Add query parameters to URL for GET requests
        if (!empty($params)) {
            $separator = strpos($fullUrl, '?') !== false ? '&' : '?';
            $fullUrl .= $separator . http_build_query($params, '', '&');
        }

        $this->create($fullUrl);
        $this->setupGet();

        return $this;
    }

    /**
     * Setup GET request options
     *
     * @param array $params
     * @return void
     */
    private function setupGet($params = [])
    {
        $this->method(self::GET);
        // GET requests don't use POST fields
        $this->option(CURLOPT_HTTPGET, true);
    }

    /**
     * HttpCurl Post Method
     *
     * @param string $path
     * @param array $params
     * @param array $options
     * @return HttpCurl
     */
    public function post($path = '', $params = [], $options = [])
    {
        $fullUrl = $this->buildUrl($path);
        $this->create($fullUrl);
        
        $this->setupPost($params);
        $this->options($options);

        return $this;
    }

    /**
     * Setup POST request options
     *
     * @param array|string $params
     * @return void
     */
    private function setupPost($params = [])
    {
        $this->method(self::POST);
        $this->option(CURLOPT_POST, true);
        
        // Handle file uploads
        if (!empty($this->files)) {
            // When files are present, merge with regular params
            $postData = array_merge((array)$params, $this->files);
            $this->option(CURLOPT_POSTFIELDS, $postData);
            
            // Don't set Content-Type header when uploading files
            // Let cURL set it automatically with boundary
        } elseif (!empty($params)) {
            // Regular POST without files
            if (is_array($params)) {
                $params = http_build_query($params, '', '&');
            }
            $this->option(CURLOPT_POSTFIELDS, $params);
        }
    }

    /**
     * HttpCurl Put Method
     *
     * @param string $path
     * @param array $params
     * @param array $options
     * @return HttpCurl
     */
    public function put($path = '', $params = [], $options = [])
    {
        $fullUrl = $this->buildUrl($path);
        $this->create($fullUrl);
        
        $this->setupPut($params);
        $this->options($options);

        return $this;
    }

    /**
     * Setup PUT request options
     *
     * @param array|string $params
     * @return void
     */
    private function setupPut($params = [])
    {
        $this->method(self::PUT);
        
        // Handle file uploads
        if (!empty($this->files)) {
            // For PUT with files, we need to use POST fields
            $postData = array_merge((array)$params, $this->files);
            $this->option(CURLOPT_POSTFIELDS, $postData);
        } elseif (!empty($params)) {
            if (is_array($params)) {
                $params = http_build_query($params, '', '&');
            }
            $this->option(CURLOPT_POSTFIELDS, $params);
        }
    }

    /**
     * HttpCurl Patch Method
     *
     * @param string $path
     * @param array $params
     * @param array $options
     * @return HttpCurl
     */
    public function patch($path = '', $params = [], $options = [])
    {
        $fullUrl = $this->buildUrl($path);
        $this->create($fullUrl);
        
        $this->setupPatch($params);
        $this->options($options);

        return $this;
    }

    /**
     * Setup PATCH request options
     *
     * @param array|string $params
     * @return void
     */
    private function setupPatch($params = [])
    {
        $this->method(self::PATCH);
        
        if (!empty($params)) {
            if (is_array($params)) {
                $params = http_build_query($params, '', '&');
            }
            $this->option(CURLOPT_POSTFIELDS, $params);
        }
    }

    /**
     * HttpCurl Delete Method
     *
     * @param string $path
     * @param array $params
     * @param array $options
     * @return HttpCurl
     */
    public function delete($path = '', $params = [], $options = [])
    {
        $fullUrl = $this->buildUrl($path);
        $this->create($fullUrl);
        
        $this->setupDelete($params);
        $this->options($options);

        return $this;
    }

    /**
     * Setup DELETE request options
     *
     * @param array|string $params
     * @return void
     */
    private function setupDelete($params = [])
    {
        $this->method(self::DELETE);
        
        if (!empty($params)) {
            if (is_array($params)) {
                $params = http_build_query($params, '', '&');
            }
            $this->option(CURLOPT_POSTFIELDS, $params);
        }
    }

    /**
     * HttpCurl Set Cookie Method
     *
     * @param array|string $params
     * @return HttpCurl
     */
    public function setCookies($params = [])
    {
        if (is_array($params)) {
            $params = http_build_query($params, '', '; ');
        }

        $this->option(CURLOPT_COOKIE, $params);
        return $this;
    }

    /**
     * Http Header Method
     *
     * @param string $header
     * @param string $content
     * @return HttpCurl
     */
    public function header($header, $content = null)
    {
        if ($content !== null) {
            $this->headers[] = $header . ': ' . $content;
        } else {
            $this->headers[] = $header;
        }
        return $this;
    }

    /**
     * Set multiple headers at once
     *
     * @param array $headers
     * @return HttpCurl
     */
    public function headers($headers = [])
    {
        foreach ($headers as $key => $value) {
            if (is_numeric($key)) {
                $this->header($value);
            } else {
                $this->header($key, $value);
            }
        }
        return $this;
    }

    /**
     * Http Method
     *
     * @param string $method
     * @return HttpCurl
     */
    public function method($method)
    {
        $this->options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
        return $this;
    }

    /**
     * Http Login
     *
     * @param string $username
     * @param string $password
     * @param string $type
     * @return HttpCurl
     */
    public function login($username = '', $password = '', $type = 'any')
    {
        $authType = 'CURLAUTH_' . strtoupper($type);
        if (defined($authType)) {
            $this->option(CURLOPT_HTTPAUTH, constant($authType));
        }
        $this->option(CURLOPT_USERPWD, $username . ':' . $password);
        return $this;
    }

    /**
     * HttpCurl Proxy Method
     *
     * @param string $url
     * @param int $port
     * @return HttpCurl
     */
    public function proxy($url = '', $port = 80)
    {
        $this->option(CURLOPT_HTTPPROXYTUNNEL, true);
        $this->option(CURLOPT_PROXY, $url . ':' . $port);
        return $this;
    }

    /**
     * HttpCurl Proxy Login Method
     *
     * @param string $username
     * @param string $password
     * @return HttpCurl
     */
    public function proxyLogin($username = '', $password = '')
    {
        $this->option(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
        return $this;
    }

    /**
     * HttpCurl SSL Method
     *
     * @param bool $verifyPeer
     * @param int $verifyHost
     * @param string|null $pathToCert
     * @return HttpCurl
     */
    public function ssl($verifyPeer = true, $verifyHost = 2, $pathToCert = null)
    {
        if ($verifyPeer) {
            $this->option(CURLOPT_SSL_VERIFYPEER, true);
            $this->option(CURLOPT_SSL_VERIFYHOST, $verifyHost);
            if ($pathToCert && file_exists($pathToCert)) {
                $this->option(CURLOPT_CAINFO, realpath($pathToCert));
            }
        } else {
            $this->option(CURLOPT_SSL_VERIFYPEER, false);
            $this->option(CURLOPT_SSL_VERIFYHOST, $verifyHost);
        }
        return $this;
    }

    /**
     * Set JSON content type and encode data
     *
     * @param array|object $data
     * @return HttpCurl
     */
    public function json($data)
    {
        $this->header('Content-Type', 'application/json');
        $this->option(CURLOPT_POSTFIELDS, json_encode($data));
        return $this;
    }

    /**
     * HttpCurl Options Method
     *
     * @param array $options
     * @return HttpCurl
     */
    public function options($options = [])
    {
        foreach ($options as $option => $value) {
            $this->option($option, $value);
        }
        return $this;
    }

    /**
     * HttpCurl Option Method
     *
     * @param string|int $option
     * @param mixed $value
     * @return HttpCurl
     */
    public function option($option, $value)
    {
        if (is_string($option) && !is_numeric($option)) {
            $constant = 'CURLOPT_' . strtoupper($option);
            if (defined($constant)) {
                $option = constant($constant);
            }
        }

        $this->options[$option] = $value;
        return $this;
    }

    /**
     * Execute a curl session and return results
     *
     * @return mixed
     * @throws CurlException
     */
    public function execute()
    {
        if (!$this->session) {
            throw new CurlException('No cURL session available. Call create() first.');
        }

        // Set default options
        $this->setDefaultOptions();

        // Set headers if any
        if (!empty($this->headers)) {
            $this->option(CURLOPT_HTTPHEADER, $this->headers);
        }

        // Apply all options to the session
        if (!curl_setopt_array($this->session, $this->options)) {
            throw new CurlException('Failed to set cURL options');
        }

        // Execute the request
        $this->lastResponseRaw = curl_exec($this->session);
        $this->info = curl_getinfo($this->session);
        $this->status = $this->info['http_code'] ?? 0;

        // Check for errors
        if ($this->lastResponseRaw === false) {
            $this->handleError();
            return false;
        }

        // Success
        curl_close($this->session);
        $this->lastResponse = $this->processResponse($this->lastResponseRaw);
        $this->reset();
        $this->hasError = false;

        return $this->lastResponse;
    }

    /**
     * Set default cURL options
     *
     * @return void
     */
    private function setDefaultOptions()
    {
        $defaults = [
            CURLOPT_CONNECTTIMEOUT => $this->curlConnectTimeout,
            CURLOPT_TIMEOUT => $this->curlTimeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => false, // Handle errors manually
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HEADER => false,
        ];

        // Only set follow location if safe
        if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
            $defaults[CURLOPT_FOLLOWLOCATION] = true;
            $defaults[CURLOPT_MAXREDIRS] = 5;
        }

        foreach ($defaults as $option => $value) {
            if (!isset($this->options[$option])) {
                $this->options[$option] = $value;
            }
        }
    }

    /**
     * Handle cURL errors
     *
     * @return void
     */
    private function handleError()
    {
        $this->errorCode = curl_errno($this->session);
        $this->errorString = curl_error($this->session);
        $this->hasError = true;

        curl_close($this->session);
        $this->reset();
    }

    /**
     * Process response data
     *
     * @param string $response
     * @return string|array
     */
    private function processResponse($response)
    {
        // Try to decode JSON responses
        if ($this->isJsonResponse()) {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $response;
    }

    /**
     * Check if response is JSON
     *
     * @return bool
     */
    private function isJsonResponse()
    {
        $contentType = $this->info['content_type'] ?? '';
        return strpos($contentType, 'application/json') !== false;
    }

    /**
     * Return raw response data from the last request
     *
     * @return string|null
     */
    public function getLastResponseRaw()
    {
        return $this->lastResponseRaw;
    }

    /**
     * Return decoded response data from the last request
     *
     * @return string|array|null
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Get Status Code
     *
     * @return int
     */
    public function statusCode()
    {
        return $this->status ?? 0;
    }

    /**
     * Checks whether curl has an error
     *
     * @return bool
     */
    public function hasError()
    {
        return $this->hasError;
    }

    /**
     * Get Curl Error Message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return 'cURL Error: ' . $this->errorString . ' (Code: ' . $this->errorCode . ')';
    }

    /**
     * Get Curl Error String
     *
     * @return string
     */
    public function error()
    {
        return $this->errorString;
    }

    /**
     * Get Curl Error Code
     *
     * @return int
     */
    public function errorCode()
    {
        return $this->errorCode;
    }

    /**
     * Is Curl available
     *
     * @return bool
     */
    public function isEnabled()
    {
        return function_exists('curl_init');
    }

    /**
     * Get request info
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Curl Debugging
     *
     * @return string
     */
    public function debug()
    {
        $output = "=============================================\n";
        $output .= "CURL Debug Information\n";
        $output .= "=============================================\n";
        $output .= "URL: " . ($this->url ?: $this->baseUrl) . "\n";
        $output .= "Status Code: " . $this->statusCode() . "\n";
        $output .= "Response:\n" . $this->lastResponseRaw . "\n\n";

        if ($this->hasError()) {
            $output .= "=============================================\n";
            $output .= "Errors:\n";
            $output .= "Code: " . $this->errorCode . "\n";
            $output .= "Message: " . $this->errorString . "\n";
        }

        $output .= "=============================================\n";
        $output .= "Info:\n";
        $output .= print_r($this->info, true);

        return $output;
    }

    /**
     * Debug BaseUrl content
     *
     * @return array
     */
    public function debugRequest()
    {
        return [
            'base_url' => $this->baseUrl,
            'current_url' => $this->url,
            'headers' => $this->headers,
            'options' => $this->options
        ];
    }

    /**
     * Reset already assigned properties
     *
     * @return HttpCurl
     */
    public function reset()
    {
        $this->lastResponseRaw = '';
        $this->headers = [];
        $this->options = [];
        $this->files = [];
        $this->errorCode = null;
        $this->errorString = '';
        $this->session = null;
        $this->url = '';

        return $this;
    }

    /**
     * Destructor to ensure cURL handle is closed
     */
    public function __destruct()
    {
        if ($this->session) {
            // curl_close($this->session); // deprecated in PHP 8.5
            unset($this->session);
        }
    }
}

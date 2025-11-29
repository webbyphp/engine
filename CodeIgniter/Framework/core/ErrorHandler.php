<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class CI_ErrorHandler
{

    private $CI;
    private $config;
    private $errorTemplatesPath;
    private $originalErrorHandler;
    private $originalExceptionHandler;
    private $isInitialized = false;

    public function __construct($config = [])
    {
        $this->CI = app();

        // Default configuration with enhanced options
        $this->config = array_merge([
            'environment' => ENVIRONMENT,
            'debug' => (ENVIRONMENT !== 'production'),
            'ide_links' => [
                'antigravity' => 'antigravity://file/{file}:{line}',
                'vscode' => 'vscode://file/{file}:{line}',
                'cursor' => 'cursor://file/{file}:{line}',
                'phpstorm' => 'phpstorm://open?file={file}&line={line}',
                'sublime' => 'subl://open?url=file://{file}&line={line}',
                'atom' => 'atom://core/open/file?filename={file}&line={line}',
                'vim' => 'vim://open?url=file://{file}&line={line}',
                'emacs' => 'emacs://open?url=file://{file}&line={line}'
            ],
            'default_ide' => config_item('use_editor') ?? 'vscode',
            'max_trace_files' => 15,
            'show_sourceCode' => true,
            'sourceCode_lines' => 12,
            'enable_ajax_errors' => true,
            'log_errors' => true,
            'cache_sourceCode' => true,
            'show_request_data' => true,
            'highlight_syntax' => true,
            'dark_theme' => true
        ], $config);

        $this->errorTemplatesPath = APPROOT . 'Views/errors/';

        // Only register handlers in debug mode
        if ($this->config['debug']) {
            $this->registerHandlers();
        }
    }

    /**
     * Register error and exception handlers with backup restoration
     */
    private function registerHandlers()
    {
        if (!$this->isInitialized) {
            // Store original handlers for restoration if needed
            $this->originalErrorHandler = set_error_handler([$this, 'handleError']);
            $this->originalExceptionHandler = set_exception_handler([$this, 'handleException']);
            register_shutdown_function([$this, 'handleShutdown']);

            $this->isInitialized = true;
        }
    }

    /**
     * Restore original error handlers
     */
    public function restoreHandlers()
    {
        if ($this->isInitialized) {
            if ($this->originalErrorHandler) {
                set_error_handler($this->originalErrorHandler);
            }
            if ($this->originalExceptionHandler) {
                set_exception_handler($this->originalExceptionHandler);
            }
            $this->isInitialized = false;
        }
    }

    /**
     * Enhanced PHP error handler with better error categorization
     */
    public function handleError($severity, $message, $file, $line, $context = [])
    {
        // Don't handle suppressed errors (@-operator)
        if (!(error_reporting() & $severity)) {
            return false;
        }

        // Skip handling certain types of errors in production-like scenarios
        if (!$this->config['debug'] && !$this->isFatalError($severity)) {
            return false;
        }

        $errorData = [
            'type' => $this->getErrorType($severity),
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'severity' => $severity,
            'context' => $this->sanitizeContext($context),
            'trace' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT),
            'timestamp' => microtime(true),
            'request_id' => $this->generateRequestId()
        ];

        // Log the error if logging is enabled
        if ($this->config['log_errors']) {
            $this->logError($errorData);
        }

        $this->displayError($errorData);
        return true;
    }

    /**
     * Enhanced exception handler with better exception types
     */
    public function handleException($exception)
    {
        $errorData = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'severity' => E_ERROR,
            'context' => [],
            'trace' => $exception->getTrace(),
            'timestamp' => microtime(true),
            'request_id' => $this->generateRequestId(),
            'exception_code' => $exception->getCode(),
            'previous_exception' => $exception->getPrevious()
        ];

        // Log the exception if logging is enabled
        if ($this->config['log_errors']) {
            $this->logError($errorData);
        }

        $this->displayError($errorData);
    }

    /**
     * Enhanced shutdown handler with better fatal error detection
     */
    public function handleShutdown()
    {
        $error = error_get_last();

        if ($error !== null && $this->isFatalError($error['type'])) {
            $errorData = [
                'type' => $this->getErrorType($error['type']),
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'severity' => $error['type'],
                'context' => [],
                'trace' => [],
                'timestamp' => microtime(true),
                'request_id' => $this->generateRequestId(),
                'is_shutdown_error' => true
            ];

            $this->displayError($errorData);
        }
    }

    /**
     * Enhanced error display with context-aware output
     */
    private function displayError($errorData)
    {
        // Detect the current context and handle accordingly
        if ($this->isCommandLineInterface()) {
            $this->displayCliError($errorData);
            return;
        }

        if ($this->isApiRequest()) {
            $this->displayApiError($errorData);
            return;
        }

        // Handle AJAX requests differently  
        if ($this->isAjaxRequest() && $this->config['enable_ajax_errors']) {
            $this->displayAjaxError($errorData);
            return;
        }

        // Clear any existing output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set proper HTTP response code
        if (!headers_sent()) {
            // http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
            header('X-Error-Request-ID: ' . $errorData['request_id']);
        }

        // Get enhanced source code context
        $sourceCode = $this->getEnhancedSourceCode($errorData['file'], $errorData['line']);

        // Get formatted stack trace
        $stackTrace = $this->formatEnhancedStackTrace($errorData['trace']);

        // Get comprehensive environment info
        $envInfo = $this->getComprehensiveEnvironmentInfo();

        // Get request information
        $requestInfo = $this->config['show_request_data'] ? $this->getRequestInfo() : [];

        // Generate the enhanced error page
        $html = $this->generateEnhancedErrorHtml($errorData, $sourceCode, $stackTrace, $envInfo, $requestInfo);

        echo $html;
        exit(1);
    }

    /**
     * Handle AJAX errors with JSON response
     */
    private function displayAjaxError($errorData)
    {
        if (!headers_sent()) {
            // http_response_code(500);
            header('Content-Type: application/json');
            header('X-Error-Request-ID: ' . $errorData['request_id']);
        }

        $response = [
            'error' => true,
            'type' => $errorData['type'],
            'message' => $errorData['message'],
            'file' => basename($errorData['file']),
            'line' => $errorData['line'],
            'request_id' => $errorData['request_id'],
            'timestamp' => date('Y-m-d H:i:s', intval($errorData['timestamp']))
        ];

        // In debug mode, include more details
        if ($this->config['debug']) {
            $response['full_file_path'] = $errorData['file'];
            $response['stackTrace'] = array_slice($errorData['trace'], 0, 5); // Limit for JSON
            $response['ide_link'] = $this->generateIdeLink($errorData['file'], $errorData['line']);
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit(1);
    }

    /**
     * Handle CLI errors with formatted console output
     */
    private function displayCliError($errorData)
    {
        // ANSI color codes
        $green = "\033[32m";
        $blue = "\033[34m";
        $cyan = "\033[36m";
        $magenta = "\033[35m";
        $reset = "\033[0m";
        $bold = "\033[1m";

        // Get color based on error type
        $typeColor = $this->getErrorTypeColor($errorData['type']);
        $headerColor = $this->getErrorHeaderColor($errorData['severity']);

        echo "\n" . $headerColor . $bold . "=== PHP " . strtoupper($this->getErrorCategory($errorData['type'])) . " ===" . $reset . "\n";
        echo $typeColor . "Type: " . $reset . $bold . $errorData['type'] . $reset . "\n";
        echo $typeColor . "Message: " . $reset . $errorData['message'] . "\n";
        echo $blue . "File: " . $reset . $errorData['file'] . "\n";
        echo $blue . "Line: " . $reset . $errorData['line'] . "\n";
        echo $cyan . "Request ID: " . $reset . $errorData['request_id'] . "\n";
        echo $magenta . "Timestamp: " . $reset . date('Y-m-d H:i:s', intval($errorData['timestamp'])) . "\n";

        // Show stack trace for CLI
        if (!empty($errorData['trace'])) {
            echo "\n" . $bold . "Stack Trace:" . $reset . "\n";
            $count = 0;
            foreach ($errorData['trace'] as $frame) {
                if ($count >= 5) break; // Limit for readability

                $file = isset($frame['file']) ? $frame['file'] : 'Unknown';
                $line = isset($frame['line']) ? $frame['line'] : 0;
                $function = isset($frame['function']) ? $frame['function'] : 'Unknown';
                $class = isset($frame['class']) ? $frame['class'] : '';
                $type = isset($frame['type']) ? $frame['type'] : '';

                echo $green . "#$count " . $reset . $class . $type . $function . "\n";
                echo "    " . $file . ":" . $line . "\n";
                $count++;
            }
        }

        echo "\n" . str_repeat("=", 50) . "\n\n";
        exit(1);
    }

    /**
     * Handle API errors with JSON response
     */
    private function displayApiError($errorData)
    {
        if (!headers_sent()) {
            // http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Error-Request-ID: ' . $errorData['request_id']);
        }

        $response = [
            'success' => false,
            'error' => [
                'type' => $errorData['type'],
                'message' => $errorData['message'],
                'code' => 500,
                'request_id' => $errorData['request_id'],
                'timestamp' => date('c', intval($errorData['timestamp']))
            ]
        ];

        // In debug mode, include more details
        if ($this->config['debug']) {
            $response['error']['debug'] = [
                'file' => $errorData['file'],
                'line' => $errorData['line'],
                'stackTrace' => array_slice($errorData['trace'], 0, 3) // Limited for API
            ];
        }

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit(1);
    }

    /**
     * Enhanced source code retrieval with syntax highlighting
     */
    private function getEnhancedSourceCode($file, $line)
    {
        if (!$this->config['show_sourceCode'] || !file_exists($file)) {
            return [];
        }

        $cache_key = md5($file . filemtime($file));

        // Simple in-memory cache for this request
        static $source_cache = [];

        if ($this->config['cache_sourceCode'] && isset($source_cache[$cache_key])) {
            $lines = $source_cache[$cache_key];
        } else {
            $lines = file($file);
            if ($this->config['cache_sourceCode']) {
                $source_cache[$cache_key] = $lines;
            }
        }

        $total_lines = count($lines);
        $context_lines = $this->config['sourceCode_lines'];

        $start = max(0, $line - $context_lines - 1);
        $end = min($total_lines, $line + $context_lines);

        $source = [];
        for ($i = $start; $i < $end; $i++) {
            $code = rtrim($lines[$i]);

            // Basic syntax highlighting for PHP
            if ($this->config['highlight_syntax'] && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $code = $this->elegantHighlightSyntax($code);
            }

            $source[] = [
                'line_number' => $i + 1,
                'code' => $code,
                'is_error_line' => ($i + 1) === $line,
                'is_near_error' => abs(($i + 1) - $line) <= 2
            ];
        }

        return $source;
    }

    /**
     * Enhanced stack trace formatting with better filtering
     */
    private function formatEnhancedStackTrace($trace)
    {
        $formatted = [];
        $count = 0;

        foreach ($trace as $frame) {
            if ($count >= $this->config['max_trace_files']) {
                break;
            }

            // Skip internal error handler frames
            if (isset($frame['class']) && $frame['class'] === __CLASS__) {
                continue;
            }

            $file = isset($frame['file']) ? $frame['file'] : 'Unknown';
            $line = isset($frame['line']) ? $frame['line'] : 0;
            $function = isset($frame['function']) ? $frame['function'] : 'Unknown';
            $class = isset($frame['class']) ? $frame['class'] : '';
            $type = isset($frame['type']) ? $frame['type'] : '';
            $args = isset($frame['args']) ? $frame['args'] : [];

            $formatted[] = [
                'file' => $file,
                'line' => $line,
                'function' => $class . $type . $function,
                'class' => $class,
                'type' => $type,
                'function_name' => $function,
                'args' => $this->formatFunctionArgs($args),
                'source' => $this->getEnhancedSourceCode($file, $line),
                'is_vendor' => $this->isVendorFile($file),
                'is_framework' => $this->isFrameworkFile($file)
            ];

            $count++;
        }

        return $formatted;
    }

    /**
     * Get comprehensive environment information
     */
    private function getComprehensiveEnvironmentInfo()
    {
        $info = [
            'php_version' => PHP_VERSION,
            'ci_version' => defined('CI_VERSION') ? CI_VERSION : 'Unknown',
            'webby_version' => WEBBY_VERSION,
            'environment' => ENVIRONMENT,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'time' => date('Y-m-d H:i:s'),
            'timestamp' => time(),
            'timezone' => date_default_timezone_get(),
            'server_load' => $this->getServerLoad(),
            'disk_free_space' => $this->getDiskFreeSpace(),
            'extensions' => $this->getLoadedExtensions()
        ];

        return $info;
    }

    /**
     * Get request information for debugging
     */
    private function getRequestInfo()
    {
        $info = [
            'get' => $_GET,
            'post' => $this->sanitizeSensitiveData($_POST),
            'cookies' => $this->sanitizeSensitiveData($_COOKIE),
            'session' => $this->getSessionData(),
            'headers' => $this->getRequestHeaders(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'ip_address' => $this->getClientIp(),
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'Unknown'
        ];

        return $info;
    }

    /**
     * Generate enhanced error HTML with improved design
     */
    private function generateEnhancedErrorHtml($errorData, $sourceCode, $stackTrace, $envInfo, $requestInfo = [])
    {
        $ide_link = $this->generateIdeLink($errorData['file'], $errorData['line']);
        $theme_class = $this->config['dark_theme'] ? 'dark-theme' : 'light-theme';

        $html = '<!DOCTYPE html>
<html lang="en" class="' . $theme_class . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - ' . htmlspecialchars($errorData['type']) . '</title>
    <!--<link href="prime-css-here" rel="stylesheet">-->
    <style>
        :root {
            --bg-primary: ' . ($this->config['dark_theme'] ? '#1a1a1a' : '#ffffff') . ';
            --bg-secondary: ' . ($this->config['dark_theme'] ? '#2a2a2a' : '#f8f9fa') . ';
            --bg-tertiary: ' . ($this->config['dark_theme'] ? '#3a3a3a' : '#e9ecef') . ';
            --text-primary: ' . ($this->config['dark_theme'] ? '#ffffff' : '#212529') . ';
            --text-secondary: ' . ($this->config['dark_theme'] ? '#95a5a6' : '#6c757d') . ';
            --text-request-id: ' . ($this->config['dark_theme'] ? 'white' : 'gray') . ';
            --accent-color: #e74c3c;
            --accent-secondary: #3498db;
            --border-color: ' . ($this->config['dark_theme'] ? '#4a4a4a' : '#dee2e6') . ';
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        
        .error-header {
            background: linear-gradient(135deg, var(--accent-color), #c0392b);
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }
        
        .error-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 40%, rgba(255,255,255,0.1) 50%, transparent 60%);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .error-type { 
            font-size: 32px; 
            font-weight: 700; 
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .error-message { 
            font-size: 20px; 
            opacity: 0.95; 
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .error-location { 
            font-family: "Monaco", "Consolas", "Courier New", monospace;
            font-size: 14px; 
            opacity: 0.9; 
            background: rgba(0,0,0,0.2);
            padding: 10px 15px;
            border-radius: 6px;
            position: relative;
            z-index: 1;
        }
        
        .error-actions {
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }
        
        .ide-link, .btn {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            margin-right: 10px;
            margin-top: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .ide-link:hover, .btn:hover { 
            background: rgba(255,255,255,0.3); 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .section {
            background: var(--bg-secondary);
            border-radius: 12px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }
        
        .section-header {
            background: var(--bg-tertiary);
            padding: 20px 25px;
            font-weight: 600;
            font-size: 18px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .section-content { 
            padding: 25px; 
        }
        
        .source-code {
            font-family: "Monaco", "Consolas", "Courier New", monospace;
            font-size: 14px;
            background: var(--bg-primary);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .source-line {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s ease;
        }
        
        .source-line:last-child { border-bottom: none; }
        
        .source-line.error { 
            background: rgba(231, 76, 60, 0.15);
            border-left: 4px solid var(--accent-color);
        }
        
        .source-line.near-error {
            background: rgba(231, 76, 60, 0.05);
        }
        
        .source-line:hover {
            background: rgba(52, 152, 219, 0.1);
        }
        
        .line-number {
            width: 80px;
            text-align: right;
            padding-right: 20px;
            color: var(--text-secondary);
            user-select: none;
            font-weight: 500;
        }
        
        .line-code { 
            flex: 1; 
            padding-left: 20px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .stack-frame {
            border-bottom: 1px solid var(--border-color);
            padding: 20px 0;
            transition: background-color 0.2s ease;
        }
        
        .stack-frame:last-child { border-bottom: none; }
        
        .stack-frame:hover {
            background: rgba(52, 152, 219, 0.05);
        }
        
        .stack-function { 
            font-weight: 600; 
            color: var(--accent-secondary); 
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .stack-file { 
            font-family: "Monaco", "Consolas", "Courier New", monospace;
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }
        
        .stack-args {
            font-size: 12px;
            color: var(--text-secondary);
            background: var(--bg-primary);
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 8px;
            border: 1px solid var(--border-color);
        }
        
        .env-grid, .request-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 15px;
            font-size: 14px;
        }
        
        .env-key, .request-key { 
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .env-value, .request-value { 
            font-family: "Monaco", "Consolas", "Courier New", monospace;
            word-break: break-all;
            background: var(--bg-primary);
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
        }
        
        .tabs {
            display: flex;
            background: var(--bg-tertiary);
            border-radius: 8px 8px 0 0;
            overflow: hidden;
        }
        
        .tab {
            padding: 15px 25px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
        }
        
        .tab:hover {
            background: rgba(52, 152, 219, 0.1);
        }
        
        .tab.active {
            background: var(--bg-secondary);
            border-bottom-color: var(--accent-secondary);
            color: var(--accent-secondary);
        }
        
        .tab-content { 
            display: none; 
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active { 
            display: block; 
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-vendor {
            background: #f39c12;
            color: white;
        }
        
        .badge-framework {
            background: #9b59b6;
            color: white;
        }
        
        .badge-app {
            background: #27ae60;
            color: white;
        }
        
        .request-id {
            font-family: "Monaco", "Consolas", "Courier New", monospace;
            font-size: 12px;
            color: var(--text-request-id);
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .error-header { padding: 25px; }
            .error-type { font-size: 24px; }
            .error-message { font-size: 16px; }
            .env-grid, .request-grid { grid-template-columns: 1fr; }
            .tabs { flex-direction: column; }
            .section-content { padding: 20px; }
        }
        
        .syntax-keyword { color: #437fc9; font-weight: bold; }
        .syntax-string { color: #09bc61; }
        .syntax-comment { color: #976ade; font-style: italic; }
        .syntax-variable *,
        .syntax-variable
        { color: inherit; color: #ba5793; }
        .syntax-function { color: #c6b123; }
        .syntax-brace { color: #cbd0ae; }
        .syntax-arr { color: #96f9fe; }

    </style>
</head>
<body>
    <div class="container">
        <div class="error-header">
            <div class="error-type">' . htmlspecialchars($errorData['type']) . '</div>
            <div class="error-message">' . htmlspecialchars($errorData['message']) . '</div>
            <div class="error-location">
                ' . htmlspecialchars($this->getRelativePath($errorData['file'])) . ':' . $errorData['line'] . '
            </div>
            <div class="error-actions">
                <a href="' . htmlspecialchars($ide_link) . '" class="ide-link">
                    📝 Open in ' . ucfirst($this->config['default_ide']) . '
                </a>
                <button onclick="copyError()" class="btn">
                    📋 Copy Error Details
                </button>
            </div>
            <div class="request-id">Request ID: ' . $errorData['request_id'] . '</div>
        </div>';

        // Source Code Section
        if (!empty($sourceCode)) {
            $html .= '<div class="section">
                <div class="section-header">
                    📄 Source Code
                    <span style="font-size: 14px; font-weight: normal;">' . basename($errorData['file']) . '</span>
                </div>
                <div class="section-content">
                    <div class="source-code">
                    <pre class="overflow">';

            foreach ($sourceCode as $line) {
                $class = 'source-line';
                if ($line['is_error_line']) {
                    $class .= ' error';
                } elseif ($line['is_near_error']) {
                    $class .= ' near-error';
                }

                $html .= '<div class="' . $class . '">
                            <div class="line-number">' . $line['line_number'] . '</div>
                            <div class="line-code">' . $line['code'] . '</div>
                          </div>';
            }

            $html .= '</pre>
                    </div>
                </div>
            </div>';
        }

        // Stack Trace Section
        $html .= '<div class="section">
            <div class="section-header">🔍 Stack Trace</div>
            <div class="section-content">';

        foreach ($stackTrace as $i => $frame) {
            $badges = '';
            if ($frame['is_vendor']) {
                $badges .= '<span class="badge badge-vendor">Vendor</span> ';
            }
            if ($frame['is_framework']) {
                $badges .= '<span class="badge badge-framework">Framework</span> ';
            }
            if (!$frame['is_vendor'] && !$frame['is_framework']) {
                $badges .= '<span class="badge badge-app">App</span> ';
            }

            $file_link = $this->generateIdeLink($frame['file'], $frame['line']);
            $html .= '<div class="stack-frame">
                        <div class="stack-function">
                            #' . $i . ' ' . htmlspecialchars($frame['function']) . ' ' . $badges . '
                        </div>
                        <div class="stack-file">
                            <a href="' . htmlspecialchars($file_link) . '" style="color: var(--text-secondary); text-decoration: none;">
                                ' . htmlspecialchars($this->getRelativePath($frame['file'])) . ':' . $frame['line'] . '
                            </a>
                        </div>';

            if (!empty($frame['args'])) {
                $html .= '<div class="stack-args">Args: ' . htmlspecialchars($frame['args']) . '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>
        </div>';

        // Tabbed sections for Environment and Request data
        $html .= '<div class="section">
            <div class="tabs">
                <div class="tab active" onclick="switchTab(\'environment\')">🌍 Environment</div>';

        if (!empty($requestInfo)) {
            $html .= '<div class="tab" onclick="switchTab(\'request\')">📥 Request Data</div>';
        }

        $html .= '</div>
            
            <div class="tab-content active" id="environment-content">
                <div class="section-content">
                    <div class="env-grid">';

        foreach ($envInfo as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            if ($key === 'memory_usage' || $key === 'memory_peak') {
                $value = $this->formatBytes($value);
            }

            $html .= '<div class="env-key">' . ucwords(str_replace('_', ' ', $key)) . '</div>
                      <div class="env-value">' . htmlspecialchars($value) . '</div>';
        }

        $html .= '</div>
                </div>
            </div>';

        // Request data tab
        if (!empty($requestInfo)) {
            $html .= '<div class="tab-content" id="request-content">
                <div class="section-content">
                    <div class="request-grid">';

            foreach ($requestInfo as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT);
                }

                $html .= '<div class="request-key">' . ucwords(str_replace('_', ' ', $key)) . '</div>
                          <div class="request-value">' . htmlspecialchars($value) . '</div>';
            }

            $html .= '</div>
                </div>
            </div>';
        }

        $html .= '</div>';

        // JavaScript for interactivity
        $html .= '<script>
            function switchTab(tabName) {
                // Hide all tab contents
                document.querySelectorAll(".tab-content").forEach(content => {
                    content.classList.remove("active");
                });
                
                // Remove active class from all tabs
                document.querySelectorAll(".tab").forEach(tab => {
                    tab.classList.remove("active");
                });
                
                // Show selected tab content
                document.getElementById(tabName + "-content").classList.add("active");
                
                // Add active class to clicked tab
                event.target.classList.add("active");
            }
            
            function copyError() {
                const errorDetails = `
Error Type: ' . $errorData['type'] . '
Message: ' . $errorData['message'] . '
File: ' . $errorData['file'] . '
Line: ' . $errorData['line'] . '
Request ID: ' . $errorData['request_id'] . '
Timestamp: ' . date('Y-m-d H:i:s', intval($errorData['timestamp'])) . '
                `.trim();
                
                navigator.clipboard.writeText(errorDetails).then(() => {
                    // Show feedback
                    const btn = event.target;
                    const originalText = btn.textContent;
                    btn.textContent = "✅ Copied!";
                    btn.style.background = "rgba(39, 174, 96, 0.3)";
                    
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.style.background = "";
                    }, 2000);
                }).catch(err => {
                    console.error("Failed to copy: ", err);
                });
            }
            
            // Auto-refresh functionality for development
            if (window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1") {
                console.log("Development mode detected - Error handler active");
            }
        </script>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Simple PHP syntax highlighting
     */
    private function elegantHighlightSyntax(string $code)
    {
        $code = str_replace("\n", "", $code);
        $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8', true);

        $code = preg_replace("/(&quot;(.*)&quot;)/ui", "<span class=\"syntax-string\">$1</span>", $code);
        $code = preg_replace("/(&#039;(.*)&#039;)/ui", "<span class=\"syntax-string\">$1</span>", $code);

        $code = preg_replace("/(if|for|switch|elseif|while|foreach)(\s*\()/ui", "<span class=\"syntax-keyword\">$1</span>$2", $code);
        $code = preg_replace("/((function|public|class|private|const|use|namespace|throw|new|require_once|require|include|include_once)\s+)/ui", "<span class=\"syntax-keyword\">$1</span>", $code);
        $code = preg_replace("/((null|true|false|else|continue|break|self::|self|static::|static|\$this)\s*)/ui", "<span class=\"syntax-keyword\">$1</span>", $code);
        $code = preg_replace("/((echo|return|extends|implements|protected)\s+)/ui", "<span class=\"syntax-keyword\">$1</span>", $code);

        $code = preg_replace("/([a-z_]+[a-z_0-9]*\s*)(\()/ui", "<span class=\"syntax-function\">$1</span>$2", $code);
        $code = preg_replace("/([a-z_]+\s*)(\()/ui", "<span class=\"syntax-function\">$1</span>$2", $code);

        $code = preg_replace("/(-&gt;)([a-z]+[a-z0-9_]*)/ui", "$1<span class=\"syntax-variable\">$2</span>", $code);
        $code = preg_replace("/(\\$([a-z_]+[a-z0-9_]*))/ui", "<span class=\"syntax-variable\">$1</span>", $code);

        $code = preg_replace("/(\)|\(|\}|\{)/ui", "<span class=\"syntax-brace\">$1</span>", $code);
        $code = preg_replace("/(\]|\[)/ui", "<span class=\"syntax-arr\">$1</span>", $code);

        $code = preg_replace("/((\/|\s)\*+(.*))/ui", "<span class=\"syntax-comment\">$1</span>", $code);
        $code = preg_replace("/^(\*+(.*))/ui", "<span class=\"syntax-comment\">$1</span>", $code);
        $code = preg_replace("/(\/\/(.*)$)/ui", "<span class=\"syntax-comment\">$1</span>", $code);

        return $code;
    }

    /**
     * Alternate PHP syntax highlighting
     */
    private function highlightPhpSyntax($code)
    {
        // Basic PHP keyword highlighting
        $keywords = ['public', 'private', 'protected', 'function', 'class', 'if', 'else', 'elseif', 'return', 'new', 'var', 'echo', 'print'];

        foreach ($keywords as $keyword) {
            $code = preg_replace('/\b' . $keyword . '\b/', '<span class="syntax-keyword">' . $keyword . '</span>', $code);
        }

        // Highlight strings
        $code = preg_replace('/(\'[^\']*\'|"[^"]*")/', '<span class="syntax-string">$1</span>', $code);

        // Highlight variables
        $code = preg_replace('/(\$\w+)/', '<span class="syntax-variable">$1</span>', $code);

        // Highlight comments
        $code = preg_replace('/(\/\/.*$|\/\*.*?\*\/)/m', '<span class="syntax-comment">$1</span>', $code);

        return $code;
    }

    /**
     * Format function arguments for display
     */
    private function formatFunctionArgs($args)
    {
        if (empty($args)) {
            return '';
        }

        $formatted = [];
        foreach ($args as $arg) {
            if (is_object($arg)) {
                $formatted[] = get_class($arg) . ' Object';
            } elseif (is_array($arg)) {
                $formatted[] = 'Array(' . count($arg) . ')';
            } elseif (is_string($arg)) {
                $formatted[] = '"' . substr($arg, 0, 50) . (strlen($arg) > 50 ? '...' : '') . '"';
            } elseif (is_bool($arg)) {
                $formatted[] = $arg ? 'true' : 'false';
            } elseif (is_null($arg)) {
                $formatted[] = 'null';
            } else {
                $formatted[] = (string) $arg;
            }
        }

        return implode(', ', $formatted);
    }

    /**
     * Check if error is fatal
     */
    private function isFatalError($type)
    {
        return in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR]);
    }

    /**
     * Check if running in command line interface
     */
    private function isCommandLineInterface()
    {
        return php_sapi_name() === 'cli' || defined('STDIN') ||
            (defined('PHP_SAPI') && PHP_SAPI === 'cli');
    }

    /**
     * Check if request is API request
     */
    private function isApiRequest()
    {
        // Check for common API indicators
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        // Check for JSON content type
        if (stripos($contentType, 'application/json') !== false) {
            return true;
        }

        // Check for JSON in Accept header
        if (stripos($acceptHeader, 'application/json') !== false) {
            return true;
        }

        // Check for common API URL patterns
        if (preg_match('/\/(api|rest|webservice)\//i', $requestUri)) {
            return true;
        }

        // Check for API version in URL
        if (preg_match('/\/v\d+\//i', $requestUri)) {
            return true;
        }

        return false;
    }

    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if file is in vendor directory
     */
    private function isVendorFile($file)
    {
        return strpos($file, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false ||
            strpos($file, DIRECTORY_SEPARATOR . 'third_party' . DIRECTORY_SEPARATOR) !== false;
    }

    /**
     * Check if file is framework file
     */
    private function isFrameworkFile($file)
    {
        return strpos($file, FCPATH . 'system' . DIRECTORY_SEPARATOR) !== false ||
            strpos($file, realpath(BASEPATH)) !== false;
    }

    /**
     * Get relative path for cleaner display
     */
    private function getRelativePath($file)
    {
        $basePath = realpath(FCPATH);
        $realFile = realpath($file);

        if ($realFile && strpos($realFile, $basePath) === 0) {
            return ltrim(substr($realFile, strlen($basePath)), DIRECTORY_SEPARATOR);
        }

        return $file;
    }

    /**
     * Generate unique request ID
     */
    private function generateRequestId()
    {
        return substr(md5(uniqid(mt_rand(), true)), 0, 8);
    }

    /**
     * Sanitize context data
     */
    private function sanitizeContext($context)
    {
        if (!is_array($context)) {
            return [];
        }

        // Remove sensitive data and limit size
        $sanitized = [];
        $maxItems = 10;
        $count = 0;

        foreach ($context as $key => $value) {
            if ($count >= $maxItems) break;

            if (is_object($value)) {
                $sanitized[$key] = get_class($value) . ' Object';
            } elseif (is_array($value)) {
                $sanitized[$key] = 'Array(' . count($value) . ')';
            } elseif (is_resource($value)) {
                $sanitized[$key] = 'Resource';
            } else {
                $sanitized[$key] = (string) $value;
            }
            $count++;
        }

        return $sanitized;
    }

    /**
     * Sanitize sensitive data from request arrays
     */
    private function sanitizeSensitiveData($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $sensitive_keys = ['password', 'pass', 'pwd', 'secret', 'token', 'key', 'auth'];
        $sanitized = $data;

        foreach ($sanitized as $key => $value) {
            foreach ($sensitive_keys as $sensitive) {
                if (stripos($key, $sensitive) !== false) {
                    $sanitized[$key] = '[HIDDEN]';
                    break;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Get session data safely
     */
    private function getSessionData()
    {
        try {
            if (isset($this->CI->session)) {
                $session_data = $this->CI->session->userdata();
                return $this->sanitizeSensitiveData($session_data);
            }
        } catch (Exception $e) {
            // Session might not be initialized
        }

        return [];
    }

    /**
     * Get request headers
     */
    private function getRequestHeaders()
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            // Fallback for servers that don't support getallheaders()
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $header = str_replace('_', '-', substr($key, 5));
                    $headers[$header] = $value;
                }
            }
        }

        return $this->sanitizeSensitiveData($headers);
    }

    /**
     * Get client IP address
     */
    private function getClientIp()
    {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }

    /**
     * Get server load if available
     */
    private function getServerLoad()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return implode(', ', array_map(function ($val) {
                return round($val, 2);
            }, $load));
        }

        return 'N/A';
    }

    /**
     * Get disk free space
     */
    private function getDiskFreeSpace()
    {
        $bytes = disk_free_space(FCPATH);
        return $bytes ? $this->formatBytes($bytes) : 'N/A';
    }

    /**
     * Get loaded PHP extensions
     */
    private function getLoadedExtensions()
    {
        $extensions = get_loaded_extensions();
        return array_slice($extensions, 0, 20); // Limit to avoid overwhelming
    }

    /**
     * Log error to file
     */
    private function logError($errorData)
    {
        $logMessage = sprintf(
            "%s: %s in %s:%d (Request ID: %s)",
            $errorData['type'],
            $errorData['message'],
            $errorData['file'],
            $errorData['line'],
            $errorData['request_id']
        );

        // Use CodeIgniter's logging if available
        if (function_exists('log_message')) {
            log_message('error', $logMessage);
        } else {
            // Fallback to error_log
            error_log($logMessage);
        }
    }

    /**
     * Generate IDE link for opening files
     */
    private function generateIdeLink($file, $line)
    {
        $ide = $this->config['default_ide'];

        if (!isset($this->config['ide_links'][$ide])) {
            return '#';
        }

        $template = $this->config['ide_links'][$ide];
        return str_replace(['{file}', '{line}'], [$file, $line], $template);
    }

    /**
     * Get error type name from error number
     */
    private function getErrorType($type)
    {
        $types = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            2048 => 'Strict Standards',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];

        return $types[$type] ?? 'Unknown Error';
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes)
    {
        if ($bytes === 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get color for error type based on severity
     */
    private function getErrorTypeColor($errorType)
    {
        // ANSI color codes
        $red = "\033[31m";        // Fatal errors
        $orange = "\033[38;5;208m"; // Warnings  
        $yellow = "\033[33m";     // Notices/Deprecated
        $blue = "\033[34m";       // Strict standards
        $magenta = "\033[35m";    // Parse/Compile errors

        $normalizedType = strtolower($errorType);

        if (
            strpos($normalizedType, 'fatal') !== false ||
            strpos($normalizedType, 'error') !== false
        ) {
            return $red;
        }

        if (strpos($normalizedType, 'warning') !== false) {
            return $orange;
        }

        if (
            strpos($normalizedType, 'notice') !== false ||
            strpos($normalizedType, 'deprecated') !== false
        ) {
            return $yellow;
        }

        if (strpos($normalizedType, 'strict') !== false) {
            return $blue;
        }

        if (
            strpos($normalizedType, 'parse') !== false ||
            strpos($normalizedType, 'compile') !== false
        ) {
            return $magenta;
        }

        return $red; // Default to red for unknown error types
    }

    /**
     * Get header color based on error severity level
     */
    private function getErrorHeaderColor($severity)
    {
        $red = "\033[31m";        // Fatal errors
        $orange = "\033[38;5;208m"; // Warnings
        $yellow = "\033[33m";     // Notices
        $blue = "\033[34m";       // Strict standards
        $magenta = "\033[35m";    // Parse/Compile errors

        switch ($severity) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                return $red;

            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return $orange;

            case E_NOTICE:
            case E_USER_NOTICE:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return $yellow;

            case 2048: // E_STRICT
                return $blue;

            case E_PARSE:
                return $magenta;

            default:
                return $red;
        }
    }

    /**
     * Get error category for display
     */
    private function getErrorCategory($errorType)
    {
        $normalizedType = strtolower($errorType);

        if (
            strpos($normalizedType, 'fatal') !== false ||
            strpos($normalizedType, 'error') !== false
        ) {
            return 'ERROR';
        }

        if (strpos($normalizedType, 'warning') !== false) {
            return 'WARNING';
        }

        if (strpos($normalizedType, 'notice') !== false) {
            return 'NOTICE';
        }

        if (strpos($normalizedType, 'deprecated') !== false) {
            return 'DEPRECATED';
        }

        if (strpos($normalizedType, 'strict') !== false) {
            return 'STRICT';
        }

        return 'ERROR'; // Default
    }
}

<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

defined('COREPATH') or exit('No direct script access allowed');

/**
 *  Debug Helper functions
 *
 *  @package		Webby
 *	@subpackage		Helpers
 *	@category		Helpers
 *	@author			Kwame Oteng Appiah-Nti
 */

use Base\Debug\Error;
use Base\Debug\DumpFormatter;

if (! function_exists(__NAMESPACE__ . '\\startSuppression')) {

    if (! function_exists('shut_up')) {
        /**
         * Start error suppresion
         */
        function shut_up()
        {
            Error::startSuppression();
        }
    }

    if (! function_exists('shush')) {
        /**
         * Alias of shut_up function
         */
        function shush()
        {
            shut_up();
        }
    }


    if (! function_exists('speak_up')) {
        /**
         * Stop error suppression
         */
        function speak_up()
        {
            Error::stopSuppression();
        }
    }

    if (! function_exists('keep_quiet')) {
        /**
         * Call the callback given by the first parameter, suppressing any warnings.
         *
         * @param callable $callback Function to call
         * @param mixed ...$args Optional arguments for the function call
         * @return mixed
         */
        function keep_quiet(callable $callback, ...$args)
        {
            return Error::silenced($callback, ...$args);
        }
    }
}

if (! function_exists('strict_dev')) {
    /**
     * Check if development 
     * environment is active
     *
     * @return void
     */
    function strict_dev()
    {
        if (ENVIRONMENT !== 'development') {
            show_error('Sorry you must be in development mode');
        }
    }
}

if (! function_exists('console')) {
    /**
     * Show output in Browser Console
     *
     * @param mixed $var converted to json
     * @param string $type - browser console log types [log]
     * @return void
     */
    function console(/* mixed */$var, string $type = 'log')
    {
        strict_dev();
        echo '<script type="text/javascript">console.';
        echo '' . $type . '';
        echo '(' . json_encode($var) . ')</script>';
    }
}

if (! function_exists('dumper')) {
    /**
     * Simple debug output with 
     * var_dump() function
     *
     * @param mixed $dump
     * @return void
     */
    function dumper($dump)
    {
        strict_dev();
        echo '<pre>';
        var_dump($dump);
        echo '</pre>';
    }
}


if (! function_exists('dr')) {
    /**
     * 
     * Dump response for APIs
     * 
     * Debug output for APIs
     * 
     * @param mixed $dump
     * @return void
     */
    function dr()
    {
        $dump = func_get_args();

        strict_dev();
        header('Content-Type: application/json');
        echo json_encode($dump);
        exit;
    }
}

if (! function_exists('ddd')) {
    /**
     * Dump Pretty Print debug output
     * with line of called file
     * 
     * @param mixed $dump
     * @return void
     */
    function ddd(...$vars)
    {
        strict_dev();

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $file = str_replace('\\', '/', $backtrace[0]['file'] ?? 'Unknown File');
        $rootpath = str_replace('\\', '/', ROOTPATH);
        $relative_path = str_replace($rootpath, '', $file);

        if (strpos($relative_path, '/') === 0) {
            $relative_path = ltrim($relative_path, '/');
        }

        $line = $backtrace[0]['line'] ?? 'Unknown Line';
        $location = htmlspecialchars($relative_path) . ':' . htmlspecialchars($line);

        // Get variable names from source code
        $variable_names = [];
        if (isset($backtrace[0]['file']) && isset($backtrace[0]['line'])) {
            $source = file($backtrace[0]['file']);
            if (isset($source[$backtrace[0]['line'] - 1])) {
                $call_line = $source[$backtrace[0]['line'] - 1];
                // Extract all arguments inside pp()
                if (preg_match('/pp\s*\((.*)\)\s*;?/', $call_line, $matches)) {
                    $args = $matches[1];
                    // Split by comma but not inside parentheses or brackets
                    $variable_names = preg_split('/,(?![^(\[]*[\)\]])/', $args);
                    $variable_names = array_map('trim', $variable_names);
                }
            }
        }

        // If we couldn't extract names, create default ones
        if (count($variable_names) !== count($vars)) {
            $variable_names = array_map(function ($i) {
                return "arg" . ($i + 1);
            }, array_keys($vars));
        }

        echo '
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #18171B;
            color: #E1E1E1;
            font-family: "SF Mono", Monaco, "Inconsolata", "Fira Mono", "Droid Sans Mono", "Source Code Pro", monospace;
            font-size: 13px;
            line-height: 1.5;
            padding: 20px;
        }
        .pp-header {
            background: #1E1E1E;
            border-left: 4px solid #F57F17;
            border-radius: 4px 4px 0 0;
            padding: 12px 20px;
            margin: 20px 0 0 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .pp-location {
            color: #999;
            font-size: 12px;
        }
        .pp-location::before {
            content: " "; // locate
        }
        .pp-container {
            background: #1E1E1E;
            border-left: 4px solid #F57F17;
            border-radius: 0 0 4px 4px;
            padding: 0 20px 15px 20px;
            margin: 0 0 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .pp-dump {
            margin: 15px 0;
            padding: 15px;
            background: #252526;
            border-radius: 4px;
            border-left: 3px solid #3794FF;
        }
        .pp-variable {
            color: #4FC1FF;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .pp-type {
            color: #4EC9B0;
            font-style: italic;
            font-size: 11px;
            margin-left: 8px;
        }
        .pp-content {
            color: #D4D4D4;
            white-space: pre-wrap;
            word-wrap: break-word;
            padding-left: 10px;
        }
        .pp-string { color: #CE9178; }
        .pp-number { color: #B5CEA8; }
        .pp-keyword { color: #569CD6; }
        .pp-null { color: #569CD6; font-style: italic; }
        .pp-bool { color: #569CD6; }
        .pp-object { color: #4EC9B0; font-weight: bold; }
        .pp-property { color: #9CDCFE; }
        .pp-operator { color: #D4D4D4; }
        .pp-array-key { color: #CE9178; }
        .pp-visibility { color: #C586C0; font-size: 11px; }
        .pp-indent { display: inline-block; width: 20px; }
        .pp-array-bracket { color: #FFD700; font-weight: bold; }
        .pp-count { color: #858585; font-size: 11px; margin-left: 5px; }
    </style>
    ';

        echo '<div class="pp-header">';
        echo '<div class="pp-location">' . $location . '</div>';
        echo '</div>';
        echo '<div class="pp-container">';

        foreach ($vars as $index => $var) {
            $varName = $variable_names[$index] ?? "arg" . ($index + 1);
            $type = gettype($var);
            $typeDisplay = DumpFormatter::getTypeDisplay($var);

            echo '<div class="pp-dump">';
            echo '<div class="pp-variable">' . htmlspecialchars($varName) .
                '<span class="pp-type">' . $typeDisplay . '</span></div>';
            echo '<div class="pp-content">' . DumpFormatter::format($var, 0) . '</div>';
            echo '</div>';
        }

        echo '</div>';
        die();
    }
}

if (! function_exists('dump_json')) {
    /**
     * Debug json output 
     * Useful when using ajax requests
     * 
     * @param mixed $dump
     * @return mixed
     */
    function dump_json($dump)
    {
        strict_dev();
        return json_encode($dump);
    }
}

if (! function_exists('start_profiler')) {
    /**
     * Enable Profiler
     *
     * @return void
     */
    function start_profiler()
    {
        strict_dev();
        ci()->output->enable_profiler(true);
    }
}

if (! function_exists('stop_profiler')) {
    /**
     * Disable Profiler
     *
     * @return void
     */
    function stop_profiler()
    {
        strict_dev();
        ci()->output->enable_profiler(false);
    }
}

if (! function_exists('section_profiler')) {
    /**
     * Set Profiler Sections
     *
     * Allows override of default/config settings for
     * Profiler section display.
     *
     * @param   array   $sections   Profiler sections
     * @return  void
     */
    function section_profiler($config = null)
    {
        strict_dev();

        $sections = [
            'config'  => true,
            'queries' => true
        ];

        if ($config !== null && is_array($config)) {
            $sections = $config;
        }

        ci()->output->set_profiler_sections($sections);
    }
}

if (! function_exists('start_benchmark')) {
    /**
     * Set and start a benchmark marker
     *
     * @param string $start_key Marker name
     * @return void
     */
    function start_benchmark($start_key = 'start')
    {
        ci()->benchmark->mark($start_key);
    }
}

if (! function_exists('end_benchmark')) {
    /**
     * Set and end a benchmark marker
     *
     * @param string $end_key Marker name
     * @return void
     */
    function end_benchmark($end_key = 'end')
    {
        ci()->benchmark->mark($end_key);
    }
}

if (! function_exists('show_time_elasped')) {
    /**
     * Calculates the time difference 
     * between two marked points.
     *
     * @param string $start_key
     * @param string $end_key
     * @return mixed
     */
    function show_time_elasped($start_key = 'start', $end_key = 'end')
    {
        return ci()->benchmark->elapsed_time($start_key, $end_key) . ' ';
    }
}

if (! function_exists('time_used')) {
    /**
     * Show time elasped
     *
     * @return void
     */
    function time_used()
    {
        echo "{elapsed_time}";
    }
}

if (! function_exists('memory_used')) {
    /**
     * Show memory used
     *
     * @return void
     */
    function memory_used()
    {
        echo "{memory_usage}";
    }
}

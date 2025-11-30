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

class Base_Profiler extends \CI_Profiler
{


    protected $_compile_;

    protected $_ci_cached_vars;

    protected $_available_sections = [
        'benchmarks',
        'get',
        'memory_usage',
        'post',
        'uri_string',
        'controller_info',
        'queries',
        'eloquent',
        'http_headers',
        'config',
        'files',
        'console',
        'userdata',
        'view_data'
    ];

    protected $_sections = []; // Stores _compile_x() results

    protected $_query_toggle_count  = 25;

    // --------------------------------------------------------------------

    public function __construct($config = [])
    {
        ci()->use->language('profiler');

        // If the config file has a query_toggle_count,
        // use it, but remove it from the config array.
        if (isset($config['query_toggle_count'])) {
            $this->_query_toggle_count = (int) $config['query_toggle_count'];
            unset($config['query_toggle_count']);
        }

        // Make sure the Console is loaded.
        if (!class_exists('ConsoleProfiler')) {
            ci()->use->library('ConsoleProfiler');
        }

        $this->set_sections($config);
        // default all sections to display
        foreach ($this->_available_sections as $section) {
            if (!isset($config[$section])) {
                $this->{'_compile_'}[$section] = true;
            }
        }

        // Strange hack to get access to the current
        // vars in the CI_Loader class.
        $this->_ci_cached_vars = ci()->use->_ci_cached_vars;
    }

    // --------------------------------------------------------------------

    /**
     * Set Sections
     *
     * Sets the private _compile_* properties to enable/disable Profiler sections
     *
     * @param	mixed
     * @return	void
     */
    public function set_sections($config)
    {
        foreach ($config as $method => $enable) {
            if (in_array($method, $this->_available_sections)) {
                $this->{'_compile_'}[$method] = ($enable !== false) ? true : false;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Auto Profiler
     *
     * This function cycles through the entire array of mark points and
     * matches any two points that are named identically (ending in "_start"
     * and "_end" respectively).  It then compiles the execution times for
     * all points and returns it as an array
     *
     * @return	string|array
     */
    protected function _compile_benchmarks()
    {
        $profile = [];
        $output = [];

        foreach (ci()->benchmark->marker as $key => $val) {
            // We match the "end" marker so that the list ends
            // up in the order that it was defined
            if (preg_match("/(.+?)_end/i", $key, $match)) {
                if (isset(ci()->benchmark->marker[$match[1] . '_end']) and isset(ci()->benchmark->marker[$match[1] . '_start'])) {
                    $profile[$match[1]] = ci()->benchmark->elapsed_time($match[1] . '_start', $key);
                }
            }
        }

        // Build a table containing the profile data.
        // Note: At some point we might want to make this data available to be logged.
        foreach ($profile as $key => $val) {
            $key = ucwords(str_replace(['_', '-'], ' ', $key));
            $output[$key] = $val;
        }

        unset($profile);

        return $output;
    }

    // --------------------------------------------------------------------

    /**
     * Compile Queries
     *
     * @return	string
     */
    protected function _compile_queries()
    {
        $dbs = [];
        $output = [];

        // Let's determine which databases are currently connected to
        foreach (get_object_vars(ci()) as $name => $cobject) {
            if ($cobject) {
                if ($cobject instanceof CI_DB) {
                    $dbs[$name] = $cobject;
                } elseif ($cobject instanceof CI_Model) {
                    foreach (get_object_vars($cobject) as $mname => $mobject) {
                        if ($mobject instanceof CI_DB) {
                            $dbs[$mname] = $mobject;
                        }
                    }
                }
            }
        }

        if (count($dbs) == 0) {
            return ci()->lang->line('profiler_no_db'); // to get db access must be public instance
        }

        // Load the text helper so we can highlight the SQL
        ci()->use->helper('text');

        // Key words we want bolded
        $highlight = ['SELECT', 'DISTINCT', 'FROM', 'WHERE', 'AND', 'LEFT&nbsp;JOIN', 'ORDER&nbsp;BY', 'GROUP&nbsp;BY', 'LIMIT', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'OR&nbsp;', 'HAVING', 'OFFSET', 'NOT&nbsp;IN', 'IN', 'LIKE', 'NOT&nbsp;LIKE', 'COUNT', 'MAX', 'MIN', 'ON', 'AS', 'AVG', 'SUM', '(', ')'];


        $total = 0; // total query time
        foreach ($dbs as $controler => $db) {

            foreach ($db->queries as $key => $val) {
                $time = number_format($db->query_times[$key], 4);
                $total += $db->query_times[$key];

                foreach ($highlight as $bold) {
                    $val = str_replace($bold, '<b>' . $bold . '</b>', $val);
                }

                $output[][$time] = '/*(' . $controler . ',Scheme:' . $db->database . ')*/ ' . $val; // it show in comment for SQL copy where was executed the query to get more easy in big projects where is the issue, if some filters where apply
            }
        }

        if (count($output) == 0) {
            $output = ci()->lang->line('profiler_no_queries');
        } else {
            $total = number_format($total, 4);
            $output[][$total] = 'Total Query Execution Time';
        }

        return $output;
    }


    // --------------------------------------------------------------------

    /**
     * Compile Eloquent Queries
     *
     * @return	string
     */
    protected function _compile_eloquent()
    {
        $output = [];

        // hack to make eloquent not throw error for now
        // but checks if file actually exists, or WebbyPHP will throw an error
        if (file_exists(filename: APPROOT . '/Models/Eloquent/Action.php')) {
            ci()->use->model('Eloquent/Action');
        }

        if (! class_exists('Illuminate\Database\Capsule\Manager', false)) {
            $output = 'Illuminate\Database has not been loaded.';
        } else {
            // Load the text helper so we can highlight the SQL
            ci()->use->helper('text');

            // Key words we want bolded
            $highlight = ['SELECT', 'DISTINCT', 'FROM', 'WHERE', 'AND', 'LEFT&nbsp;JOIN', 'ORDER&nbsp;BY', 'GROUP&nbsp;BY', 'LIMIT', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'OR&nbsp;', 'HAVING', 'OFFSET', 'NOT&nbsp;IN', 'IN', 'LIKE', 'NOT&nbsp;LIKE', 'COUNT', 'MAX', 'MIN', 'ON', 'AS', 'AVG', 'SUM', '(', ')'];


            $total = 0; // total query time
            shush();
            $queries = Illuminate\Database\Capsule\Manager::getQueryLog();
            speak_up();

            foreach ($queries as $q) {
                $time = number_format($q['time'] / 1000, 4);
                $total += $q['time'] / 1000;

                $query = $this->interpolateQuery($q['query'], $q['bindings']);
                foreach ($highlight as $bold)
                    $query = str_ireplace($bold, '<b>' . $bold . '</b>', $query);

                $output[][$time] = $query;
            }

            if (count($output) == 0) {
                $output = ci()->lang->line('profiler_no_queries');
            } else {
                $total = number_format($total, 4);
                $output[][$total] = 'Total Query Execution Time';
            }
        }

        return $output;
    }

    public function interpolateQuery($query, array $params)
    {
        $keys = [];
        $values = $params;

        //build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = "/:" . $key . "/";
            } else {
                $keys[] = '/[?]/';
            }

            if (is_string($value))
                $values[$key] = "'" . $value . "'";

            if (is_array($value))
                $values[$key] = implode(',', $value);

            if (is_null($value))
                $values[$key] = 'NULL';
        }

        $query = preg_replace($keys, $values, $query, 1, $count);

        return $query;
    }


    // --------------------------------------------------------------------

    /**
     * Compile $_GET Data
     *
     * @return	string
     */
    protected function _compile_get()
    {
        $output = [];

        $get = ci()->input->get();

        if (!is_array($get)) {
            if ($get === false or $get == null) {
                $output = ci()->lang->line('profiler_no_get');
            }
        } else {
            foreach ($get as $key => $val) {
                if (is_array($val)) {
                    $output[$key] = "<pre>" . htmlspecialchars(stripslashes(print_r($val, true))) . "</pre>";
                } else {
                    $output[$key] = htmlspecialchars(stripslashes($val));
                }
            }
        }

        return $output;
    }

    // --------------------------------------------------------------------

    /**
     * Compile $_POST Data
     *
     * @return	string
     */
    protected function _compile_post()
    {
        $output = [];

        if (!is_array($_POST)) return $output = ci()->lang->line('profiler_no_post');

        if (count($_POST) == 0 and $_FILES == 0) {
            $output = ci()->lang->line('profiler_no_post');
        } else {
            foreach ($_POST as $key => $val) {
                if (!is_numeric($key)) {
                    $key = "'" . $key . "'";
                }

                if (is_array($val)) {
                    $output['&#36;_POST[' . $key . ']'] = '<pre>' . htmlspecialchars(stripslashes(print_r($val, true))) . '</pre>';
                } else {
                    $output['&#36;_POST[' . $key . ']'] = htmlspecialchars(stripslashes($val));
                }
            }
        }

        return $output;
    }

    // --------------------------------------------------------------------

    /**
     * Show query string
     *
     * @return	string
     */
    protected function _compile_uri_string()
    {
        if (ci()->uri->uri_string == '') {
            $output = ci()->lang->line('profiler_no_uri');
        } else {
            $output = ci()->uri->uri_string;
        }

        return $output;
    }

    // --------------------------------------------------------------------

    /**
     * Show the controller and function that were called
     *
     * @return	string
     */
    protected function _compile_controller_info()
    {
        $output = ci()->router->class . "/" . ci()->router->method;

        return $output;
    }

    // --------------------------------------------------------------------

    /**
     * Compile memory usage
     *
     * Display total used memory
     *
     * @return	string
     */
    protected function _compile_memory_usage()
    {
        if (function_exists('memory_get_usage') && ($usage = memory_get_usage()) != '') {
            $output = number_format($usage) . ' bytes';
        } else {
            $output = ci()->lang->line('profiler_no_memory_usage');
        }

        return $output;
    }

    // --------------------------------------------------------------------

    /**
     * Compile header information
     *
     * Lists HTTP headers
     *
     * @return	string
     */
    protected function _compile_http_headers()
    {
        $output = [];

        foreach (['HTTP_ACCEPT', 'HTTP_USER_AGENT', 'HTTP_CONNECTION', 'SERVER_PORT', 'SERVER_NAME', 'REMOTE_ADDR', 'SERVER_SOFTWARE', 'HTTP_ACCEPT_LANGUAGE', 'SCRIPT_NAME', 'REQUEST_METHOD', ' HTTP_HOST', 'REMOTE_HOST', 'CONTENT_TYPE', 'SERVER_PROTOCOL', 'QUERY_STRING', 'HTTP_ACCEPT_ENCODING', 'HTTP_X_FORWARDED_FOR'] as $header) {
            $val = (isset($_SERVER[$header])) ? $_SERVER[$header] : '';
            $output[$header] =  $val;
        }

        return $output;
    }

    // --------------------------------------------------------------------

    /**
     * Compile config information
     *
     * Lists developer config variables
     *
     * @return	string
     */
    protected function _compile_config()
    {
        $output = [];

        foreach (ci()->config->config as $config => $val) {
            if (is_array($val)) {
                $val = print_r($val, true);
            }

            $output[$config] = htmlspecialchars($val);
        }

        return $output;
    }

    // --------------------------------------------------------------------

    public function _compile_files()
    {
        $files = get_included_files();

        sort($files);

        return $files;
    }

    //--------------------------------------------------------------------

    public function _compile_console()
    {
        $logs = ConsoleProfiler::get_logs();

        if ($logs['console']) {
            foreach ($logs['console'] as $key => $log) {
                if ($log['type'] == 'log') {
                    $logs['console'][$key]['data'] = print_r($log['data'], true);
                } elseif ($log['type'] == 'memory') {
                    $logs['console'][$key]['data'] = $this->get_file_size($log['data']);
                }
            }
        }

        return $logs;
    }

    //--------------------------------------------------------------------

    public function _compile_userdata()
    {
        $output = [];

        if (false !== ci()->use->is_loaded('session')) {

            $compiled_userdata = ci()->session->all_userdata();

            if (!is_array($compiled_userdata)) return $output;

            if (count($compiled_userdata)) {
                foreach ($compiled_userdata as $key => $val) {
                    if (is_numeric($key)) {
                        $output[$key] = print_r($val, true);
                    }

                    if (is_array($val) || is_object($val)) {
                        if (is_object($val))
                            $output[$key] = json_decode(json_encode($val), true);
                        else
                            $output[$key] = htmlspecialchars(stripslashes(print_r($val, true)));
                    } else {
                        $output[$key] = htmlspecialchars(stripslashes(print_r($val, true)));
                    }
                }
            }
        }

        return $output;
    }

    //--------------------------------------------------------------------

    /**
     * Compile View Data
     *
     * Allows any data passed to views to be available in the profiler bar.
     *
     * @return array
     */
    public function _compile_view_data()
    {
        $output = [];

        foreach ($this->{'_ci_cached_vars'} as $key => $val) {
            if (is_numeric($key)) {
                $output[$key] = "'$val'";
            }

            if (is_array($val) || is_object($val)) {
                $output[$key] = '<pre>' . htmlspecialchars(stripslashes(print_r($val, true))) . '</pre>';
            } else {
                $output[$key] = htmlspecialchars(stripslashes($val));
            }
        }

        return $output;
    }

    //--------------------------------------------------------------------

    public static function get_file_size($size, $retstring = null)
    {
        // adapted from code at http://aidanlister.com/repos/v/function.size_readable.php
        $sizes = ['bytes', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        if ($retstring === null) {
            $retstring = '%01.2f %s';
        }

        $lastsizestring = end($sizes);

        foreach ($sizes as $sizestring) {
            if ($size < 1024) {
                break;
            }
            if ($sizestring != $lastsizestring) {
                $size /= 1024;
            }
        }

        if ($sizestring == $sizes[0]) {
            $retstring = '%01d %s';
        } // Bytes aren't normally fractional
        return sprintf($retstring, $size, $sizestring);
    }

    //--------------------------------------------------------------------

    /**
     * Run the Profiler
     *
     * @return	string
     */
    public function run()
    {
        ci()->use->helper('language');

        $fields_displayed = 0;

        foreach ($this->_available_sections as $section) {
            if ($this->{'_compile_'}[$section] !== false) {
                $func = "_compile_{$section}";
                if ($section == 'http_headers') $section = 'headers';
                $this->_sections[$section] = $this->{$func}();
                $fields_displayed++;
            }
        }

        $profiler_view = 'errors/profiling/' . config_item('profiler_view') . PHPEXT ?: 'errors/profiling/' . 'default-view.php';

        return ci()->use->view($profiler_view, [
            'sections' => $this->_sections,
            'config' =>  [
                'benchmarks' => true,
                'get' => true,
                'memory_usage' => true,
                'post' => true,
                'uri_string' => true,
                'controller_info' => true,
                'queries' => true,
                'eloquent' => true,
                'http_headers' => true,
                'config' => true,
                'files' => true,
                'console' => true,
                'userdata' => true,
                'view_data' => true,
                'session_data' => true
            ],
            /**
             * The location of the profiler bar. Valid locations are:
             * bottom-left
             * bottom-right
             * top-left
             * top-right
             * bottom
             * top
             */
            'profiler_bar_location' => config_item('profiler_bar_location') ?: 'bottom'
        ], true);
    }
}

<?php
defined('COREPATH') or exit('No direct script access allowed');

/**
 *  CI_CORE Helper functions
 *
 *  @package        Webby
 *  @subpackage     Helpers
 *  @category       Helpers
 *  @author         Kwame Oteng Appiah-Nti
 */

use Base\Route\Route;

/* ------------------------------- Uri Functions ---------------------------------*/

if (! function_exists('app_url')) {
    /**
     * alias of base_url.
     *
     * @param string $uri
     * @param bool $protocol
     * @return string
     */
    function app_url($uri = '', $protocol = null)
    {
        return base_url($uri, $protocol);
    }
}

if (! function_exists('url')) {
    /**
     * Improved alias of site_url
     *
     * @param string|array $uri
     * @param bool $protocol
     * @return mixed
     */
    function url($uri = '', $param = '', $protocol = null)
    {
        $uri = ltrim($uri, '/');

        if ($uri === 'void') {
            return void_url();
        }

        // Detect if the $uri is string and starts with 'https://' or 'http://'
        if (is_string($uri) && (strpos($uri, 'https://') === 0 || strpos($uri, 'http://') === 0)) {
            return "{$uri}{$param}";
        }

        // Detect if the $uri starts with 'www.'
        if (is_string($uri) && strpos($uri, 'www.') === 0) {
            return "{$uri}{$param}";
        }

        if (is_array($uri)) {
            return site_url($uri, $protocol);
        }

        if (Route::getName($uri)) {
            $uri = route()->named($uri);
        }

        $uri = dotToslash($uri);

        if (!empty($param) && $protocol === null) {
            return site_url("{$uri}/{$param}");
        }

        return site_url($uri, $protocol);
    }
}

if (! function_exists('void_url')) {
    /**
     * A function that adds a void url
     *
     * @return void
     */
    function void_url()
    {
        echo 'javascript:void(0)';
    }
}

if (! function_exists('uri')) {

    /**
     *  Fetch URI string or Segment Array
     *
     * @param mixed ...$args
     * @return object|array|string
     */
    function uri(...$args)
    {

        if (
            count($args) === 1 && is_bool($args[0]) && $args[0] === true
            || count($args) === 2 && is_bool($args[0]) && is_bool($args[1])
        ) {

            $use_rsegment = ($args[1] ?? false) === true;
            $prefix = $use_rsegment ? 'r' : '';

            if ($args[0] === true) {
                return ci()->uri->{$prefix . 'segment_array'}();
            }

            return ci()->uri->{$prefix . 'uri_string'}();
        }

        $input = $args[0] ?? null;
        $uri_string = is_string($input) ? $input : null;

        // Detect native URI extension (PHP 8.5+)
        $has_native = PHP_VERSION_ID >= 80500 && extension_loaded('uri');

        if ($has_native) {

            return new class($uri_string) {

                private \Uri\Rfc3986\Uri $native;

                public function __construct(?string $uriString = null)
                {
                    global $input;

                    $uriString ??= $_SERVER['REQUEST_URI'] ?? '/';

                    // Build full absolute URI if needed
                    if (!str_contains($uriString, '://')) {
                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $host   = $_SERVER['HTTP_HOST'] ?? parse_url(base_url(), PHP_URL_HOST);
                        $uriString = $scheme . '://' . $host . $uriString;
                    }

                    // Handle relative paths passed explicitly
                    if (is_string($input) && !str_contains($input, '://') && $input !== '') {
                        $uriString = rtrim(base_url(), '/') . '/' . ltrim($input, '/');
                    }

                    $this->native = new \Uri\Rfc3986\Uri($uriString);
                }

                // Forward all native methods 
                public function __call($name, $args)
                {
                    return $this->native->{$name}(...$args);
                }

                // Fluent CI3-specific methods 
                public function segment(int $n): ?string
                {
                    $segments = $this->segments();
                    return $segments[$n - 1] ?? null;
                }

                public function segments(): array
                {
                    return array_values(array_filter(explode('/', trim($this->native->getPath() ?: '/', '/'))));
                }

                public function rsegment(int $n): ?string
                {
                    $rsegment = get_instance()->uri->rsegment_array();
                    return $rsegment[$n - 1] ?? null;
                }

                public function queryArray(): array
                {
                    parse_str($this->native->getQuery() ?: '', $query);
                    return $query;
                }

                public function withQuery(array|string $params): self
                {
                    $query = is_array($params)
                        ? http_build_query($params, '', '&', PHP_QUERY_RFC3986)
                        : $params;
                    $clone = clone $this;
                    $clone->native = $this->native->withQuery($query);
                    return $clone;
                }

                public function withPath(string $path): self
                {
                    $clone = clone $this;
                    $clone->native = $this->native->withPath($path);
                    return $clone;
                }

                public function withScheme(string $scheme): self
                {
                    $clone = clone $this;
                    $clone->native = $this->native->withScheme(strtolower($scheme));
                    return $clone;
                }

                public function withHost(string $host): self
                {
                    $clone = clone $this;
                    $clone->native = $this->native->withHost(idn_to_ascii($host) ?: $host);
                    return $clone;
                }

                // public function withPort(?int $port): self
                // {
                //     $clone = clone $this;
                //     $clone->native = $port === null ? $this->native->withoutPort() : $this->native->withPort($port);
                //     return $clone;
                // }

                public function withFragment(string $fragment): self
                {
                    $clone = clone $this;
                    $clone->native = $this->native->withFragment($fragment);
                    return $clone;
                }

                public function withoutQuery(): self
                {
                    return $this->withQuery('');
                }

                public function withoutFragment(): self
                {
                    return $this->withFragment('');
                }

                public function withSegment(int $n, string $value): self
                {
                    $segment = $this->segments();
                    $segment[$n - 1] = $value;
                    return $this->withPath('/' . implode('/', $segment));
                }

                public function appendSegment(string $segment): self
                {
                    return $this->withPath(rtrim($this->native->getPath(), '/') . '/' . ltrim($segment, '/'));
                }

                public function addQuery(array $params): self
                {
                    return $this->withQuery(array_merge($this->queryArray(), $params));
                }

                public function removeQueryParam(string $key): self
                {
                    $query = $this->queryArray();
                    unset($query[$key]);
                    return $this->withQuery($query);
                }

                public function path(): string
                {
                    return $this->native->getPath() ?: '/';
                }

                public function relative(): string
                {
                    $base = rtrim(base_url(), '/');
                    $full = $this->native->toString();
                    return str_starts_with($full, $base)
                        ? '/' . ltrim(substr($full, strlen($base)), '/')
                        : $full;
                }

                public function isHttps(): bool
                {
                    return $this->native->getScheme() === 'https';
                }

                public function toString(): string
                {
                    return  $this->native->toString();
                }

                public function __toString(): string
                {
                    return $this->toString();
                }
            };
        }

        return new class() {

            private $uri;

            private array $components;

            public function __construct(?string $uri = null)
            {
                $this->uri = $uri ?? ($_SERVER['REQUEST_URI'] ?? '/');

                if (!str_contains($this->uri, '://') && isset($_SERVER['HTTP_HOST'])) {
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $this->uri = $scheme . '://' . $_SERVER['HTTP_HOST'] . $this->uri;
                }

                if (is_string($uri) && !str_contains($uri, '://') && $uri !== '') {
                    $this->uri = rtrim(base_url(), '/') . '/' . ltrim($uri, '/');
                }

                $this->components = parse_url($this->uri) ?: [];
                $this->components['path'] ??= '/';
            }

            // Core
            public function segment(int $n): ?string
            {
                $segment = $this->segments();
                return $segment[$n - 1] ?? null;
            }

            public function segments(): array
            {
                return array_values(array_filter(explode('/', trim($this->components['path'] ?? '/', '/'))));
            }

            public function rsegment(int $n): ?string
            {
                $rsegment = ci()->uri->rsegment_array();
                return $rsegment[$n - 1] ?? null;
            }

            public function queryArray(): array
            {
                parse_str($this->components['query'] ?? '', $query);
                return $query;
            }

            // Fluent modifiers
            public function withQuery(array|string $params): self
            {
                $query = is_array($params) ? http_build_query($params, '', '&', PHP_QUERY_RFC3986) : $params;
                return $this->cloneWith(['query' => $query]);
            }

            public function withPath(string $path): self
            {
                $path = trim($path, '/');
                return $this->cloneWith(['path' => '/' . $path]);
            }

            public function withScheme(string $scheme): self
            {
                return $this->cloneWith(['scheme' => strtolower($scheme)]);
            }

            public function withHost(string $host): self
            {
                return $this->cloneWith(['host' => idn_to_ascii($host) ?: $host]);
            }

            public function withPort(?int $port): self
            {
                $components = $this->components;
                unset($components['port']);
                if ($port !== null) $components['port'] = $port;
                return $this->cloneWith($components);
            }

            public function withFragment(string $fragment): self
            {
                return $this->cloneWith(['fragment' => $fragment]);
            }

            public function withoutQuery(): self
            {
                return $this->cloneWith(['query' => null]);
            }

            public function withoutFragment(): self
            {
                return $this->cloneWith(['fragment' => null]);
            }

            public function withSegment(int $n, string $value): self
            {
                $segment = $this->segments();
                $segment[$n - 1] = $value;
                return $this->withPath('/' . implode('/', $segment));
            }

            public function appendSegment(string $segment): self
            {
                return $this->withPath(rtrim($this->components['path'], '/') . '/' . ltrim($segment, '/'));
            }

            public function prependSegment(string $segment): self
            {
                return $this->withPath('/' . trim($segment, '/') . $this->components['path']);
            }

            public function replaceQuery(array $params): self
            {
                return $this->withoutQuery()->withQuery($params);
            }

            public function addQuery(array $params): self
            {
                return $this->withQuery(array_merge($this->queryArray(), $params));
            }

            public function removeQueryParam(string $key): self
            {
                $query = $this->queryArray();
                unset($query[$key]);
                return $this->withQuery($query);
            }

            // Utilities
            public function path(): string
            {
                return $this->components['path'] ?? '/';
            }

            public function relative(): string
            {
                $baseUrl = rtrim(base_url(), '/');
                $uri = str_starts_with($this->uri, $baseUrl) ? '/' . ltrim(substr($this->uri, strlen($baseUrl)), '/') : $this->uri;
                return urldecode($uri);
            }

            public function isHttps(): bool
            {
                return ($this->components['scheme'] ?? 'http') === 'https';
            }

            public function toString(): string
            {
                $uri = $this->rebuild();
                return urldecode($uri);
            }

            public function toRawString(): string
            {
                return $this->rebuild();
            }

            public function __toString(): string
            {
                return $this->rebuild();
            }

            private function cloneWith(array $override): self
            {
                $clone = clone $this;
                $clone->components = array_merge($clone->components, $override);
                foreach (['query', 'fragment'] as $k) if (array_key_exists($k, $override) && $override[$k] === null) unset($clone->components[$k]);
                $clone->uri = $clone->rebuild();
                return $clone;
            }

            private function rebuild(): string
            {
                $scheme = $this->components['scheme'] ?? 'http';
                $host = $this->components['host'] ?? parse_url(base_url(), PHP_URL_HOST);
                $port = isset($this->components['port']) ? ':' . $this->components['port'] : '';
                $path = $this->components['path'] ?? '/';
                $query = $this->components['query'] ?? null ? '?' . $this->components['query'] : '';
                $fragment = $this->components['fragment'] ?? null ? '#' . $this->components['fragment'] : '';
                return "$scheme://$host$port$path$query$fragment";
            }
        };
    }
}

// ------------------------------------------------------------------------

if (! function_exists('action')) {
    /**
     * Use it for form actions.
     *
     * @param string $uri
     * @param mixed $method
     * @return string
     */
    function action($uri = '', $method = null)
    {
        if (is_null($uri)) {
            return "action='' ";
        }

        if (!is_null($method) && $method === 'post' || $method === 'get') {
            return "action='" . site_url($uri) . "'" . ' ' . "method='" . $method . "'" . ' ';
        }

        return "action='" . site_url($uri) . "'" . ' ';
    }
}

if (! function_exists('is_active')) {
    /**
     * Use it to set active or current url for 
     * css classes. Default class name is (active)
     *
     * @param string $link
     * @param string $class
     * @param string $default
     * @return string
     */
    function is_active($link, $class = null, $default = 'active')
    {
        $link = dotToslash($link);

        if ($class != null) {
            return ci()->uri->uri_string() == $link ? $class : $default;
        }

        return ci()->uri->uri_string() == $link ? $default : '';
    }
}

if (! function_exists('active_link')) {
    /**
     * Alias for is_active
     *
     * @param string $link
     * @param string $class
     * @param string $default
     * @param string $contains
     * @return bool|string
     */
    function active_link($link, $class = null, $default = 'active', $contains = '')
    {
        if (!empty($contains)) {
            return str_contains(current_route(), $contains);
        }

        return is_active($link, $class, $default);
    }
}

if (! function_exists('active_segment')) {
    /**
     * Use it to set active or current uri_segment for 
     * css classes. Default class name is (active)
     *
     * @param string $segment
     * @param string $segment_name
     * @param string $class
     * @return string
     */
    function active_segment(int $segment, string $segment_name, $class = 'active')
    {
        return uri_segment($segment) === $segment_name ? $class : '';
    }
}


if (! function_exists('uri_segment')) {
    /**
     * Alias for CodeIgniter's $this->uri->segment
     *
     * @param string $n
     * @param mixed $no_result
     * @return string
     */
    function uri_segment($n, $no_result = NULL)
    {
        return ci()->uri->segment($n, $no_result);
    }
}

if (! function_exists('go_back')) {
    /**
     * Go back using Html5 previous history
     * with a styled anchor tag
     * 
     * @param string $text
     * @param string $style
     * @return void
     */
    function go_back($text, $style = null)
    {
        echo '<a class="' . $style . '" href="javascript:window.history.go(-1);">' . $text . '</a>';
    }
}

if (! function_exists('html5_back')) {
    /**
     * Similar to go_back() function
     * 
     * To be used as link in href
     *
     * @return string
     */
    function html5_back()
    {
        return 'javascript:window.history.go(-1)';
    }
}

if (! function_exists('route')) {
    /**
     * @param string $uri 
     * @return object
     */
    function route($uri = null)
    {

        $route = new Route();

        if (is_null($uri)) {
            return $route->back();
        }

        return $route->setRoute($uri);
    }
}

if (! function_exists('route_to')) {
    /**
     * @param string $uri 
     * @return object
     */
    function route_to($uri = '')
    {
        return (new Route())->setRoute($uri)->redirect();
    }
}

if (! function_exists('route_view')) {
    /**
     * Return a view using 
     * view names from routes
     * 
     * @param string $view The view name
     * @return string
     */
    function route_view($view = '')
    {
        $path = trim(server('REQUEST_URI'), '/');

        if (strpos($path, '/') !== false) {
            $view = $path;
        }

        return $view;
    }
}

if (! function_exists('current_route')) {
    /**
     * Returns the current route
     *
     * @return string
     */
    function current_route()
    {
        return uri_string();
    }
}

/* ------------------------------- Request | Resource && User Agent Functions ---------------------------------*/

if (! function_exists('files')) {
    /**
     * function to get $_FILES values
     *
     * @param string $index
     * @return mixed
     */
    function files($index = '')
    {
        if (isset($_FILES[$index])) {
            return $_FILES[$index];
        }

        if ($index === '') {
            return $_FILES;
        }

        return null;
    }
}

if (! function_exists('has_file')) {
    /**
     * Check if file to upload is not empty
     *
     * @param string $file
     * @return bool
     */
    function has_file($file = '')
    {
        if ($file !== '' && isset($_FILES[$file])) {
            $file = $_FILES[$file];
        }

        return (empty($file['name']))
            ? false
            : true;
    }
}

if (! function_exists('is_file_empty')) {
    /**
     * Check if file is truely empty
     * 
     * expects $_FILES as $file
     * 
     * @param string $file
     * @return bool
     */
    function is_file_empty($file)
    {
        if (is_bool($file)) {
            throw new \Exception('Only $_FILES can be checked');
        }

        return (empty($file['size']))
            ? true
            : false;
    }
}

if (! function_exists('uploader')) {
    /**
     * Helper function to load the 
     * enhanced uploader library
     * 
     * @param array $config
     * @return object
     */
    function uploader($config = [])
    {
        ci()->load->library('uploader', $config);
        return ci()->uploader;
    }
}

if (! function_exists('input')) {
    /**
     * Function to set the input object
     *
     * @return CI_Input
     */
    function input()
    {
        return ci()->input;
    }
}

if (! function_exists('post')) {
    /**
     * Function to set only post methods
     *
     * @param string $index
     * @param bool $xss_clean
     * @return string|array
     */
    function post($index = null, $xss_clean = null)
    {
        return ci()->input->post($index, $xss_clean);
    }
}

if (! function_exists('get')) {
    /**
     * Function to set only get methods
     *
     * @param $string $index
     * @param bool $xss_clean
     * @return string|array
     */
    function get($index = null, $xss_clean = null)
    {
        return ci()->input->get($index, $xss_clean);
    }
}

if (! function_exists('is_ajax_request')) {
    /**
     * Check whether request is an ajax request
     *
     * @return bool
     */
    function is_ajax_request()
    {
        return ci()->input->is_ajax_request();
    }
}

if (! function_exists('server')) {
    /**
     * Fetch an item or all items 
     * from the SERVER array
     *
     * @param string $index
     * @param bool $xss_clean
     * @return string|array
     */
    function server($index = null, $xss_clean = null)
    {
        if (is_null($index)) {
            return $_SERVER;
        }

        return ci()->input->server($index, $xss_clean);
    }
}

if (! function_exists('ip_address')) {
    /**
     * Alias of IP Address Fetching from 
     * CodeIgniter's Input Class
     *
     * @return string
     */
    function ip_address()
    {
        return ci()->input->ip_address();
    }
}

if (! function_exists('raw_input_stream')) {
    /**
     * Holds a cache of php://input contents
     *
     * @return mixed
     */
    function raw_input_stream()
    {
        return ci()->input->raw_input_stream;
    }
}

if (! function_exists('raw_input_contents')) {
    /**
     * Get a uri and treat as php://input contents
     *
     * @param string|array $uri
     * @return mixed
     */
    function raw_input_contents($uri = null)
    {

        if (! is_null($uri) && ! is_array($uri)) {
            return file_get_contents($uri);
        }

        //@Todo: Will implement a logic here
        if (is_null($uri) && is_array($uri)) {
            //return file_get_contents("do something");
        }

        return raw_input_stream();
    }
}

/* ------------------------------- Form Functions ---------------------------------*/

if (! function_exists('old')) {

    /**
     * Use it as an alias and fill in for 
     * CodeIgniter's set_value function
     *
     * @param   string  $field      Field name
     * @param   string  $default    Default value
     * @param   bool    $html_escape    Whether to escape HTML special characters or not
     * @return  string
     * 
     */
    function old($field, $default = '', $html_escape = true)
    {
        if (!empty(session('old')) && !empty(session('old')[$field])) {
            return session('old')[$field];
        }

        return set_value($field, $default, $html_escape);
    }
}

if (! function_exists('old_radio')) {

    /**
     * Use it as a fill in for 
     * CodeIgniter's set_radio function
     * when returning form validation 
     * with input fields in a session
     *
     * @param   string  $field  Field name
     * @param   string  $value  Field value
     * @return  string
     * 
     */
    function old_radio($field, $value = '')
    {
        if (!empty(session('old')) && !empty(session('old')[$field])) {
            $field = session('old')[$field];
        }

        return ($field == $value) ? 'checked=checked' : '';
    }
}

if (! function_exists('old_checkbox')) {

    /**
     * Use it as a fill in for 
     * CodeIgniter's set_checkbox function
     * when returning form validation 
     * with input fields in a session
     *
     * @param string $field
     * @param string $value
     * @param bool $default
     * @return string
     */
    function old_checkbox($field, $value = '')
    {
        return old_radio($field, $value);
    }
}

if (! function_exists('selected')) {
    /**
     * Use it to compare values without 
     * CodeIgniter's set_select function
     *
     * @param string $existing_value
     * @param string $comparing_value
     * @return string
     */
    function selected($existing_value, $comparing_value)
    {
        return ($existing_value === $comparing_value) ? ' selected="selected"' : '';
    }
}

if (! function_exists('multi_selected')) {
    /**
     * Use it to compare values that have
     * multiple selected values
     * 
     * Preferrable when updating a form 
     *
     * @param string $existing_value
     * @param array $comparing_array_values
     * @return string
     */
    function multi_selected($existing_value, $comparing_array_values)
    {
        return in_array($existing_value, $comparing_array_values) ? ' selected="selected"' : '';
    }
}



if (! function_exists('verify_selected')) {
    /**
     * Works similarly as the above function
     * This time use it to compare values with CodeIgniter's 
     * set_select function as a third parameter 
     *
     * e.g set_select('field_name', 
     *           $value_to_compare, 
     *           verify_selected($value_to_compare , $compared_value)
     *       );      
     * 
     * @param string $existing_value
     * @param string $comparing_value
     * @return string
     */
    function verify_selected($existing_value, $comparing_value)
    {
        //Use it to compare values
        return ($existing_value === $comparing_value) ? true : false;
    }
}

if (! function_exists('use_form_validation')) {
    /**
     * Alias of CodeIgniter's $this->form_validation
     * 
     * @return object
     */
    function use_form_validation($name = null)
    {

        if ($name !== null) {
            ci()->load->library('form_validation', null, $name);
            return ci()->{$name};
        }

        ci()->load->library('form_validation');

        return ci()->form_validation;
    }
}

if (! function_exists('validate')) {
    /**
     * Alias of CodeIgniter's 
     * $this->form_validation->set_rules
     *
     * @param string $field
     * @param string $label
     * @param string|array $rules
     * @param mixed $errors
     * @return mixed
     */
    function validate($field = '', $label = '', $rules = [], $errors = null)
    {
        if (!empty($field)) {
            return ci()->form_validation->set_rules($field, $label, $rules, $errors);
        }

        return ci()->form_validation;
    }
}

if (! function_exists('form_valid')) {
    /**
     * Checks if form is valid
     * Can use parameter ($rules) to specify a
     * an already given rules
     *
     * @param string $rules
     * @return bool
     */
    function form_valid($rules = '')
    {
        return ci()->form_validation->run($rules);
    }
}

if (! function_exists('form_error_exists')) {
    /**
     * Checks if a form error exists
     *
     * @param string $input_field
     * @return mixed
     */
    function form_error_exists($input_field = null)
    {
        $error = form_error($input_field);
        $custom_error = get_form_error($input_field);

        if (is_null($input_field)) {
            return '';
        }

        if (! empty($error)) {
            return true;
        }

        if (! empty($custom_error)) {
            return $custom_error;
        }

        return false;
    }
}

if (! function_exists('form_error_array')) {
    /**
     * Gets form errors in an array form
     *
     * @return array
     */
    function form_error_array()
    {
        return ci()->form_validation->error_array();
    }
}

if (! function_exists('get_form_error')) {
    /**
     * Retrieve a form error from
     * error array
     *
     * @param string $error_key
     * @return mixed
     */
    function get_form_error($error_key)
    {
        $error_array = !empty(session('form_error')) ? session('form_error') : [];
        $error_array = array_merge(form_error_array(), $error_array);

        if (array_key_exists($error_key, $error_array)) {
            return $error_array[$error_key];
        }

        return '';
    }
}

if (! function_exists('set_error')) {
    /**
     * Sets form error on a 
     * named input field
     *
     * @param string $field
     * @param string $error
     * @return mixed
     */
    function set_error($field, $error)
    {
        ci()->form_validation->set_error($field, $error);
    }
}

if (! function_exists('set_error_delimeter')) {
    /**
     * Sets error delimeter to
     * be used when displaying errors
     *
     * @param string $open_tag
     * @param string $close_tag
     * @return mixed
     */
    function set_error_delimeter($open_tag = '', $close_tag = '')
    {
        ci()->form_validation->set_error_delimiters($open_tag, $close_tag);
    }
}

if (! function_exists('set_form_data')) {
    /**
     * Set form data
     *
     * @param array $form_data
     * @return mixed
     */
    function set_form_data(array $form_data)
    {
        ci()->form_validation->set_data($form_data);
    }
}

/* ------------------------------- Loader Functions ---------------------------------*/

if (! function_exists('use_config')) {
    /**
     * Load a config file and instantiate
     *
     * @param string $config_file
     * @param bool $use_sections
     * @param bool $fail_gracefully
     * @return bool true if the file was loaded correctly or false on failure
     */
    function use_config(
        $config_file = '',
        $use_sections = false,
        $fail_gracefully = false
    ) {

        $config_file = has_dot($config_file);

        return ci()->config->load($config_file, $use_sections, $fail_gracefully);
    }
}

if (! function_exists('use_thirdparty')) {
    /**
     * Use a package from a specific directory
     * and use it's available models, libraries etc
     * the codeigniter way
     *
     * @param string $path
     * @param string $file
     * @param bool $file_content
     * @param bool $view_cascade
     * @return void
     */
    function use_thirdparty($path, $file = '', $file_content = false, $view_cascade = true)
    {
        ci()->load->thirdparty($path, $file, $file_content, $view_cascade);
    }
}

if (! function_exists('remove_thirdparty')) {
    /**
     * Remove a package from a Third Party directory
     * including it's available models, libraries etc
     * the codeigniter way
     *
     * @param string $path
     * @return void
     */
    function remove_thirdparty($path)
    {
        ci()->load->removeThirdparty($path);
    }
}

if (! function_exists('use_package')) {
    /**
     * Use a package from a specific directory
     * and use it's available models, libraries etc
     * the codeigniter way
     *
     * @param string $path
     * @param bool $view_cascade
     * @return void
     */
    function use_package($path, $view_cascade = true)
    {
        ci()->load->package($path, $view_cascade);
    }
}

if (! function_exists('remove_package')) {
    /**
     * Remove a package from a specific directory
     * including it's available models, libraries etc
     * the codeigniter way
     *
     * @param string $path
     * @return void
     */
    function remove_package($path)
    {
        ci()->load->removePackage($path);
    }
}

if (! function_exists('use_library')) {
    /**
     * Use a library/libraries and instantiate
     *
     * @param string|array $library
     * @param array $params
     * @param string $object_name
     * @return void
     */
    function use_library($library, $params = null, $object_name = null)
    {
        $library = has_dot($library);

        ci()->load->library($library, $params, $object_name);
    }
}

if (! function_exists('use_driver')) {
    /**
     * Use a driver/drivers and instantiate
     *
     * @param string|array $driver
     * @param array $params
     * @param string $object_name
     * @return void
     */
    function use_driver($driver, $params = null, $object_name = null)
    {
        $driver = has_dot($driver);

        ci()->load->driver($driver, $params, $object_name);
    }
}

if (! function_exists('use_action')) {
    /**
     * Use an action/actions and instantiate
     *
     * @param string|array $action
     * @return void
     */
    function use_action($action)
    {
        $action = has_dot($action);

        ci()->load->action($action);
    }
}


if (! function_exists('use_service')) {
    /**
     * Use a service/services and instantiate
     *
     * @param string|array $service
     * @param array $params
     * @param string $object_name
     * @return void
     */
    function use_service($service, $object_name = null, $params = null)
    {
        $service = has_dot($service);

        ci()->load->service($service, $params, $object_name);
    }
}

if (! function_exists('use_services')) {
    /**
     * Discover services listed in
     * the service.php config file
     *
     * Only instantiate webby services and
     * not app services
     * 
     * @param array $classes
     * @return mixed
     */
    function use_services(array $classes = [])
    {

        $webby_services = ci()->config->item('webby_services');
        $app_services = ci()->config->item('app_services');

        $services = !empty($webby_services) ? $webby_services : [];

        if (!empty($classes) || !empty($app_services)) {
            $services = array_merge($services, $classes);
        }

        $keys = array_keys($services);
        $values = has_dot(array_values($services));
        $services = array_unique(array_combine($keys, $values));

        foreach ($services as $alias => $service) {
            service($service, $alias);
        }

        $services = array_merge($services, $app_services);

        return $services;
    }
}

if (! function_exists('use_model')) {
    /**
     * Use a model/models and instantiate
     *
     * @param string|array $model
     * @param string $name
     * @param bool $db_conn
     * @return mixed
     */
    function use_model($model, $name = '', $db_conn = false)
    {
        $class = null;

        if ((!empty($model) && class_exists($model))) {
            $class = new $model();
        }

        if (is_object($class) && !empty($name)) {
            class_alias(get_class($class), $name);
            return new $name();
        }

        if (is_object($class)) {
            return new $class();
        }

        $model = has_dot($model);

        ci()->load->model($model, $name, $db_conn);
    }
}

if (! function_exists('use_helper')) {
    /**
     * Use a helper/helpers
     *
     * @param string|array $helper
     * @return void
     */
    function use_helper($helper)
    {
        $helper = has_dot($helper);

        ci()->load->helper($helper);
    }

    /**
     *  Load any CI3 helper with dot notation
     *
     *  @param     string    $name
     *  @param     array     $params
     *  @return    mixed
     */
    function helper($name, $params)
    {
        //	Separate 'file' and 'helper' by dot notation
        [$helper, $function] = array_pad(
            explode('.', $name),
            2,
            null
        );

        //	If using dot notation
        if ($function !== null) {
            get_instance()->load->helper($helper);
            $helper = $function;
        }

        return call_user_func_array($helper, $params);
    }
}

if (! function_exists('use_rule')) {
    /**
     * Use a rule
     * This function lets users load rules.
     * That can be used when validating forms 
     * It is designed to be called from a user's app
     * It can be used in controllers or models
     *
     * @param string|array $rule
     * @param bool $return_array
     * @return void
     */
    function use_rule($rule = [], $return_array = false)
    {
        $rule = has_dot($rule);

        ci()->load->rule($rule, $return_array);
    }
}

if (! function_exists('use_form')) {
    /**
     * Use a form
     * This function lets users load forms.
     * That can be used when validating forms 
     * 
     * It is designed to be called from a user's app
     * It can be used in controllers or models
     *
     * @param string|array $rule
     * @return void
     */
    function use_form($form = [])
    {
        $form = has_dot($form);

        ci()->load->form($form);
    }
}

if (! function_exists('rules')) {
    /**
     * Return available rules
     * Call this function when you load
     * files that use $rules array variable
     *
     * @param string $rule
     * @return mixed
     */
    function rules()
    {
        return !empty(ci()->load->rules) ? ci()->load->rules : [];
    }
}

if (! function_exists('use_language')) {
    /**
     * load a language file 
     *
     * @param string $langfile
     * @param string $idiom
     * @param bool $return
     * @param bool $add_suffix
     * @param string $alt_path
     * @return void|array
     */
    function use_language($langfile, $idiom = '', $return = false, $add_suffix = true, $alt_path = '')
    {
        ci()->lang->load($langfile, $idiom, $return, $add_suffix, $alt_path);
    }
}

if (! function_exists('trans')) {
    /**
     * specify a line to use 
     * from the language file
     *
     * @param string $line
     * @param bool $log_errors
     * @return string
     */
    function trans($line, $log_errors = true)
    {
        return ci()->lang->line($line, $log_errors);
    }
}

if (! function_exists('__')) {
    /**
     * alias to the function above
     *
     * @param string $line
     * @param bool $log_errors
     * @return string
     */
    function __($line, $log_errors = true)
    {
        return trans($line, $log_errors);
    }
}

if (! function_exists('parse_lang')) {
    /**
     * Translate a language line
     * with placeholders
     *
     * @param string $lang_line
     * @param array|mixed ...$placeholders
     * @return string
     */
    function parse_lang(string $lang_line, ...$placeholders)
    {
        $format = trans($lang_line);
        return vsprintf($format, $placeholders);
    }
}

<?php
defined('COREPATH') or exit('No direct script access allowed');

/**
 *  Webby Helper functions
 *
 *  @package		Webby
 *	@subpackage		Helpers
 *	@category		Helpers
 *	@author			Kwame Oteng Appiah-Nti
 */

/* ------------------------------- Random Code Generation Functions ---------------------------------*/

if (! function_exists('unique_code')) {
    /**
     * Generates unique ids/codes
     *
     * @param integer $limit
     * @return string
     */
    function unique_code(int $limit = 13)
    {
        return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
    }
}

if (! function_exists('unique_id')) {
    /**
     * Generates unique ids
     *
     * @param integer $length
     * @return string
     */
    function unique_id(int $length = 13)
    {

        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($length / 2));
        } else {
            throw new \Exception("no cryptographically secure random function available");
        }
        return substr(bin2hex($bytes), 0, $length);
    }
}

if (! function_exists('hash_id')) {

    /**
     * Generate unique fake ids
     * 
     * @param int|string $string
     * @param int $length
     * @return string
     */
    function hash_id(mixed $string, int $length = 6)
    {
        return Base\Helpers\FakeId::encode($string, $length);
    }
}

if (! function_exists('unhash_id')) {

    /**
     * Unhash fake ids
     * 
     * @param int|string $string
     * @return string|int
     */
    function unhash_id(mixed $string)
    {
        return Base\Helpers\FakeId::decode($string);
    }
}

/* ----------------------------- Polyfill Functions ---------------------------------*/

if (! function_exists('utf8_encode')) {
    /**
     * A function to restore utf8_encode
     * 
     * @param string $string
     * @return string
     */
    function utf8_encode($string, $as_obsolete = false)
    {
        if (!$as_obsolete) {
            return mb_convert_encoding($string, 'UTF-8');
        }

        $string .= $string;
        $length = \strlen($string);

        for ($i = $length >> 1, $j = 0; $i < $length; ++$i, ++$j) {
            switch (true) {
                case $string[$i] < "\x80":
                    $string[$j] = $string[$i];
                    break;
                case $string[$i] < "\xC0":
                    $string[$j] = "\xC2";
                    $string[++$j] = $string[$i];
                    break;
                default:
                    $string[$j] = "\xC3";
                    $string[++$j] = \chr(\ord($string[$i]) - 64);
                    break;
            }
        }

        return substr($string, 0, $j);
    }
}

if (! function_exists('utf8_decode')) {
    /**
     * A function to restore utf8_decode
     *
     * @param string $string
     * @return string
     */
    function utf8_decode($string, $as_obsolete = false)
    {
        if (!$as_obsolete) {
            return mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');
        }

        $string = (string) $string;
        $length = \strlen($string);

        for ($i = 0, $j = 0; $i < $length; ++$i, ++$j) {
            switch ($string[$i] & "\xF0") {
                case "\xC0":
                case "\xD0":
                    $c = (\ord($string[$i] & "\x1F") << 6) | \ord($string[++$i] & "\x3F");
                    $string[$j] = $c < 256 ? \chr($c) : '?';
                    break;

                case "\xF0":
                    ++$i;
                    // no break

                case "\xE0":
                    $string[$j] = '?';
                    $i += 2;
                    break;

                default:
                    $string[$j] = $string[$i];
            }
        }

        return substr($string, 0, $j);
    }
}

/* ------------------------------- Config Functions ---------------------------------*/

if (! function_exists('config')) {
    /**
     * Fetch or Set a config file item
     * or get an instance of a namespaced 
     * Config class.
     *
     * @param array|string $key
     * @param mixed $value
     * @return mixed
     */
    function config(string|array|null $key = null, mixed $value = null)
    {

        $config = ci('config');

        if (is_array($key)) {
            foreach ($key as $item => $val) {
                $config->set_item($item, $val);
            }
            return $config;
        }

        if ($key === null) {
            return $config;
        }

        if ($value !== null) {
            $config->set_item($key, $value);
            return $config;
        }

        // Handle Namespaced Config Classes
        // If the first letter is uppercase, 
        // assume it's a Config Class.
        if (ctype_upper($key[0])) {

            static $instances = [];

            if (strpos($key, '.') !== false) {
                [$class, $property] = explode('.', $key, 2);
            } else {
                $class = $key;
                $property = null;
            }

            $className = ucfirst($class);
            $fqcn = 'App\\Config\\' . $className;

            if (!isset($instances[$fqcn])) {
                if (!class_exists($fqcn)) {
                    return $config->item($key);
                }
                $instances[$fqcn] = new $fqcn();
            }

            return $property === null ? $instances[$fqcn] : $instances[$fqcn]->get($property);
        }

        return $config->item($key);
    }
}

/* ------------------------------- Session Functions ---------------------------------*/

if (! function_exists('init_session')) {
    /**
     * This function is used to initialize or create sessions
     * same as the native session_start function.
     * @return 
     */
    function init_session()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start(); //the good old friend
        }
    }
}

if (! function_exists('sessions')) {
    /**
     * This function is used for retrieving all Session Data
     * @return array (all session data)
     */
    function sessions()
    {
        ci()->load->library('session');
        return ci()->session->all_userdata();
    }
}

if (! function_exists('session')) {
    /**
     * Add or retrieve a session data
     *
     * @param array|string $key
     * @param string|null $value
     * @return string|null|object
     */
    function session($key = null, $value = null)
    {
        ci()->load->library('session');

        if (empty($key)) {
            return ci()->session;
        }

        if (is_array($key)) {
            return ci()->session->set_userdata($key);
        }

        if (!is_null($value) && is_string($key)) {
            return ci()->session->set_userdata($key, $value);
        }

        return ci()->session->userdata($key);
    }
}

if (! function_exists('remove_session')) {
    /**
     * Remove session data
     *
     * @param array|string $key
     * @return void
     */
    function remove_session($key)
    {
        ci()->load->library('session');
        ci()->session->unset_userdata($key);
    }
}

if (! function_exists('has_session')) {
    /**
     * Verify if a session value exists
     *
     * @param string $key
     * @return bool
     */
    function has_session($key)
    {
        ci()->load->library('session');
        return ci()->session->has_userdata($key);
    }
}

if (! function_exists('flash_session')) {
    /**
     * Set or retrieve flash data
     *
     * @param array|string $key
     * @param string $value
     * @return string|void
     */
    function flash_session($key, $value = null)
    {
        ci()->load->library('session');

        if (is_array($key)) {
            return ci()->session->set_flashdata($key);
        }

        if (!is_null($value) && is_string($key)) {
            return ci()->session->set_flashdata($key, $value);
        }

        return ci()->session->flashdata($key);
    }
}

if (! function_exists('destroy_session')) {
    /**
     * Destroy session data
     *
     * @return void
     */
    function destroy_session()
    {
        ci()->load->library('session');
        ci()->session->sess_destroy();
    }
}

if (! function_exists('success_message')) {
    /**
     * Set/Get success message
     *
     * @param string $message
     * @return string
     */
    function success_message(?string $message = null)
    {

        if ($message !== null) {
            return flash_session('success_message', $message);
        }

        return flash_session('success_message');
    }
}

if (! function_exists('error_message')) {
    /**
     * Set/Get error message
     *
     * @param string $message
     * @return string
     */
    function error_message(?string $message = null)
    {

        if ($message !== null) {
            return flash_session('error_message', $message);
        }

        return flash_session('error_message');
    }
}

if (! function_exists('info_message')) {
    /**
     * Set/Get info message
     *
     * @param string $message
     * @return string
     */
    function info_message(?string $message = null)
    {

        if ($message !== null) {
            return flash_session('info_message', $message);
        }

        return flash_session('info_message');
    }
}

if (! function_exists('warn_message')) {
    /**
     * Set/Get warning message
     *
     * @param string $message
     * @return string
     */
    function warn_message(?string $message = null)
    {

        if ($message !== null) {
            return flash_session('warn_message', $message);
        }

        return flash_session('warn_message');
    }
}

if (! function_exists('clear_message')) {
    /**
     * Clearing message type
     * @return void
     */
    function clear_message($message_type)
    {
        remove_session($message_type);
    }
}

/* ------------------------------- Cache Functions ---------------------------------*/

if (! function_exists('cache')) {
    /**
     * Cache items or retreive items
     *
     * @param string $key
     * @param mixed $value
     * @param string $cache_path
     * @param integer $ttl
     * @return mixed
     */
    function cache($key = null, $value = null, $cache_path = '', $ttl = 1800)
    {
        $cache = new Base\Cache\Cache;
        $cache->ttl = $ttl;

        if ($key === null) {
            return $cache;
        }

        if (!empty($cache_path)) {
            $cache->setCachePath($cache_path);
        }

        if (is_string($key) && $value !== null) {
            return $cache->cacheItem($key, $value);
        }

        return $cache->getCacheItem($key);
    }
}

/* ------------------------------- String Functions ---------------------------------*/

if (! function_exists('dotToslash')) {
    /**
     * Replace dots with forward slashes in a string.
     *
     * @param string $string The input string
     * @return string The string with dots replaced by forward slashes
     */
    function dotToslash(string $string): string
    {
        return str_replace('.', '/', $string);
    }
}

if (! function_exists('convertDotsToSlashes')) {
    /**
     * Recursively convert dots to forward slashes in strings, arrays, or mixed data.
     * 
     * This function handles:
     * - Strings: converts dots to slashes
     * - Arrays: recursively processes all values
     * - Other types: returns unchanged
     *
     * @param mixed $input The input data (string, array, or other)
     * @return mixed The processed data with dots converted to slashes
     */
    function convertDotsToSlashes($input)
    {
        if (is_string($input)) {
            return dotToslash($input);
        }

        if (is_array($input)) {
            return array_map('convertDotsToSlashes', $input);
        }

        // Return other types unchanged
        return $input;
    }
}

if (! function_exists('convertDotsInArray')) {
    // Alternative version with more specific typing for arrays

    /**
     * Convert dots to slashes in an array of strings.
     *
     * @param array<string> $strings Array of strings to process
     * @return array<string> Array with dots converted to slashes
     */
    function convertDotsInArray(array $strings): array
    {
        return array_map('dotToslash', $strings);
    }
}


if (! function_exists('has_dot')) {
    /**
     * Alias to convertDotsToSlashes() instead
     * @param mixed $string
     * @return mixed
     */
    function has_dot(mixed $string): mixed
    {
        return convertDotsToSlashes($string);
    }
}

if (! function_exists('pad_left')) {
    /**
     * prefix string at the beginning of a string
     *
     * @param string $str
     * @param mixed $value
     * @param int $length
     * @return string
     */
    function pad_left($str, $value, $length)
    {
        return str_pad($value, $length, $str, STR_PAD_LEFT);
    }
}

if (! function_exists('pad_right')) {
    /**
     * suffix string at the end of a string
     *
     * @param string $str
     * @param mixed $value
     * @param int $length
     * @return string
     */
    function pad_right($str, $value, $length)
    {
        return str_pad($value, $length, $str, STR_PAD_RIGHT);
    }
}

if (! function_exists('str_left_zeros')) {
    /**
     * prefix zeros at the beginning of a string
     * 
     * @param mixed $value
     * @param int $length
     * @return string
     */
    function str_left_zeros($value, $length)
    {
        return pad_left('0', $value, $length);
    }
}

if (! function_exists('str_right_zeros')) {
    /**
     * suffix zeros at the end of a string
     *
     * @param mixed $value
     * @param int $length
     * @return string
     */
    function str_right_zeros($value, $length)
    {
        return pad_right('0', $value, $length);
    }
}

if (! function_exists('str2hex')) {
    /**
     * convert string to hexadecimal
     *
     * @param string $str
     * @return string
     */
    function str2hex($str)
    {
        $str = trim($str);
        return bin2hex($str);
    }
}

if (! function_exists('hex2str')) {
    /**
     * convert hexadecimal to string
     *
     * @param string $hex_string
     * @return string
     */
    function hex2str(/*hexadecimal*/$hex_string)
    {
        $hex_string = hex2bin($hex_string);
        return trim($hex_string);;
    }
}

if (! function_exists('dec2str')) {
    /**
     * Convert decimal to string using base
     * this is made for special use case
     * else use strval() i.e from int to string
     * 
     * @param string $decimal
     * @param int $base
     * @return string
     */
    function dec2str(/*decimal*/$decimal, $base = 36)
    {
        $string = null;

        $base = (int) $base;
        if ($base < 2 | $base > 36 | $base == 10) {
            throw new \Exception('$base must be in the range 2-9 or 11-36');
        }

        // maximum character string is 36 characters
        $charset = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // strip off excess characters (anything beyond $base)
        $charset = substr($charset, 0, $base);

        if (!preg_match('/^[0-9]{1,50}$/', trim($decimal))) {
            throw new \Exception('Value must be a positive integer with < 50 digits');
        }

        do {
            // get remainder after dividing by BASE
            $remainder = bcmod($decimal, $base);

            $char = substr($charset, $remainder, 1);   // get CHAR from array
            $string = "$char$string";                    // prepend to output

            //$decimal = ($decimal - $remainder) / $base;
            $decimal = bcdiv(bcsub($decimal, $remainder), $base);
        } while ($decimal > 0);

        return $string;
    }
}

if (! function_exists('bchexdec')) {
    /**
     * Summary of bchexdec
     * @param mixed $hex
     * @return bool|int|string
     */
    function bchexdec(mixed $hex)
    {
        if (!ctype_xdigit($hex)) return false; // Or throw an error
        $decimal = '0';
        $length = strlen($hex);
        for ($i = 0; $i < $length; $i++) {
            $char = $hex[$i];
            $value = hexdec($char); // Get decimal value of single hex char
            // Multiply current decimal by 16, then add new char's value
            $decimal = bcadd(bcmul($decimal, '16'), (string)$value);
        }
        return $decimal;
    }
}

if (! function_exists('slugify')) {
    /**
     * create strings with hyphen seperated
     *
     * @param string $string
     * @return string
     */
    function slugify($string, $separator = '-', $lowercase = true)
    {
        ci()->load->helper('url');
        ci()->load->helper('text');

        // Replace unsupported 
        // characters (if necessary more will be added)
        $string = str_replace("'", '-', $string);
        $string = str_replace(".", '-', $string);
        $string = str_replace("²", '2', $string);

        // Slugify and return the string
        return url_title(
            convert_accented_characters($string),
            $separator,
            $lowercase
        );
    }
}

if (! function_exists('extract_email_name')) {
    /**
     * Extract name from a given email address
     *
     * @param string $email
     * @return string
     */
    function extract_email_name($email)
    {
        $email = explode('@', $email);

        return $email[0];
    }
}

if (! function_exists('str_extract')) {
    /**
     * Extract symbol,character,word from a string
     *
     * @param string $string
     * @param string $symbol
     * @return string
     */
    function str_extract($string, $symbol)
    {
        $string = explode($symbol, $string);

        return $string = $string[0];
    }
}

if (! function_exists('exploded_title')) {
    /**
     * Explode Title
     *
     * @param string $title
     * @return string
     */
    function exploded_title($title)
    {
        return @trim(@implode('-', @preg_split("/[\s,-\:,()]+/", @$title)), '');
    }
}

if (! function_exists('str_clean')) {
    /**
     * Clean by removing spaces and special 
     * characters from string
     *
     * @param string $string
     * @return string
     */
    function str_clean($string)
    {
        $string = str_replace(' ', '', $string); // Replaces all spaces.

        return $text = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }
}

if (! function_exists('replace_string')) {
    /**
     * search for a string 
     * and replace string with another
     *
     * @param string $string
     * @param string $word
     * @param string $replace_with
     * @return string|bool
     */
    function replace_string($string, $word, $replace_with)
    {
        if (find_word($string, $word)) {
            return str_replace($word, $replace_with, $string);
        }

        return false;
    }
}

if (! function_exists('remove_underscore')) {
    /**
     * remove underscore from string
     *
     * @param string $str
     * @return string
     */
    function remove_underscore($str)
    {
        return str_replace("_", " ", $str);
    }
}


if (! function_exists('remove_hyphen')) {

    /**
     * remove hyphen from string
     *
     * @param string $str
     * @return string
     */
    function remove_hyphen($str)
    {
        return str_replace("-", " ", $str);
    }
}

if (! function_exists('str_humanize')) {
    /**
     *
     * remove hyphen or underscore from 
     * string and you can capitalize it 
     *
     * @param string $str
     * @param bool $capitalize
     * @return string
     */
    function str_humanize($str, $capitalize = false)
    {
        $str = remove_underscore($str);
        $str = remove_hyphen($str);

        if ($capitalize) {
            $str = ucwords($str);
        }

        return $str;
    }
}

if (! function_exists('str_ext')) {
    /**
     * Strips filename from extension Or
     * file extension from name
     * 
     * if $name is true return filename only
     * this is rarely used
     *
     * @param string $string
     * @return string
     */
    function str_ext(string $filename, $name = false)
    {
        if ($name) {
            return pathinfo($filename, PATHINFO_FILENAME);
        }

        return substr($filename, strrpos($filename, '.', -1), strlen($filename));
    }
}

if (! function_exists('str_last_word')) {
    /**
     * Return last word of a string
     * 
     * @param string $string
     * @return string
     */
    function str_last_word(string $string, $symbol = ' ')
    {
        return substr($string, strrpos($string, $symbol) + 1);
    }
}

if (! function_exists('limit_words')) {
    /**
     * Limit length of a given string
     *
     * @param string $text
     * @param int $limit
     * @param string $ending_character
     * @return string
     */
    function limit_words($text, $limit, $ending_character = '&#8230;')
    {
        ci()->load->helper('text');

        return word_limiter($text, $limit, $ending_character);
    }
}

if (! function_exists('truncate_text')) {
    /**
     * Truncate words of a given string
     *
     * @param string $text
     * @param int $limit
     * @param string $ending_character
     * @return string
     */
    function truncate_text($text, $limit, $ending_character = '&#8230;')
    {
        ci()->load->helper('text');

        return character_limiter($text, $limit, $ending_character);
    }
}

if (! function_exists('str_censor')) {
    /**
     * Censor bad words from string
     *
     * @param string $text
     * @param string|array $words_to_censor
     * @param bool $replacement
     * @return string
     */
    function str_censor($text, $words_to_censor, $replacement = false)
    {
        ci()->load->helper('text');

        return word_censor($text, $words_to_censor, $replacement);
    }
}

if (! function_exists('find_word')) {
    /**
     * search for word from a string
     *
     * @param string $string
     * @param string $word
     * @return string
     */
    function find_word($string, $word)
    {
        if (is_array($string)) {
            $string = arrtostr(',', $string);
        }

        if (strpos($string, $word) !== false) {
            return true;
        }

        return false;
    }
}

if (! function_exists('contains')) {
    /**
     * Returns true if $needle
     * is a substring of $haystack
     *
     * @param string $needle
     * @param mixed $haystack
     * @return bool
     */
    function contains($needle, $haystack)
    {
        return strpos($haystack, $needle) !== false;
    }
}

if (! function_exists('starts_with')) {
    /**
     *  Determine if a given string 
     *  starts with a given substring
     *
     *  @param     string          $haystack
     *  @param     string|array    $needles
     *  @return    bool
     */
    function starts_with($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if (
                $needle != '' &&
                substr($haystack, 0, strlen($needle)) === (string) $needle
            ) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('ends_with')) {
    /**
     *  Determine if a given string 
     *  ends with a given substring
     *
     *  @param string $haystack
     *  @param string|array $needles
     *  @return bool
     */
    function ends_with($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }
}

/* ------------------------------- Array Functions ---------------------------------*/

if (! function_exists('rayz')) {
    /**
     *  Helper function to instantiate the Rayz Class
     *
     *  @param  array|iterator  $array
     *  @return object
     */
    function rayz(array|iterator $array = [])
    {
        return (new \Base\Helpers\Rayz($array));
    }
}

if (! function_exists('arrayz')) {
    /**
     * Helper function for Arrayz Class
     *
     * @param mixed $items
     * @return Base\Helpers\Arrayz
     */
    function arrayz(mixed $items = []): Base\Helpers\Arrayz
    {
        return Base\Helpers\Arrayz::make($items);
    }
}

if (! function_exists('has_element')) {
    /**
     * Check if array element exists
     *
     * @param string|mixed $element
     * @param array $array
     * @return bool
     */
    function has_element($element, $array)
    {
        if (in_array($element, $array)) {
            return true;
        }

        return false;
    }
}

if (! function_exists('with_each')) {
    /**
     * Replaces the deprecated each function
     * since PHP 7.2 but limited in its implementation
     *
     * @param array $array
     * @return mixed
     */
    function with_each(array &$array): mixed
    {
        $key = key($array);
        $result = ($key === null)
            ? false
            : [$key, current($array), 'key' => $key, 'value' => current($array)];
        next($array);
        return $result;
    }
}

if (! function_exists('strtoarr')) {
    /**
     * Converts a string to an array
     *
     * @param string $symbol
     * @param string $string
     * @return array
     */
    function strtoarr(string $symbol, string $string): array
    {
        return explode($symbol, $string);
    }
}

if (! function_exists('arrtostr')) {
    /**
     * Converts an array to a string using a given symbol
     * 
     * @param string $symbol
     * @param array $array
     * @return string|false
     */
    function arrtostr(string $symbol, ?array $array): string|false
    {
        if ($array === null) {
            return false;
        }

        return implode($symbol, $array);
    }
}

if (! function_exists('add_array')) {
    /**
     * Add an element to an array
     *
     * @param array|string $array
     * @param mixed $element
     * @param string|null $symbol
     * @param bool $return_string
     * @return array|string
     */
    function add_array(array|string $array, mixed $element, ?string $symbol = null, bool $return_string = false): array|string
    {
        if (!is_array($array) && $symbol !== null) {
            $array = strtoarr($symbol, $array);
        }

        if (is_array($array)) {
            $array[] = $element;
        }

        if ($return_string === true && $symbol !== null) {
            return arrtostr($symbol, $array);
        }

        return $array;
    }
}

if (! function_exists('add_associative_array')) {
    /**
     * This is a function that helps to 
     * add associative key => value
     * to an associative array
     * 
     * Set multi to true to 
     * insert into multidimensional
     *
     * @param array $array
     * @param string $key
     * @param string $value
     * @return array
     */
    function add_associative_array($array, $key, $value, $multi = false)
    {

        if ($multi === false) {
            $array[$key] = $value;
            return $array;
        }

        $array = array_map(function ($array) use ($key, $value) {
            return $array + [$key => $value];
        }, $array);

        return $array;
    }
}

if (! function_exists('set_array')) {
    /**
     * Alias of the above function
     *
     * @param array $array
     * @param string $key
     * @param string $value
     * @return array
     */
    function set_array($array, $key, $value, $multi = false)
    {
        return add_associative_array($array, $key, $value, $multi);
    }
}

if (! function_exists('remove_empty_elements')) {
    /**
     * Remove keys and values 
     * that are empty
     *
     * @param array $array
     * @return array
     */
    function remove_empty_elements($array)
    {
        $array = array_map('array_filter', $array);
        return $array = array_filter($array);
    }
}

if (! function_exists('remove_first_element')) {
    /**
     * Removes first element of an array
     *
     * @param array $array
     * @return array
     */
    function remove_first_element($array): array
    {
        if (is_object($array)) {
            $array = get_object_vars($array);
        }

        unset($array[current(array_keys($array))]);

        return $array;
    }
}

if (! function_exists('remove_from_array')) {
    /**
     * Removes a key or keys from an array
     *
     * @param array $array
     * @param string $element
     * @param string $symbol
     * @param bool $return_string
     * @return mixed
     */
    function remove_from_array(
        $array,
        $element,
        $symbol = null,
        $return_string = false
    ): mixed {
        if (!is_array($array) && $symbol != null) {
            $array = strtoarr($symbol, $array);
        }

        if (is_array($array) && ($key = array_search($element, array_keys($array))) !== false) {
            unset($array[$element]);
        }

        if ($return_string == true) {
            return $array = arrtostr($symbol, $array);
        }

        return $array;
    }
}

if (! function_exists('remove_with_value')) {
    /**
     * Remove key and value from multidimensional array
     *
     * @param array $array
     * @param string $key
     * @param string $value
     * @return array
     */
    function remove_with_value($array, $key, $value)
    {

        foreach ($array as $innerKey => $innerArray) {
            if ($innerArray[$key] == $value) {
                unset($array[$innerKey]);
            }
        }

        return $array;
    }
}

if (! function_exists('array_keys_case_insensitive_value')) {
    function array_keys_case_insensitive_value(array $array, string $search_value): array
    {
        $matching_keys = [];
        $search_value_lower = strtolower($search_value);

        foreach ($array as $key => $value) {
            if (is_string($value) && strtolower($value) === $search_value_lower) {
                $matching_keys[] = $key;
            }
        }

        return $matching_keys;
    }
}

if (! function_exists('object_array')) {
    /**
     * Retrieve arrays with single objects
     * And their index (For specific table 
     * operations e.g user_roles)
     *
     * @param array $object_array
     * @param int|string $index
     * @return string|object|array
     */
    function object_array($object_array, $index)
    {
        if (!empty($object_array)) {
            return $object_array[0]->$index;
        }

        return '';
    }
}

if (! function_exists('arrayfy')) {

    /**
     * Convert an array-object and retrieve
     * as an array or generator
     *
     * @param object|array $object
     * @param bool $asGenerator
     * @param int $threshold
     * @return \Generator|array
     */
    function arrayfy($object, $asGenerator = false, $threshold = 1000)
    {
        if ($asGenerator) {
            $object = to_generator($object, $threshold);
        }

        if ($object instanceof \Generator) {
            return $object;
        }

        // Handles objects and converts them to arrays
        if (is_object($object)) {
            $array = [];
            foreach ($object as $key => $value) {
                $array[$key] = arrayfy($value); // Recursively call arrayfy for nested values
            }
            return $array;
        }

        // Handles arrays and ensures all nested items are converted
        if (is_array($object)) {
            $array = [];
            foreach ($object as $key => $value) {
                $array[$key] = arrayfy($value); // Recursively call arrayfy for each item
            }
            return $array;
        }

        // Handles other types, including simple data types and converting objects in a final attempt
        if (!is_array($object)) {
            return json_decode(json_encode($object), true);
        }

        throw new \Exception("Parameter must be an object or a supporting type", 1);
    }
}

if (! function_exists('objectify')) {

    /**
     * Convert an array and retrieve
     * as an object
     *
     * @param array $array
     * @param bool $natural
     * @param string|object $class
     * @return object
     */
    function objectify(array $array, $natural = false, $class = 'stdClass')
    {

        if ($natural) {

            $object = new $class;

            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    // Recursively convert nested arrays
                    $object->$key = objectify($value, true, $class);
                } else {
                    $object->$key = $value;
                }
            }

            return $object;
        }

        if (is_array($array)) {
            $array = json_encode($array, JSON_THROW_ON_ERROR);
            return json_decode($array, null, 512, JSON_THROW_ON_ERROR);
        }

        return (object)[]; // return empty object if condition not met

    }
}

if (! function_exists('to_object')) {
    /**
     * Alias of objectify()
     *
     * @param array $array
     * @param bool $natural
     * @param string|object $class
     * @return object
     */
    function to_object(array|object $array, $natural = false, $class = 'stdClass')
    {
        return objectify($array, $natural, $class);
    }
}

if (! function_exists('contains_object')) {
    function contains_object(array $array): bool
    {
        foreach ($array as $value) {
            if (is_object($value)) {
                return true; // Found an object, exit
            }
        }

        return false; // No object
    }
}

if (! function_exists('value')) {
    /**
     *  Return the default value of the given value
     *
     *  @param     mixed    $value
     *  @return    mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

// ------------------------------------------------------------------------

if (! function_exists('with')) {
    /**
     *  Return the given object (useful for chaining)
     *
     *  @param     mixed    $object
     *  @return    mixed
     */
    function with($object)
    {
        return $object;
    }
}

if (! function_exists('is_json')) {

    /**
     * Check if string is json
     * Mostly string should be a json string
     * 
     * @param string $string
     * @return bool
     */
    function is_json(string $json, int $depth = 512, int $flag = 0)
    {
        return json_validate($json, $depth, $flag);
    }
}

if (! function_exists('compare_json')) {
    /**
     * Compare two json objects
     *
     * Based on
     * https://stackoverflow.com/questions/34346952/compare-two-json-in-php
     * 
     * The second answer
     * 
     * @param  string $first_object
     * @param  string $second_object
     * @return bool
     */
    function compare_json($first_object, $second_object)
    {
        $match = json_decode($first_object) == json_decode($second_object);
        return $match ? true : false;
    }
}

if (! function_exists('to_array')) {
    /**
     * Alias of arrayfy()
     *
     * @param object|array $object
     * @param bool $asGenerator
     * @param int $threshold
     * @return \Generator|array
     */
    function to_array($object, $asGenerator = false, $threshold = 1000)
    {
        return arrayfy($object, $asGenerator, $threshold);
    }
}

if (! function_exists('to_generator')) {
    /**
     * Convert an array or object
     * to a generator
     *
     * @param mixed $data
     * @param int $threshold
     * @return \Generator|array|object
     */
    function to_generator($data, $threshold = 1000)
    {
        $count = 0;

        if (is_array($data)) {
            $count = count($data);
        }

        if (is_object($data)) {
            $count = count(get_object_vars($data));
        }

        if ($count > $threshold) {
            foreach ($data as $key => $value) {
                yield $key => $value;
            }
        } else {
            return $data;
        }
    }
}

if (! function_exists('collect_from')) {
    /**
     * Create collection from various data types
     *
     * @param mixed $data
     * @return \Base\Helpers\Arrayz
     */
    function collect_from(mixed $data): \Base\Helpers\Arrayz
    {
        return \Base\Helpers\Arrayz::from($data);
    }
}

if (! function_exists('collection_from_query')) {
    /**
     * Create collection from CodeIgniter query result
     *
     * @param mixed $query_result
     * @return \Base\Helpers\Arrayz
     */
    function collection_from_query(mixed $query_result): \Base\Helpers\Arrayz
    {
        return collect_from($query_result);
    }
}

if (! function_exists('make_list')) {
    /**
     * Create a simple list (beginner-friendly)
     *
     * @param mixed ...$items
     * @return \Base\Helpers\Arrayz
     */
    function make_list(mixed ...$items): \Base\Helpers\Arrayz
    {
        return arrayz($items);
    }
}

if (! function_exists('filter_list')) {
    /**
     * Filter a list by condition (beginner-friendly)
     *
     * @param mixed $list
     * @param callable $condition
     * @return array
     */
    function filter_list(mixed $list, callable $condition): array
    {
        return arrayz($list)->filter($condition)->values()->toArray();
    }
}

if (! function_exists('map_list')) {
    /**
     * Transform each item in a list (beginner-friendly)
     *
     * @param mixed $list
     * @param callable $transformer
     * @return array
     */
    function map_list(mixed $list, callable $transformer): array
    {
        return arrayz($list)->map($transformer)->toArray();
    }
}

if (! function_exists('find_in_list')) {
    /**
     * Find first item matching condition (beginner-friendly)
     *
     * @param mixed $list
     * @param callable $condition
     * @return mixed
     */
    function find_in_list(mixed $list, callable $condition): mixed
    {
        return arrayz($list)->first($condition);
    }
}

if (! function_exists('count_where')) {
    /**
     * Count items matching condition
     *
     * @param mixed $list
     * @param callable $condition
     * @return int
     */
    function count_where(mixed $list, callable $condition): int
    {
        return arrayz($list)->filter($condition)->count();
    }
}

if (! function_exists('list_contains')) {
    /**
     * Check if list contains value (beginner-friendly)
     *
     * @param mixed $list
     * @param mixed $value
     * @return bool
     */
    function list_contains(mixed $list, mixed $value): bool
    {
        return arrayz($list)->contains($value);
    }
}

if (! function_exists('get_column')) {
    /**
     * Extract a column from array of arrays/objects (beginner-friendly)
     *
     * @param mixed $data
     * @param string $column
     * @return array
     */
    function get_column(mixed $data, string $column): array
    {
        return arrayz($data)->pluck($column)->toArray();
    }
}

if (! function_exists('sort_list_by')) {
    /**
     * Sort list by a field (beginner-friendly)
     *
     * @param mixed $list
     * @param string $field
     * @param bool $ascending
     * @return array
     */
    function sort_list_by(mixed $list, string $field, bool $ascending = true): array
    {
        $direction = $ascending ? 'asc' : 'desc';
        return arrayz($list)->sortBy($field, $direction)->values()->toArray();
    }
}

if (! function_exists('group_list_by')) {
    /**
     * Group list by a field (beginner-friendly)
     *
     * @param mixed $list
     * @param string $field
     * @return array
     */
    function group_list_by(mixed $list, string $field): array
    {
        return arrayz($list)->groupBy($field)->map(fn($group) => $group->toArray())->toArray();
    }
}

if (! function_exists('take_from_list')) {
    /**
     * Take first n items from list (beginner-friendly)
     *
     * @param mixed $list
     * @param int $count
     * @return array
     */
    function take_from_list(mixed $list, int $count): array
    {
        return arrayz($list)->take($count)->toArray();
    }
}

if (! function_exists('skip_from_list')) {
    /**
     * Skip first n items from list (beginner-friendly)
     *
     * @param mixed $list
     * @param int $count
     * @return array
     */
    function skip_from_list(mixed $list, int $count): array
    {
        return arrayz($list)->skip($count)->values()->toArray();
    }
}

if (! function_exists('chunk_list')) {
    /**
     * Split list into smaller chunks (beginner-friendly)
     *
     * @param mixed $list
     * @param int $size
     * @return array
     */
    function chunk_list(mixed $list, int $size): array
    {
        return arrayz($list)->chunk($size)->map(fn($chunk) => $chunk->toArray())->toArray();
    }
}

/* ------------------------------- Date | Time | Format Functions ---------------------------------*/

if (! function_exists('now')) {
    // /**
    //  * Current timestamp
    //  * 
    //  * @return string 
    //  */
    // function now(string|bool $format = true)
    // {
    //     if ($format === true) {
    //         return NOW;
    //     }

    //     $now = new DateTime();
    //     return $now->format($format);

    // }

    //  /**
    //  * Get the current date and time.
    //  * 
    //  * @param string|bool $format The format string, or `true` to return a Unix timestamp.
    //  * @return int|DateTimeImmutable|string The Unix timestamp, an object, or a formatted string.
    //  */
    // function now(string|bool $format = true): int|DateTimeImmutable|string
    // {
    //     $now = new DateTimeImmutable('now');

    //     if ($format === true) {
    //         return $now->getTimestamp();
    //     }

    //     if (is_string($format)) {
    //         return $now->format($format);
    //     }

    //     // Default case: return the DateTimeImmutable object
    //     return $now;
    // }

    /**
     * Get the current date and time, optionally adjusting for a timezone or returning a specific format.
     * 
     * @param string|bool|null|array $param Can be:
     *                                 - `null` (default): Returns DateTimeImmutable object using default timezone.
     *                                 - `true`: Returns Unix timestamp using default timezone.
     *                                 - `string` (format): Returns formatted string using default timezone.
     *                                 - `string` (timezone name e.g., 'America/New_York'): Returns DateTimeImmutable object for that timezone.
     *                                 - `array` of ['format' => string, 'timezone' => string]: Returns formatted string for that timezone.
     *                                 - `array` of ['timestamp' => true, 'timezone' => string]: Returns Unix timestamp for that timezone.
     * @return int|DateTimeImmutable|string The Unix timestamp, DateTimeImmutable object, or formatted string.
     */
    function now(string|bool|null|array $param = null): int|DateTimeImmutable|string
    {
        // Determine the timezone to use
        $timezone = date_default_timezone_get(); // Start with PHP's default

        if (is_string($param) && !empty($param) && !is_valid_date_format($param)) { // Check if param is likely a timezone string
            // Attempt to treat the string as a timezone name
            try {
                $dtz = new DateTimeZone($param);
                $timezone = $param;
                $currentDt = new DateTimeImmutable('now', $dtz);
                // If the parameter was just a timezone string, return the object for that timezone
                return $currentDt;
            } catch (Exception $e) {
                // If it's not a valid timezone, assume it's a format string
                // The current $timezone (PHP's default) will be used for the currentDt creation below
            }
        }

        // Handle array parameter for advanced usage (timezone + format/timestamp)
        if (is_array($param)) {
            if (isset($param['timezone']) && !empty($param['timezone'])) {
                try {
                    $dtz = new DateTimeZone($param['timezone']);
                    $timezone = $param['timezone'];
                } catch (Exception $e) {
                    // Invalid timezone in array, fall back to default
                }
            }
        }

        // Create the DateTimeImmutable object for the determined timezone
        $currentDt = new DateTimeImmutable('now', new DateTimeZone($timezone));

        // Handle return based on $param or array settings
        if ($param === true || (is_array($param) && isset($param['timestamp']) && $param['timestamp'] === true)) {
            // Return Unix timestamp
            return $currentDt->getTimestamp();
        }

        if (is_string($param) && !empty($param) && is_valid_date_format($param)) {
            // Return formatted string
            return $currentDt->format($param);
        }

        if (is_array($param) && isset($param['format']) && is_string($param['format'])) {
            // Return formatted string from array
            return $currentDt->format($param['format']);
        }

        // Default: return the DateTimeImmutable object
        return $currentDt;
    }

    // Helper function to loosely check if a string is a date format
    // This is a basic check; real validation might be more complex
    function is_valid_date_format(string $str): bool
    {

        // Look for common format characters, exclude 
        // common timezone string parts
        $commonFormatChars = [
            'Y',
            'm',
            'd',
            'H',
            'i',
            's',
            'A',
            'a',
            'F',
            'M',
            'j',
            'D',
            'l',
            'P',
            'Z',
            'T'
        ];

        foreach ($commonFormatChars as $char) {
            if (str_contains($str, $char)) {
                return true;
            }
        }

        // Simple check to avoid confusion with 
        // some timezone names (e.g., "GMT")
        // This is not foolproof but helps.
        if (
            in_array(
                strtolower($str),
                ['gmt', 'utc', 'z']
            )
        ) {
            return false;
        }

        return false;
    }
}

if (! function_exists('timezone')) {
    /**
     * Timezone from TIMEZONE Constants
     * in the config/constants.php file
     * 
     * @return string 
     */
    function timezone()
    {
        return TIMEZONE;
    }
}

if (! function_exists('datetime')) {
    /**
     * Datetime from DATETIME Constants
     * in the config/constants.php file
     * 
     * @return string 
     */
    function datetime()
    {
        return DATETIME;
    }
}

if (! function_exists('today')) {
    /**
     * Today from TODAY Constants
     * in the config/constants.php file
     * 
     * @return string 
     */
    function today()
    {
        return TODAY;
    }
}

if (! function_exists('system_default_timezone')) {
    /**
     * Default Timezone from DEFAULT_TIMEZONE Constants
     * in the config/constants.php file
     * 
     * @return string 
     */
    function system_default_timezone()
    {
        return DEFAULT_TIMEZONE;
    }
}

if (! function_exists('arrange_date')) {
    /**
     * This takes out forward slashes and
     * replaces them with hyphens
     * 
     * @param string $date
     * @return string
     */
    function arrange_date($date)
    {
        if (strstr($date, '/')) {
            return $date = str_replace('/', '-', $date);
        }

        return $date;
    }
}

if (! function_exists('real_date')) {
    /**
     * Output a human readable date
     *
     * @param string $date
     * @param string $format
     * @return string
     */
    function real_date($date, $format = null)
    {
        if ($date == "0000-00-00 00:00:00") {
            return '';
        } elseif ($date == "0000-00-00") {
            return '';
        } else {
            if (!empty($format)) {
                return format_date($format, $date);
            } else {
                return format_date('jS F, Y', $date);
            }
        }
    }
}

if (! function_exists('correct_date')) {
    /**
     * Take date and format it in Y-m-d
     * This fixes a date and can be stored
     * and used easily
     * 
     * @param string $date
     * @return string
     */
    function correct_date($date)
    {
        if ($date == "0000-00-00 00:00:00") {
            return '';
        } elseif ($date == "0000-00-00") {
            return '';
        } else {
            return format_date('Y-m-d', $date);
        }
    }
}

if (! function_exists('correct_datetime')) {
    /**
     * Take datetime and format it in Y-m-d H:i:a
     * This fixes a datetime and can be stored
     * and used easily
     *
     * @param string $date
     * @return string
     */
    function correct_datetime($date)
    {
        if ($date == "0000-00-00 00:00:00") {
            return '';
        } elseif ($date == "0000-00-00") {
            return '';
        } else {
            return format_date('Y-m-d H:i:a', $date);
        }
    }
}

if (! function_exists('real_time')) {
    /**
     * Take date and format it in H:i:a
     *
     * @param string $date
     * @return string
     */
    function real_time($date, $withSeconds = false)
    {
        if ($date == "0000-00-00 00:00:00") {
            return '';
        } elseif ($date == "0000-00-00") {
            return '';
        }

        if ($withSeconds) {
            return format_date('H:i:s a', $date);
        }

        return format_date('H:i a', $date);
    }
}

if (! function_exists('format_date')) {
    /**
     * Take date and set a custom date format
     *
     * @param string $format
     * @param string $date
     * @return string
     */
    function format_date($format, $date)
    {
        return date($format, strtotime($date));
    }
}

if (! function_exists('time_difference')) {
    /**
     * Calculate time difference
     *
     * @param string $start_date
     * @param string $end_date
     * @return string
     */
    function time_difference($start_datetime, $end_datetime)
    {
        $start = date_create($start_datetime);
        $end = date_create($end_datetime);

        // difference between two dates or times
        $interval = date_diff($start, $end);

        $minutes   = $interval->format('%i');
        $seconds   = $interval->format('%s');
        $hours     = $interval->format('%h');
        $months    = $interval->format('%m');
        $days      = $interval->format('%d');
        $years     = $interval->format('%y');

        // get time difference
        if ($interval->format('%i%h%d%m%y') == "00000") {
            return $seconds . " Seconds";
        }

        if ($interval->format('%h%d%m%y') == "0000") {
            return $minutes . " Minutes";
        }

        if ($interval->format('%d%m%y') == "000") {
            return $hours . " Hours";
        }

        if ($interval->format('%m%y') == "00") {
            return $days . " Days";
        }

        if ($interval->format('%y') == "0") {
            return $months . " Months";
        }

        return $years . " Years";
    }
}

if (! function_exists('date_difference')) {
    /**
     * Calculate date difference
     * return interval as object
     * 
     * @param string $start_date
     * @param string $end_date
     * @return mixed
     */
    function date_difference($start_date, $end_date)
    {
        $start_date = date_create($start_date);
        $end_date = date_create($end_date);

        //difference between two dates
        $interval = date_diff($start_date, $end_date);

        return $interval;
    }
}

if (! function_exists('date_plus_day')) {
    /**
     * Add days to a given date
     *
     * @param string $date
     * @param int $days
     * @param string $format
     * @return string
     */
    function date_plus_day($date, $days, $format = null)
    {
        if ($format != null) {
            return date($format, strtotime($date . ' + ' . $days . 'days'));
        } else {
            return date('Y-m-d', strtotime($date . ' + ' . $days . 'days'));
        }
    }
}

if (! function_exists('date_minus_day')) {
    /**
     * Subtract days from a given date
     *
     * @param string $date
     * @param int $days
     * @param string $format
     * @return string
     */
    function date_minus_day($date, $days, $format = null)
    {
        if ($format != null) {
            return date($format, strtotime($date . ' - ' . $days . 'days'));
        } else {
            return date('Y-m-d', strtotime($date . ' - ' . $days . 'days'));
        }
    }
}

if (! function_exists('time_ago')) {
    /**
     * Time ago
     *
     * @param mixed $datetime
     * @param bool $show_ago
     * @return string
     */
    function time_ago($datetime, $show_ago = false)
    {

        $time_difference = time() - strtotime($datetime);

        $different_times = [
            12 * 30 * 24 * 60 * 60  =>  'year',
            30 * 24 * 60 * 60       =>  'month',
            24 * 60 * 60            =>  'day',
            60 * 60                 =>  'hour',
            60                      =>  'minute',
            1                       =>  'second'
        ];

        foreach ($different_times as $seconds => $period) {
            $derived_time = $time_difference / $seconds;

            if ($derived_time >= 1) {
                $time = round($derived_time);
                return ($show_ago)
                    ? $time . ' ' . $period . ($time > 1 ? 's' : '') . ' ago'
                    // 'about ' . $time . ' ' . $period . ( $time > 1 ? 's' : '' ) . ' ago';
                    : $time . ' ' . $period . ($time > 1 ? 's' : '');
            }
        }

        return 'just now';
    }
}

if (! function_exists('travel')) {
    /**
     * Travel through time
     *
     * @return \Base\Helpers\TimeTravel
     */
    function travel(
        $timezone = null,
        $autoFormat = false,
        $format = 'Y-m-d H:i:s'
    ) {
        return new Base\Helpers\TimeTravel(
            $timezone,
            $autoFormat,
            $format
        );
    }
}

// Create a quick formatter helper
if (!function_exists('quick_travel')) {
    /**
     * Quick travel
     * @param mixed $format
     * @return Base\Helpers\TimeTravel
     */
    function quick_travel($format = 'Y-m-d H:i:s')
    {
        return new Base\Helpers\TimeTravel(
            null,
            true,
            $format
        );
    }
}

if (! function_exists('format')) {
    /**
     * Format helper function for webby
     *
     * @param string $path
     * @return \Base\Helpers\Format
     */
    function format()
    {
        return new \Base\Helpers\Format;
    }
}

/* ------------------------------- Security Functions ---------------------------------*/

if (! function_exists('hash_algo')) {
    /**
     * A wrapper for php hash function
     *
     * @param string $algorithm
     * @param string $string
     * @return string
     */
    function hash_algo($algorithm, $string)
    {
        return hash($algorithm, $string);
    }
}

if (! function_exists('escape')) {

    /**
     * Escape HTML entities in a string
     *
     * @param string $value
     * @return string
     */
    function escape($value)
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (! function_exists('csrf')) {
    /**
     * Creates a CSRF hidden form input
     *
     * @return void
     */
    function csrf()
    {
        echo '<input type="hidden" name="' . ci()->security->get_csrf_token_name() . '" value="' . ci()->security->get_csrf_hash() . '">';
    }
}

if (! function_exists('csrf_token')) {
    /**
     * Grab CSRF token name
     *
     * @return string
     */
    function csrf_token()
    {
        return ci()->security->get_csrf_token_name();
    }
}

if (! function_exists('csrf_hash')) {
    /**
     * Grab CSRF hash
     *
     * @return string
     */
    function csrf_hash()
    {
        return ci()->security->get_csrf_hash();
    }
}

if (! function_exists('honeypot')) {
    /**
     * Generate a honeypot field
     *
     * @return string
     */
    function honeypot($name = '', $template = '', $container = '')
    {
        if (!config('honeypot')['enabled']) {
            return '';
        }

        $data = [
            'name' => $name ?: config('honeypot')['name'],
            'template' => $template ?: config('honeypot')['template'],
            'container' => $container ?: config('honeypot')['container'],
            'honeyfield' => config('honeypot')['timefield']
        ];

        if (config('honeypot')['time_check_enabled']) {
            $data['honeytime'] = base64_encode(time());
            session('honeytime', $data['honeytime']);
            $data['timename'] = config('honeypot')['timename'];
            $data['honeyfield'] = str_replace('{timename}', $data['timename'], $data['honeyfield']);
            $data['honeyfield'] = str_replace('{honeytime}', $data['honeytime'], $data['honeyfield']);
        } else {
            $data['honeyfield'] = '';
        }

        if (!empty($data['container'])) {
            $output = str_replace('{name}', $data['name'], $data['template']);
            $output = str_replace('{template}', $output, $output);
            $output = str_replace(['{template}', '{honeyfield}'], [$output, $data['honeyfield']], $data['container']);
            return $output;
        }

        $output = str_replace('{name}', $data['name'], $data['template']);
        return $output;
    }
}

if (! function_exists('honey_check')) {
    /**
     * Checks if the honeypot field 
     * is not filled
     *
     * @param string $honeypot
     * @return bool
     */
    function honey_check($honeypot = '')
    {
        if (!config('honeypot')['enabled']) {
            return true;
        }

        if (trim((string)$honeypot) != '') {
            return false;
        }

        return true;
    }
}

if (! function_exists('honey_time')) {
    /**
     * Checks the time it takes a 
     * form to be submitted
     *
     * @param string $field
     * @param int $time
     * @return bool
     */
    function honey_time($field = '', $time = '')
    {
        if (!config('honeypot')['enabled'] || !config('honeypot')['time_check_enabled']) {
            return true;
        }

        $honeytime = base64_decode((string)session('honeytime'));

        $time =  (int) $time ?: (int) config('honeypot')['time'];

        $seconds = time() - (int)$honeytime;

        if ($seconds < $time) {
            return false;
        } else {
            return true;
        }
    }
}

if (! function_exists('honey_style')) {
    /**
     * Styles the honey_pot container
     *
     * @param string $custom_style
     * @return string
     */
    function honey_style($custom_style = '')
    {
        if (!config('honeypot')['enabled']) {
            return '';
        }

        if (empty($custom_style)) {
            return config('honeypot')['style'];
        }

        return $custom_style;
    }
}

if (! function_exists('encrypt')) {
    /**
     *  Encrypt a given string
     *
     *  @param     string    $value
     *  @return    string
     */
    function encrypt($value)
    {
        return app('encryption')->encrypt($value);
    }
}

if (! function_exists('decrypt')) {
    /**
     *  Decrypt a given string
     *
     *  @param     string    $value
     *  @return    string
     */
    function decrypt($value)
    {
        return app('encryption')->decrypt($value);
    }
}

if (! function_exists('clean')) {
    /**
     *  Clean string from XSS
     *
     *  @param     string|array    $str
     *  @param     bool    $is_image
     *  @return    string|array
     */
    function clean($str, $is_image = false)
    {
        ci()->load->helper('security');
        return xss_clean($str, $is_image);
    }
}

if (! function_exists('cleanxss')) {
    /**
     * Prevents XXS Attacks
     *
     * @param string $input
     * @return string
     */
    function cleanxss($input)
    {
        $search = [
            '@&lt;script[^&gt;]*?&gt;.*?&lt;/script&gt;@si', // Strip out javascript
            '@&lt;[\/\!]*?[^&lt;&gt;]*?&gt;@si', // Strip out HTML tags
            '@&lt;style[^&gt;]*?&gt;.*?&lt;/style&gt;@siU', // Strip style tags properly
            '@&lt;![\s\S]*?--[ \t\n\r]*&gt;@', // Strip multi-line comments
        ];

        $inputx = preg_replace($search, '', $input);
        $inputx = trim($inputx);
        $inputx = stripslashes($inputx);
        $inputx = stripslashes($inputx);

        return clean($inputx);
    }
}

if (! function_exists('ping_url')) {
    /**
     * Ping url to check
     * if it is online or exists
     *
     *  @param     string  $url
     *  @return    bool
     */
    function ping_url(string $url): bool
    {
        $url = parse_url($url);

        if (!isset($url["host"])) {
            return false;
        }

        return !(gethostbyname($url["host"]) == $url["host"]);
    }
}

if (! function_exists('filter_url')) {
    /**
     *  filter url
     *
     *  @param     string    $url
     *  @return    string
     */
    function filter_url($url)
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (! function_exists('is_url')) {

    /**
     * check if string is url
     *
     * @param string $url
     * @param bool $is_live
     * @param bool $return
     * @return bool|string
     */
    function is_url($url, $is_live = false, $return = false)
    {
        $url = filter_url($url);

        $url = filter_var($url, FILTER_VALIDATE_URL);

        if ($return && $is_live) {
            $live = ping_url($url);
            return $live ? $url : $live;
        }

        if ($return && $url) {
            return $url;
        }

        if ($is_live) {
            return ping_url($url);
        }

        if ($url) {
            return true;
        }

        return false;
    }
}

if (! function_exists('is_email')) {
    /**
     * Check if email is valid
     *
     * @param string $email
     * @return bool
     */
    function is_email($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            return false;
        }
    }
}

if (! function_exists('is_domain')) {
    /**
     * Checks if an email is from 
     * a given domain e.g. @webby
     *
     * @param string $email
     * @param string $domain
     * @return bool
     */
    function is_domain($email, $domain)
    {
        if (preg_match("/$domain/", $email)) {
            return true;
        } else {
            return false;
        }
    }
}

if (! function_exists('is_email_injected')) {
    /**
     * validate against any email injection attempts
     *
     * @param string $email
     * @return bool
     */
    function is_email_injected($email)
    {
        $injections = [
            '(\n+)',
            '(\r+)',
            '(\t+)',
            '(%0A+)',
            '(%0D+)',
            '(%08+)',
            '(%09+)',
        ];

        $inject = join('|', $injections);
        $inject = "/$inject/i";
        if (preg_match($inject, $email)) {
            return true;
        } else {
            return false;
        }
    }
}

if (! function_exists('is_email_valid')) {
    /**
     * checks whether the email address is valid
     *
     * @param string $email
     * @return bool
     */
    function is_email_valid($email)
    {
        $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i";

        if (preg_match($pattern, $email)) {
            return true;
        } else {
            return false;
        }
    }
}

<?php

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */

/**
 * CodeIgniter String Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/userguide3/helpers/string_helper.html
 */

// ------------------------------------------------------------------------

if (! function_exists('trim_slashes')) {
	/**
	 * Trim Slashes
	 *
	 * Removes any leading/trailing slashes from a string:
	 *
	 * /this/that/theother/
	 *
	 * becomes:
	 *
	 * this/that/theother
	 *
	 * @todo	Remove in version 3.1+.
	 * @deprecated	3.0.0	This is just an alias for PHP's native trim()
	 *
	 * @param	string
	 * @return	string
	 */
	function trim_slashes($str)
	{
		return trim($str, '/');
	}
}

// ------------------------------------------------------------------------

if (! function_exists('strip_slashes')) {
	/**
	 * Strip Slashes
	 *
	 * Removes slashes contained in a string or in an array
	 *
	 * @param	mixed	string or array
	 * @return	mixed	string or array
	 */
	function strip_slashes($str)
	{
		if (! is_array($str)) {
			return stripslashes($str);
		}

		foreach ($str as $key => $val) {
			$str[$key] = strip_slashes($val);
		}

		return $str;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('strip_quotes')) {
	/**
	 * Strip Quotes
	 *
	 * Removes single and double quotes from a string
	 *
	 * @param	string
	 * @return	string
	 */
	function strip_quotes($str)
	{
		return str_replace(['"', "'"], '', $str);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('quotes_to_entities')) {
	/**
	 * Quotes to Entities
	 *
	 * Converts single and double quotes to entities
	 *
	 * @param	string
	 * @return	string
	 */
	function quotes_to_entities($str)
	{
		return str_replace(["\'", "\"", "'", '"'], ["&#39;", "&quot;", "&#39;", "&quot;"], $str);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('reduce_double_slashes')) {
	/**
	 * Reduce Double Slashes
	 *
	 * Converts double slashes in a string to a single slash,
	 * except those found in http://
	 *
	 * http://www.some-site.com//index.php
	 *
	 * becomes:
	 *
	 * http://www.some-site.com/index.php
	 *
	 * @param	string
	 * @return	string
	 */
	function reduce_double_slashes($str)
	{
		return preg_replace('#(^|[^:])//+#', '\\1/', $str);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('reduce_multiples')) {
	/**
	 * Reduce Multiples
	 *
	 * Reduces multiple instances of a particular character.  Example:
	 *
	 * Fred, Bill,, Joe, Jimmy
	 *
	 * becomes:
	 *
	 * Fred, Bill, Joe, Jimmy
	 *
	 * @param	string
	 * @param	string	the character you wish to reduce
	 * @param	bool	true/false - whether to trim the character from the beginning/end
	 * @return	string
	 */
	function reduce_multiples($str, $character = ',', $trim = false)
	{
		$str = preg_replace('#' . preg_quote($character, '#') . '{2,}#', $character, $str);
		return ($trim === true) ? trim($str, $character) : $str;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('random_string')) {
	/**
	 * Create a "Random" String
	 *
	 * @param	string	type of random string.  basic, alpha, alnum, numeric, nozero, unique, md5, encrypt and sha1
	 * @param	int	number of characters
	 * @return	mixed
	 */
	function random_string($type = 'alnum', $len = 8)
	{
		switch ($type) {
			case 'basic':
				return mt_rand();
			case 'alnum':
			case 'numeric':
			case 'nozero':
			case 'alpha':
				switch ($type) {
					case 'alpha':
						$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
						break;
					case 'alnum':
						$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
						break;
					case 'numeric':
						$pool = '0123456789';
						break;
					case 'nozero':
						$pool = '123456789';
						break;
				}
				return substr(str_shuffle(str_repeat($pool, ceil($len / strlen($pool)))), 0, $len);
			case 'unique': // todo: remove in 3.1+
			case 'md5':
				return md5(uniqid(mt_rand()));
			case 'encrypt': // todo: remove in 3.1+
			case 'sha1':
				return sha1(uniqid(mt_rand(), true));
		}
	}
}

// ------------------------------------------------------------------------

if (! function_exists('increment_string')) {
	/**
	 * Add's _1 to a string or increment the ending number to allow _2, _3, etc
	 *
	 * @param	string	required
	 * @param	string	What should the duplicate number be appended with
	 * @param	string	Which number should be used for the first dupe increment
	 * @return	string
	 */
	function increment_string($str, $separator = '_', $first = 1)
	{
		preg_match('/(.+)' . preg_quote($separator, '/') . '([0-9]+)$/', $str, $match);
		return isset($match[2]) ? $match[1] . $separator . ($match[2] + 1) : $str . $separator . $first;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('alternator')) {
	/**
	 * Alternator
	 *
	 * Allows strings to be alternated. See docs...
	 *
	 * @param	string (as many parameters as needed)
	 * @return	string
	 */
	function alternator()
	{
		static $i;

		if (func_num_args() === 0) {
			$i = 0;
			return '';
		}

		$args = func_get_args();
		return $args[($i++ % count($args))];
	}
}

// ------------------------------------------------------------------------

if (! function_exists('camel_case')) {
	/**
	 *  Convert a string to camel case
	 *
	 *  @param     string    $str
	 *  @return    string
	 */
	function camel_case($str)
	{
		static $camel_cache = [];

		if (isset($camel_cache[$str])) {
			return $camel_cache[$str];
		}

		return $camel_cache[$str] = lcfirst(studly_case($str));
	}
}

// ------------------------------------------------------------------------



if (! function_exists('charset')) {
	/**
	 *  Get the accepted character sets or a particular character set
	 *
	 *  @param     string         $key
	 *  @return    array|boolean
	 */
	function charset($key = NULL)
	{
		if (is_null($key)) {
			return app('user_agent')->charsets();
		}

		return app('user_agent')->accept_charset($key);
	}
}

if (! function_exists('kebab_case')) {
	/**
	 *  Convert a string to kebab case
	 *
	 *  @param     string    $str
	 *  @return    string
	 */
	function kebab_case($str)
	{
		return snake_case($str, '-');
	}
}

// ------------------------------------------------------------------------

if (! function_exists('length')) {
	/**
	 *  Return the length of the given string
	 *
	 *  @param     string    $value
	 *  @param     string    $encoding
	 *  @return    integer
	 */
	function length($value, $encoding = NULL)
	{
		if (! is_null($encoding)) {
			return mb_strlen($value, $encoding);
		}

		return mb_strlen($value);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('repeater')) {
	/**
	 * Repeater function
	 *
	 * @todo	Remove in version 3.1+.
	 * @deprecated	3.0.0	This is just an alias for PHP's native str_repeat()
	 *
	 * @param	string	$data	String to repeat
	 * @param	int	$num	Number of repeats
	 * @return	string
	 */
	function repeater($data, $num = 1)
	{
		return ($num > 0) ? str_repeat($data, $num) : '';
	}
}

// ------------------------------------------------------------------------

if (! function_exists('slug_case')) {
	/**
	 *  Convert the given string to slug case
	 *
	 *  @param     string     $value
	 *  @param     string     $separator
	 *  @param     boolean    $lowercase
	 *  @return    string
	 */
	function slug_case($str, $separator = '-', $lowercase = true)
	{
		$str = helper('text.convert_accented_characters', [$str]);

		return helper('url.url_title', [$str, $separator, $lowercase]);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('snake_case')) {
	/**
	 *  Convert a string to snake case
	 *
	 *  @param     string    $str
	 *  @param     string    $delimiter
	 *  @return    string
	 */
	function snake_case($str, $delimiter = '_')
	{
		static $snake_cache = [];
		$key = $str . $delimiter;

		if (isset($snake_cache[$key])) {
			return $snake_cache[$key];
		}

		if (! ctype_lower($str)) {
			$str = preg_replace('/\s+/u', '', $str);
			$str = preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $str);
		}

		return $snake_cache[$key] = mb_strtolower($str, 'UTF-8');
	}
}

// ------------------------------------------------------------------------

if (! function_exists('str_after')) {
	/**
	 *  Return the remainder of a string after a given value
	 *
	 *  @param     string    $str
	 *  @param     string    $search
	 *  @return    string
	 */
	function str_after($str, $search)
	{
		if (! is_bool(strpos($str, $search))) {
			return substr($str, strpos($str, $search) + strlen($search));
		}

		return $str;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('str_after_last')) {
	/**
	 *  Return the remainder of a string after the last given value
	 *
	 *  @param     string    $str
	 *  @param     string    $search
	 *  @return    string
	 */
	function str_after_last($str, $search)
	{
		if (! is_bool(strrevpos($str, $search))) {
			return substr($str, strrevpos($str, $search) + strlen($search));
		}

		return $str;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('str_before')) {
	/**
	 *  Return the string before the given value
	 *
	 *  @param     string    $str
	 *  @param     string    $search
	 *  @return    string
	 */
	function str_before($str, $search)
	{
		return substr($str, 0, strpos($str, $search));
	}
}

// ------------------------------------------------------------------------

if (! function_exists('str_before_last')) {
	/**
	 *  Return the string before the last given value
	 *
	 *  @param     string    $str
	 *  @param     string    $search
	 *  @return    string
	 */
	function str_before_last($str, $search)
	{
		return substr($str, 0, strrevpos($str, $search));
	}
}

// ------------------------------------------------------------------------

if (! function_exists('str_between')) {
	/**
	 *  Return the string between the given values
	 *
	 *  @param     string    $str
	 *  @param     string    $search1
	 *  @param     string    $search2
	 *  @return    string
	 */
	function str_between($str, $search1, $search2)
	{
		return str_before(str_after($str, $search1), $search2);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('str_between_last')) {
	/**
	 *  Return the string between the last given values
	 *
	 *  @param     string    $str
	 *  @param     string    $search1
	 *  @param     string    $search2
	 *  @return    string
	 */
	function str_between_last($str, $search1, $search2)
	{
		return str_after_last(str_before_last($str, $search2), $search1);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('str_contains')) {
	/**
	 *  Determine if a given string contains a given substring
	 *
	 *  @param     string          $haystack
	 *  @param     string|array    $needles
	 *  @return    boolean
	 */
	function str_contains($haystack, $needles)
	{
		foreach ((array) $needles as $needle) {
			if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
				return true;
			}
		}

		return false;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('str_finish')) {
	/**
	 *  Cap a string with a single instance of a given value
	 *
	 *  @param     string    $str
	 *  @param     string    $cap
	 *  @return    string
	 */
	function str_finish($str, $cap)
	{
		$quoted = preg_quote($cap, '/');

		return preg_replace('/(?:' . $quoted . ')+$/u', '', $str) . $cap;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('str_is')) {
	/**
	 *  Determine if a given string matches a given pattern
	 *
	 *  @param     string    $pattern
	 *  @param     string    $value
	 *  @return    boolean
	 */
	function str_is($pattern, $value)
	{
		if ($pattern == $value) {
			return true;
		}

		$pattern = preg_quote($pattern, '#');

		//	Asterisks are translated into zero-or-more regular expression wildcards
		//	to make it convenient to check if the strings starts with the given
		//	pattern such as "library/*", making any string check convenient.
		$pattern = str_replace('\*', '.*', $pattern);

		return (bool) preg_match('#^' . $pattern . '\z#u', $value);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('str_limit')) {
	/**
	 *  Ellipsize a string
	 *
	 *  @param     string     $str
	 *  @param     integer    $max_length
	 *  @param     integer    $position
	 *  @param     string     $ellipsis
	 *  @return    string
	 */
	function str_limit($str, $max_length = 100, $position = 1, $ellipsis = '&hellip;')
	{
		return helper('text.ellipsize', [$str, $max_length, $position, $ellipsis]);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('str_random')) {
	/**
	 *  Create a "Random" String
	 *
	 *  @param     integer    $length
	 *  @param     string     $type
	 *  @return    string
	 */
	function str_random($length = 16, $type = 'alnum')
	{
		return helper('string.random_string', [$type, $length]);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('int_random')) {
	/**
	 *  Create a "Random" Integer
	 *
	 *  @param     integer    $length
	 *  @param     string     $type
	 *  @return    string
	 */
	function int_random($length = 6, $str = false)
	{
		if (is_bool($str)) {
			$chars = ($str) ? '123456789ABCDEFGHJKLMNPQRSTUVWXYZ' : '0123456789';
		} else {
			$chars = $str;
		}

		$clen   = strlen($chars) - 1;
		$id  = '';
		for ($i = 0; $i < $length; $i++) {
			$id .= $chars[mt_rand(0, $clen)];
		}
		return ($id);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('str_replace_array')) {
	/**
	 *  Replace a given value in the string sequentially with an array
	 *
	 *  @param     string    $search
	 *  @param     array     $replace
	 *  @param     string    $subject
	 *  @return    string
	 */
	function str_replace_array($search, array $replace, $subject)
	{
		foreach ($replace as $value) {
			$subject = preg_replace('/' . $search . '/', $value, $subject, 1);
		}

		return $subject;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('strrevpos')) {
	/**
	 *  Find the position of the last occurrence of a substring in a string
	 *
	 *  @param     string         $haystack
	 *  @param     string         $needle
	 *  @return    string|boolean
	 */
	function strrevpos($haystack, $needle)
	{
		$revpos = strpos(strrev($haystack), strrev($needle));

		if ($revpos !== false) {
			return strlen($haystack) - $revpos - strlen($needle);
		}

		return false;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('studly_case')) {
	/**
	 *  Convert a string to studly caps case
	 *
	 *  @param     string    $str
	 *  @return    string
	 */
	function studly_case($str)
	{
		static $studly_cache = [];
		$key = $str;

		if (isset($studly_cache[$key])) {
			return $studly_cache[$key];
		}

		$value = ucwords(str_replace(['-', '_'], ' ', $str));

		return str_replace(' ', '', $value);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('title_case')) {
	/**
	 *  Convert the given string to title case
	 *
	 *  @param     string    $str
	 *  @return    string
	 */
	function title_case($str)
	{
		return mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
	}
}

// ------------------------------------------------------------------------

if (! function_exists('upper_case')) {
	/**
	 *  Convert the given string to upper case
	 *
	 *  @param     string    $str
	 *  @return    string
	 */
	function upper_case($str)
	{
		return strtoupper($str);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('lower_case')) {
	/**
	 *  Convert the given string to lower case
	 *
	 *  @param     string    $str
	 *  @return    string
	 */
	function lower_case($str)
	{
		return strtolower($str);
	}
}

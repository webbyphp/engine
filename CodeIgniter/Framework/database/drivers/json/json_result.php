<?php

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
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
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.3.0
 * @filesource
 */

/**
 * JSON Result Class
 *
 * This class extends the parent result class: CI_DB_result
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		EllisLab Dev Team
 * @author		Kwame Oteng Appiah-Nti
 * @copyright	Copyright (c) 2024, Developer Kwame, Inc.
 * @since		Version 2.13.0
 * @link		TBA
 * @todo improve on implementation
 */
class CI_DB_json_result extends CI_DB_result
{

    /**
     * Number of rows in the result set
     *
     * @access	public
     * @return	int
     */
    function num_rows()
    {
        return (bool)true;
    }

    // --------------------------------------------------------------------

    /**
     * Number of fields in the result set
     *
     * @access	public
     * @return	int
     */
    function num_fields()
    {
        return (bool)true;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch Field Names
     *
     * Generates an array of column names
     *
     * @access	public
     * @return	array
     */
    function list_fields()
    {
        return [];
    }

    // --------------------------------------------------------------------

    /**
     * Field data
     *
     * Generates an array of objects containing field meta-data
     *
     * @access	public
     * @return	array
     */
    function field_data()
    {
        return [];
    }

    // --------------------------------------------------------------------

    /**
     * Free the result
     *
     * @return	null
     */
    function free_result()
    {
        return null;
    }

    // --------------------------------------------------------------------

    /**
     * Data Seek
     *
     * Moves the internal pointer to the desired offset.  We call
     * this internally before fetching results to make sure the
     * result set starts at zero
     *
     * @access	private
     * @return	array
     */
    function _data_seek($n = 0)
    {
        return [];
    }

    // --------------------------------------------------------------------

    /**
     * Result - associative array
     *
     * Returns the result set as an array
     *
     * @access	private
     * @return	array
     */
    function _fetch_assoc()
    {
        return [];
    }

    // --------------------------------------------------------------------

    /**
     * Result - object
     *
     * Returns the result set as an object
     *
     * @access	private
     * @return	object
     */
    protected function _fetch_object($class_name = 'stdClass')
    {
        return new $class_name();
    }
}

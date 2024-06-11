<?php

namespace Base\Seeder;

use Base\Console\ConsoleColor;

/**
* Base Seeder class for Webby.
*
* This class provides a simple implementation for seeding
* the database in Webby. It is designed to be extended
* by other seeders and provides a common interface
* for running other seeders.
*
* @author Kwame Oteng Appiah-Nti <developerkwame@gmail.com> (Developer Kwame)
*/
class Seeder
{
	/**
	* The Webby application instance.
	*
	* @var object
	*/
	private $app;

	/**
	* The database connection instance.
	*
	* @var object
	*/
	protected $db;

	/**
	* The database forge instance.
	*
	* @var object
	*/
	protected $dbforge;

	/**
	* The table variable.
	*
	* @var string
	*/
	protected $table = '';

	protected $defaultString = 'Running db seed...';

	protected $seeders = [];

	/**
	 * The colors used for the console output.
	 */
	public const string INFO = 'cyan';
	public const string SUCCESS = 'green';
	public const string ERROR = 'red';
	public const string WARNING = 'yellow';

	/**
	* Constructor for Seeder.
	*
	* It gets the Webby application instance,
	* uses the database and the database forge.
	*
	* @return void
	*/
	public function __construct() 
	{
		// Get the Webby application instance
		$this->app = app();
		// Use the database
		$this->app->use->database();
		// Use the database forge
		$this->app->use->dbforge();

		// Get the database connection and database forge instances
		$this->db = $this->app->db;
		$this->dbforge = $this->app->dbforge;
	}

    public function tableExists()
	{
		if ($this->db->table_exists($this->table) === false) {
            return false;
		}

        return true;
	}

    public function tableName()
    {
        return $this->table;
    }

	public function truncate()
	{
		return $this->db->truncate($this->table);
	}

	protected function message($text, $color = 'green', $times = 1, $nextline = true)
    {
        if ($nextline) {
            return ConsoleColor::{$color}($text) . $this->nextline($times);
        }

        return ConsoleColor::{$color}($text);
    }

	protected function display($text, $color = 'green', $times = 1, $nextline = true)
	{
		echo $this->message($text, $color, $times, $nextline);
	}

	protected function nextline($times = 1)
    {
        $line = " \n";

        if ($times == 0) {
            return $line = '';
        }

        if ($times > 1) {
            return str_repeat($line, $times);
        }

        return $line;
    }

	protected function eol()
	{
		echo PHP_EOL;
	}

	/**
	* Magic method to get properties.
	*
	* @param string $property The property name
	* @return mixed The property value
	*/
	public function __get($property) {
		return $this->app->$property;
	}

}

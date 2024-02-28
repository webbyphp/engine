<?php

/**
 * Extend Route Class 
 * as Console Commands
 *
 * @author Kwame Oteng Appiah-Nti (Developer Kwame)
 * 
 */

namespace Base\Console\Route;

class Command
{

    /**
     * Allow web base routes to 
     * be set as command
     *
     * @param string $from
	 * @param string $to
	 * @param array $options
	 * @param boolean $nested
     * @return void
     */
    public static function set($from, $to, $options = [], $nested = false)
	{
		\Base\Route\Route::any($from, $to, $options, $nested);
	}

	/**
	 * Cli/Console route
	 *
	 * @param string $from
	 * @param string $to
	 * @param array $options
	 * @param boolean $nested
	 * @return mixed
	 */
	public static function cli($from, $to, $options = [], $nested = false)
	{
		\Base\Route\Route::cli($from, $to, $options, $nested);
	}
}

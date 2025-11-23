<?php

namespace Base\Debug;

use Exception;

/**
 * Temporarily suppresses PHP warnings 
 * and notices.This class implements a 
 * standard error suppression logic.
 */
class Error
{
	/**
	 * Reference count for suppressions.
	 * @var int
	 */
	private static $suppressCount = 0;

	/**
	 * Original error reporting level storage.
	 * @var false|int
	 */
	private static $originalLevel = false;

	/**
	 * Start suppressing common warnings and notices.
	 */
	public static function startSuppression()
	{
		if (self::$suppressCount == 0) {
			// Store the current level and 
			// set the new suppressed level.
			// Exclude common non-fatal errors.
			self::$originalLevel = error_reporting(E_ALL & ~(
				E_WARNING |
				E_NOTICE |
				E_USER_WARNING |
				E_USER_NOTICE |
				E_DEPRECATED |
				E_USER_DEPRECATED |
				2048 // E_STRICT - which has been deprecated
			));
		}
		self::$suppressCount++;
	}

	/**
	 * Refresh the previous error level.
	 */
	public static function stopSuppression()
	{
		if (self::$suppressCount > 0) {
			self::$suppressCount--;
			if (self::$suppressCount == 0) {
				// Restore only when the count reaches zero.
				error_reporting(self::$originalLevel);
				self::$originalLevel = false; // Reset state
			}
		}
	}

	/**
	 * Call a callback function with warnings suppressed.
	 *
	 * @param callable $callback The function to call.
	 * @param array $args Optional arguments for the function.
	 * @return mixed The result of the callback.
	 */
	public static function silenced(callable $callback, $args = [])
	{
		self::startSuppression();

		$returnValue = null;

		try {
			// Handle variadic arguments by calling 
			// the function with the array
			$returnValue = call_user_func_array($callback, $args);
		} catch (Exception $exp) {
			// Ensure restoration even if an 
			// exception occurs within the callback
			self::stopSuppression();
			throw $exp;
		}

		self::stopSuppression();
		return $returnValue;
	}
}

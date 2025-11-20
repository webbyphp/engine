<?php

namespace Base\Debug;

class Error
{

	private static int $suppressCount = 0;

	private static bool $originalLevel = false;

	/**
	 * Reference-counted warning suppression
	 *
	 * @param bool $end Whether to restore warnings
	 */
	public static function suppressWarnings(bool $end = false): void
	{
		if ($end) {

			if (self::$suppressCount) {

				--self::$suppressCount;
				if (!self::$suppressCount) {
					error_reporting(self::$originalLevel);
				}
			}
		} else {

			if (!self::$suppressCount) {
				self::$originalLevel =
					error_reporting(E_ALL & ~(
						E_WARNING |
						E_NOTICE |
						E_USER_WARNING |
						E_USER_NOTICE |
						E_DEPRECATED |
						E_USER_DEPRECATED |
						2048 // E_STRICT - which has been deprecated
					));
			}

			++self::$suppressCount;
		}
	}

	/**
	 * Restore error level to previous value
	 */
	public static function restoreWarnings(): void
	{
		self::suppressWarnings(true);
	}

	/**
	 * Call the callback given by the first parameter, suppressing any warnings.
	 *
	 * @param callable $callback Function to call
	 * @param mixed ...$args Optional arguments for the function call
	 * @return mixed
	 */
	public static function quietCall(callable $callback, ...$args)
	{
		self::suppressWarnings();

		try {
			$rv = $callback(...$args);
		} finally {
			self::restoreWarnings();
		}

		return $rv;
	}
}

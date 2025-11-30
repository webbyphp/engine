<?php

declare(strict_types=1);
/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Base\Helpers;

/**
 * UUID Generator Class
 * Supports UUID versions 3, 4, 5, and 7
 */
final class Uuid
{
	/**
	 * Generate a version 3 (MD5 hash) UUID
	 */
	public static function v3(string $name, ?string $namespace = null): string|false
	{
		$namespace ??= self::v4();

		if (empty($name) || !self::isValid($namespace)) {
			return false;
		}

		$nhex = str_replace(['-', '{', '}'], '', $namespace);
		$nstr = hex2bin($nhex);

		if ($nstr === false) {
			return false;
		}

		$hash = md5($nstr . $name);

		return sprintf(
			'%08s-%04s-%04x-%04x-%12s',
			substr($hash, 0, 8),
			substr($hash, 8, 4),
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
			substr($hash, 20, 12)
		);
	}

	/**
	 * Generate a version 4 (random) UUID
	 */
	public static function v4(bool $trim = false): string
	{
		try {
			$data = random_bytes(16);
		} catch (\Exception $e) {
			// Fallback to mt_rand if random_bytes fails
			$data = '';
			for ($i = 0; $i < 16; $i++) {
				$data .= chr(mt_rand(0, 255));
			}
		}

		$data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
		$data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

		$hex = bin2hex($data);

		return $trim
			? $hex
			: sprintf(
				'%08s-%04s-%04s-%04s-%12s',
				substr($hex, 0, 8),
				substr($hex, 8, 4),
				substr($hex, 12, 4),
				substr($hex, 16, 4),
				substr($hex, 20, 12)
			);
	}

	/**
	 * Generate a version 5 (SHA-1 hash) UUID
	 */
	public static function v5(string $name, ?string $namespace = null): string|false
	{
		$namespace ??= self::v4();

		if (empty($name) || !self::isValid($namespace)) {
			return false;
		}

		$nhex = str_replace(['-', '{', '}'], '', $namespace);
		$nstr = hex2bin($nhex);

		if ($nstr === false) {
			return false;
		}

		$hash = sha1($nstr . $name);

		return sprintf(
			'%08s-%04s-%04x-%04x-%12s',
			substr($hash, 0, 8),
			substr($hash, 8, 4),
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
			substr($hash, 20, 12)
		);
	}

	/**
	 * Generate a version 7 (timestamp-based) UUID
	 */
	public static function v7(): string
	{
		$bytes = random_bytes(16);
		$timestamp = (int)(microtime(true) * 1000);

		// Set timestamp bytes (48 bits)
		$bytes[0] = chr(($timestamp >> 40) & 0xFF);
		$bytes[1] = chr(($timestamp >> 32) & 0xFF);
		$bytes[2] = chr(($timestamp >> 24) & 0xFF);
		$bytes[3] = chr(($timestamp >> 16) & 0xFF);
		$bytes[4] = chr(($timestamp >> 8) & 0xFF);
		$bytes[5] = chr($timestamp & 0xFF);

		// Set version (7) and variant bits
		$bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x70);
		$bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

		$hex = bin2hex($bytes);

		return sprintf(
			'%08s-%04s-%04s-%04s-%12s',
			substr($hex, 0, 8),
			substr($hex, 8, 4),
			substr($hex, 12, 4),
			substr($hex, 16, 4),
			substr($hex, 20, 12)
		);
	}

	/**
	 * Validate UUID format
	 */
	public static function isValid(string $uuid): bool
	{
		return (bool)preg_match(
			'/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i',
			$uuid
		);
	}
}

<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Base\Config\Writer;

use Base\Exceptions\WriteException;

/**
 * Config file parser interface.
 */
interface WriterInterface
{
    /**
     * Writes a configuration from `$config` to `$filename`.
     *
     * @param  array $config
     * @param  string $filename
     *
     * @throws WriteException if the data could not be written to the file
     *
     * @return array
     */
    public function toFile($config, $filename);

    /**
     * Writes a configuration from `$config` to a string.
     *
     * @param  array $config
     * @param  bool $pretty
     *
     * @return array
     */
    public function toString($config, $pretty = true);

    /**
     * Returns an array of allowed file extensions for this writer.
     *
     * @return array
     */
    public static function getSupportedExtensions();
}

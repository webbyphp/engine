<?php

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

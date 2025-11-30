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

use \Base\Config\Writer\AbstractWriter;
use \Base\Exceptions\WriteException;

/**
 * JSON Writer.
 *
 * @package    Config
 * @author     Jesus A. Domingo <jesus.domingo@gmail.com>
 * @author     Hassan Khan <contact@hassankhan.me>
 * @author     Filip Š <projects@filips.si>
 * @author     Mark de Groot <mail@markdegroot.nl>
 * @link       https://github.com/noodlehaus/config
 * @license    MIT
 */
class Json extends AbstractWriter
{
    /**
     * 
     * Writes an array to a JSON file.
     * 
     * @throws WriteException
     */
    public function toFile($config, $filename)
    {
        $data = $this->toString($config);
        $success = @file_put_contents($filename, $data . PHP_EOL);
        if ($success === false) {
            throw new WriteException(['file' => $filename]);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     * Writes an array to a JSON string.
     */
    public function toString($config, $pretty = true)
    {
        return json_encode($config, $pretty ? (JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : 0);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSupportedExtensions()
    {
        return ['json'];
    }
}

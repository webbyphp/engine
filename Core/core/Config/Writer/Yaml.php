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

use Symfony\Component\Yaml\Yaml as YamlParser;

use \Base\Config\Writer\AbstractWriter;

/**
 * Yaml Writer.
 *
 * @package    Config
 * @author     Jesus A. Domingo <jesus.domingo@gmail.com>
 * @author     Hassan Khan <contact@hassankhan.me>
 * @author     Filip Š <projects@filips.si>
 * @author     Mark de Groot <mail@markdegroot.nl>
 * @link       https://github.com/noodlehaus/config
 * @license    MIT
 */
class Yaml extends AbstractWriter
{
    /**
     * {@inheritdoc}
     * Writes an array to a Yaml string.
     */
    public function toString($config, $pretty = true)
    {
        return \Symfony\Component\Yaml\Yaml::dump($config);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSupportedExtensions()
    {
        return ['yaml'];
    }
}

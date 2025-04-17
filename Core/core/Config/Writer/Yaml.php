<?php

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

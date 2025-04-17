<?php

namespace Base\Config\Writer;

use \Base\Config\Writer\AbstractWriter;

/**
 * Class Serialize
 *
 * @package Config
 */
class Serialize extends AbstractWriter
{

    /**
     * {@inheritdoc}
     */
    public function toString($config, $pretty = true)
    {
        return serialize($config);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSupportedExtensions()
    {
        return ['txt'];
    }
}

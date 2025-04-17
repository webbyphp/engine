<?php

namespace Base\Config\Writer;

use \Base\Exceptions\WriteException;

/**
 * Base Writer.
 *
 * @package    Config
 * @author     Jesus A. Domingo <jesus.domingo@gmail.com>
 * @author     Hassan Khan <contact@hassankhan.me>
 * @author     Filip Š <projects@filips.si>
 * @author     Mark de Groot <mail@markdegroot.nl>
 * @link       https://github.com/noodlehaus/config
 * @license    MIT
 */
abstract class AbstractWriter implements WriterInterface
{
    /**
     * @throws \Base\Exceptions\WriteException
     */
    public function toFile($config, $filename)
    {
        $contents = $this->toString($config);
        $success = @file_put_contents($filename, $contents);
        if ($success === false) {
            throw new WriteException(['file' => $filename]);
        }

        return $contents;
    }
}

<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Base\Config\Parser;

/**
 * Abstract parser
 *
 * @package    Config
 * @author     Jesus A. Domingo <jesus.domingo@gmail.com>
 * @author     Hassan Khan <contact@hassankhan.me>
 * @author     Filip Š <projects@filips.si>
 * @link       https://github.com/noodlehaus/config
 * @license    MIT
 */
abstract class AbstractParser implements ParserInterface
{

    /**
     * String with configuration
     *
     * @var string
     */
    protected $config;

    /**
     * Sets the string with configuration
     *
     * @param string $config
     * @param string $filename
     *
     * @codeCoverageIgnore
     */
    public function __construct($config, $filename = null)
    {
        $this->config = $config;
    }
}

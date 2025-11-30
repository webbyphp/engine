<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Base\Htmx;

use InvalidArgumentException;

trait Htmx
{
    private array $swapOptions = [
        'innerHTML',
        'outerHTML',
        'beforebegin',
        'afterbegin',
        'beforeend',
        'afterend',
        'delete',
        'none',
    ];

    /**
     * Validate swap option
     */
    private function validateSwap(string $option, string $field = 'swap'): bool
    {
        [$option] = explode(' ', $option, 2);

        if (! in_array($option, $this->swapOptions, true)) {
            throw new InvalidArgumentException(sprintf(
                'Option "%s" is not a valid variable for %s. A valid option has to be one of: %s',
                $option,
                $field,
                implode(', ', $this->swapOptions)
            ));
        }

        return true;
    }
}

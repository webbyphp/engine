<?php

namespace Base\Helpers;

class Honeypot
{
    /**
     * Generate a honeypot field.
     *
     * @param string $name
     * @param string $template
     * @param string $container
     * @return string
     */
    public function generate($name = '', $template = '', $container = '')
    {
        return honeypot($name, $template, $container);
    }

    /**
     * Checks if the honeypot field is not filled.
     *
     * @param string $honeypot
     * @return bool
     */
    public function check($honeypot = '')
    {
        return honey_check($honeypot);
    }

    /**
     * Checks the time it takes a form to be submitted.
     *
     * @param string $field
     * @param int    $time
     * @return bool
     */
    public function timeCheck($field = '', $time = '')
    {
        return honey_time($field, $time);
    }

    /**
     * Styles the honey_pot container.
     *
     * @param string $custom_style
     * @return string
     */
    public function style($custom_style = '')
    {
        return honey_style($custom_style);
    }
}

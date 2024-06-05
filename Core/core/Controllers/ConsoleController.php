<?php

namespace Base\Controllers;

use Base\Console\ConsoleColor;

class ConsoleController extends Controller
{
    protected $env = 'development';
        
    public function __construct()
    {
        parent::__construct();

        if (!is_cli()) {show_404();}
    }

    /**
     * Set to allow for only
     * development environment
     *
     * @return void
     */
    protected function onlydev()
    {
        if (ENVIRONMENT !== $this->env) {
            exit;
        }
    }

	private function message($text, $color = 'green', $times = 1, $nextline = true)
    {
        if ($nextline) {
            return ConsoleColor::{$color}($text) . $this->nextline($times);
        }

        return ConsoleColor::{$color}($text);
    }

	protected function response($text, $color = 'green', $times = 1, $nextline = true)
	{
		echo $this->message($text, $color, $times, $nextline);
	}

    protected function success($text, $times = 1, $nextline = true)
    {
        echo $this->message($text, 'green', $times, $nextline);
    }

    protected function info($text, $times = 1, $nextline = true)
    {
        echo $this->message($text, 'cyan', $times, $nextline);
    }

    protected function warning($text, $times = 1, $nextline = true)
    {
        echo $this->message($text, 'yellow', $times, $nextline);
    }

    protected function error($text, $times = 1, $nextline = true)
    {
        echo $this->message($text, 'red', $times, $nextline);
    }

    protected function eol()
	{
		echo PHP_EOL;
	}

    protected function nextline($times = 1)
    {
        $line = " \n";

        if ($times == 0) {
            return $line = '';
        }

        if ($times > 1) {
            return str_repeat($line, $times);
        }

        return $line;
    }

}

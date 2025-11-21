<?php

use Base\Helpers\DotEnvWriter;
use Base\Console\ConsoleColor;
use Base\Controllers\ConsoleController;

class Baseurl extends ConsoleController
{

    /**
     * Console keyword
     *
     * @var string
     */
    private $keyword = 'app.baseURL';

    private const DEFAULT_BASEURL = 'http://localhost:8085/';

    private $changedBaseUrl = '';

    public function __construct(
        private $args = [],
        private $defaultBaseUrl = self::DEFAULT_BASEURL,
        private $dotenv = new DotEnvWriter()
    ) {
        parent::__construct();

        $this->args = $_SERVER['argv'];
    }

    /**
     * Check if key exists
     *
     * @return bool
     */
    private function check()
    {

        $exists = false;

        if ($this->dotenv->exists($this->keyword)) {
            $exists = true;
        }

        return $exists;
    }

    /**
     * Prepare key
     *
     * @return bool
     */
    private function prepareKey()
    {

        $content = $this->dotenv->getContent();

        if (strstr($content, '# ' . $this->keyword)) {
            $this->dotenv->setContent(str_replace('# ' . $this->keyword, $this->keyword, $content));
        }

        $this->dotenv->write();

        return $this->check();
    }

    private function fixBaseUrl($baseUrl, $default)
    {

        if ($baseUrl == '--default') {
            $baseUrl = '';
        }

        if ($baseUrl !== $default && $baseUrl !== '') {
            $baseUrl = rtrim($baseUrl, '/') . '/';
        }

        if (str_contains($baseUrl, '--')) {
            $baseUrl = str_replace('--', '', $baseUrl);
        }

        return $baseUrl;
    }

    private function checkValidUrl($baseUrl)
    {
        if (!str_contains($baseUrl, 'http://') && !empty($baseUrl)) {
            echo ConsoleColor::yellow("\n\tTemporary base url: {$baseUrl}") . "\n" . "\n"; // exit;
        }
    }

    /**
     * Change base url
     * Used with webby command
     *
     * @return void
     */
    public function host()
    {
        $exists = $this->check();

        if (!$exists) {
            $this->prepareKey();
        }

        $exists = $this->check();

        if ($exists) {
            $this->changeBaseUrl();
        }

        echo ConsoleColor::green("\n\tBase url updated to: [{$this->changedBaseUrl}]") . "\n\n";
    }

    /**
     * Turn off
     * Used with webby command
     * 
     * @return void
     */
    public function default()
    {
        $exists = $this->check();

        if ($exists) {
            $this->setDefaultBaseUrl();
        }

        echo ConsoleColor::yellow("\n\tBase url set to default: [{$this->defaultBaseUrl}]") . "\n\n";
    }

    /**
     * Set Default Base Url
     *
     * @return void
     */
    private function setDefaultBaseUrl()
    {

        $baseUrl = $this->dotenv->getValue($this->keyword);

        $default = $this->defaultBaseUrl;

        $baseUrl = str_replace('baseurl/default/', '', $this->args[1]);

        if ($default !== $baseUrl) {
            $this->dotenv->setValue($this->keyword, str_replace('"', '', "'{$default}'"));
        }
    }

    /**
     * Change Base Url
     *
     * @return void
     */
    private function changeBaseUrl()
    {
        $dotenv = new DotEnvWriter();

        $default = $dotenv->getValue($this->keyword);

        $baseUrl = str_replace('baseurl/host/', '', $this->args[1]);

        $baseUrl = $this->fixBaseUrl($baseUrl, $default);

        $this->checkValidUrl($baseUrl);

        if ($baseUrl !== $default && $baseUrl !== '') {
            $dotenv->setValue($this->keyword, str_replace('"', '', "'{$baseUrl}'"));
        }

        if ($baseUrl === '') {
            $this->default();
        }

        $this->changedBaseUrl = $baseUrl;

        if (!$dotenv->wasChanged()) {
            exit;
        }
    }
}

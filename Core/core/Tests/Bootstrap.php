<?php

/*
 *---------------------------------------------------------------
 * COMPOSER AUTOLOADING
 *---------------------------------------------------------------
 *
 * Load composer to simplify 
 * class and namespace autoloading
 */
require_once dirname(__DIR__) . '/../../../../../vendor/autoload.php';

// Load custom instance class
require_once __DIR__ . '/WebbyInstance.php';

/*
 * Paths needed to be accessed as important directories
 */
include_once dirname(__DIR__) . '/../../../../../bootstrap/paths.php';

// Specify the root directory of the CodeIgniter installation.
// Adapt this path to where CI is actually located.
$root = dirname(__DIR__) . '/../../../../../public/index.php';

// Specify the path to the CodeIgniter 3 Framework directory
$cipath = dirname(__DIR__) . '/../../CodeIgniter/Framework/';

// Specify the path to the Webby Core directory
$corepath = dirname(__DIR__) . '/../../Core/';

// Specify the environment for the test instance
$environment = 'testing'; // Set environment to testing by default

// Specify the $assign_to_config array to use
// Adjusted as needed
$assignToConfig = [
    'core_directory' => $core_directory, // needed
    'ci_directory' => $ci_directory, // needed
    'view_directory' => $view_directory, // needed
    'composer_directory' => $composer_directory, // needed
    'apppath' => $cipath,
    'environment' => $environment,
];

$webby = null;

// Create Webby instance
try {
    $webby = \WebbyInstance::init(
        $cipath,
        $corepath,
        $environment,
        $assignToConfig
    );
} catch (\WebbyInstanceException $e) {
    exit('Error initializing WebbyPHP instance for tests: ' . $e->getMessage());
}

// Store the CI3 or Webby instance to make it accessible in our tests
// This static property can be used on a base TestCase or a helper function
class Webby
{
    public static $app;

    public static function instance()
    {
        return static::$app;
    }
}

// Very Important
Webby::$app = $webby;

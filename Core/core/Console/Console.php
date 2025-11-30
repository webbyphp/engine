<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * WebbyPHP Console
 * 
 * The CLI Engine for WebbyPHP
 * 
 * @author  Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 * 
 */

namespace Base\Console;

class Console
{

    protected static $phpCommand = '';

    private static $rootpath = '';

    protected static $cliversion = '';

    private static $env = '';

    private static $removeComposerDev = 'composer --no-dev update';

    private static $composerCommand = 'composer ';

    // Pest variables
    private static $pestCommand = './vendor/bin/pest ';
    private static $pestColorAlways = '--colors=always ';
    private static $pestTestCoverate = '--coverage ';

    private const WEBBY_CLI_VERSION = '2.12.4';

    private const DEFAULT_HOST = "localhost";

    private const DEFAULT_PORT = 8085;

    private const UIP = '0.0.0.0';

    private static $webbyphpEngine = 'webbyphp/engine';

    public static function phpCommand()
    {
        return Console::userConstants()->PHP_CLI_VERSION . ' public/index.php ';
    }

    /** 
     * Grab available defined user constants
     */
    protected static function userConstants()
    {
        return (object) get_defined_constants(true)['user'];
    }

    /**
     * Webby Cli welcome message
     *
     * @return string
     */
    protected static function welcome()
    {
        // static::$cliversion = defined('WEBBY_VERSION') ? WEBBY_VERSION : static::userConstants()->WEBBY_CLI_VERSION;

        static::$cliversion = defined('WEBBY_VERSION') ? WEBBY_VERSION : self::WEBBY_CLI_VERSION;

        return ConsoleColor::cyan("Welcome to Webby PHP Framework") . " " . ConsoleColor::green(static::$cliversion) . "\n";
    }

    /**
     * Show Webby Current Version
     *
     * @return string
     */
    protected static function version()
    {
        static::$cliversion = defined('WEBBY_VERSION') ? WEBBY_VERSION : self::WEBBY_CLI_VERSION;

        return ConsoleColor::cyan("\t\tWebby PHP Framework") . " " . ConsoleColor::green(static::$cliversion) . "\n";
    }

    /**
     * Display when command
     * not found
     *
     * @return void
     */
    public static function noCommand(): void
    {
        $output =   " \n";
        $output .=  static::welcome() . "\n";
        $output .=  ConsoleColor::white(" Sorry this command is not known", 'light', 'red') . " \n";

        echo $output . "\n";
    }

    /**
     * Execute cli command
     *
     * @return void
     */
    public static function executeCommand()
    {
        $arguments = func_get_args();

        [$listArguments] = $arguments;

        // $count = is_countable($listArguments) ? count($listArguments) : 0;
        $count = $_SERVER['argc'];

        if ($count === 0) {
            static::noCommand();
            exit;
        }

        // Declare empty argX variables
        $arg2 = '';
        $arg3 = '';
        $arg4 = '';
        $arg5 = '';
        $arg6 = '';

        list($webby, $arg1) = $listArguments;

        if ($count === 3) {
            list($webby, $arg1, $arg2) = $listArguments;
        }

        if ($count === 4) {
            list($webby, $arg1, $arg2, $arg3) = $listArguments;
        }

        if ($count === 5) {
            list($webby, $arg1, $arg2, $arg3, $arg4) = $listArguments;
        }

        if ($count === 6) {
            list($webby, $arg1, $arg2, $arg3, $arg4, $arg5) = $listArguments;
        }

        if ($count === 7) {
            list($webby, $arg1, $arg2, $arg3, $arg4, $arg5, $arg6) = $listArguments;
        }

        if (!isset($webby)) {
            static::noCommand();
            exit;
        }

        switch ($arg1) {
            case '--help':
            case '-h':
                static::consoleEnv();

                if (!empty($arg2)) {
                    \Base\Console\Commands\Help::whichHelp($arg2);
                    exit;
                }

                \Base\Console\Commands\Help::runHelp();
                break;
            case '--version':
            case '-v':
                static::consoleEnv();

                if (!empty($arg2)) {
                    echo static::version();
                    exit;
                }

                echo static::version();
                break;
            case '--env':
                static::consoleEnv();

                if (!empty($arg2)) {
                    \Base\Console\Commands\Help::whichHelp($arg2);
                    exit;
                }

                \Base\Console\Commands\Help::runHelp();
                break;
            case 'key:generate':
                static::consoleEnv();
                static::runSystemCommand(Console::phpCommand() . 'key/prepare');

                if ($arg2 == "--regenerate") {
                    static::runSystemCommand(Console::phpCommand() . 'key/regenerate');
                    exit;
                }

                static::runSystemCommand(Console::phpCommand() . 'key');
                break;
            case 'list:routes':
                static::runSystemCommand(Console::phpCommand() . 'routes');
                break;
            case 'app:on':
                static::runSystemCommand(Console::phpCommand() . 'maintenance/on/' . $arg2);
                break;
            case 'app:off':
                static::runSystemCommand(Console::phpCommand() . 'maintenance/off/' . $arg2);
                break;
            case 'app:mode':
                static::runSystemCommand(Console::phpCommand() . 'maintenance/mode/' . $arg2);
                break;
            case 'app:to-production':
                static::runSystemCommand(Console::phpCommand() . 'environment/production');
                break;
            case 'app:to-testing':
                static::runSystemCommand(Console::phpCommand() . 'environment/testing');
                break;
            case 'app:to-development':
                static::runSystemCommand(Console::phpCommand() . 'environment/development');
                break;
            case 'app:baseurl':
                static::runSystemCommand(Console::phpCommand() . 'baseurl/host/' . $arg2);
                break;
            case 'resource:link':
                static::consoleEnv();
                static::runSystemCommand(Console::phpCommand() . 'create/resourcelink');
                break;
            case 'test':
                static::consoleEnv();
                static::runSystemCommand(static::$pestCommand . static::$pestColorAlways . $arg2 . ' ' . $arg3);
                break;
            case 'test:coverage':
                static::consoleEnv();
                static::runSystemCommand(static::$pestCommand . static::$pestColorAlways . static::$pestTestCoverate . $arg2 . ' ' . $arg3);
                break;
            case 'use:command':
                if (empty($arg2)) {
                    echo ConsoleColor::red("No arguments provided!") . "\n";
                    exit;
                }
                static::runSystemCommand(Console::phpCommand() . $arg2);
                break;
            case 'git:init':
                static::consoleEnv();
                static::runSystemCommand('git init');
                break;
            case 'create:package':
                static::consoleEnv();
                static::createPackage($arg2, $arg3, $arg4);
                break;
            case 'create:module':
                static::consoleEnv();
                static::createModule($arg2, $arg3, $arg4);
                break;
            case 'create:controller':
                static::consoleEnv();
                static::createController($arg2, $arg3, $arg4, $arg5);
                break;
            case 'create:command':
                static::consoleEnv();
                static::createCommand($arg2, $arg3, $arg4);
                break;
            case 'create:model':
                static::consoleEnv();
                static::createModel($arg2, $arg3, $arg4, $arg5);
                break;
            case 'create:view':
                static::consoleEnv();
                static::createView($arg2, $arg3, $arg4);
                break;
            case 'create:service':
                static::consoleEnv();
                static::createService($arg2, $arg3, $arg4);
                break;
            case 'create:action':
                static::consoleEnv();
                static::createAction($arg2, $arg3, $arg4);
                break;
            case 'create:library':
                static::consoleEnv();
                static::createLibrary($arg2, $arg3, $arg4);
                break;
            case 'create:helper':
                static::consoleEnv();
                static::createHelper($arg2, $arg3, $arg4);
                break;
            case 'create:form':
                static::consoleEnv();
                static::createForm($arg2, $arg3, $arg4);
                break;
            case 'create:rule':
                static::consoleEnv();
                static::createRule($arg2, $arg3, $arg4);
                break;
            case 'create:middleware':
                static::consoleEnv();
                static::createMiddleware($arg2, $arg3, $arg4);
                break;
            case 'create:enum':
                static::consoleEnv();
                static::createEnum($arg2, $arg3, $arg4);
                break;
            case 'create:tap':
                static::consoleEnv();
                static::createTap($arg2, $arg3, $arg4);
                break;
            case 'create:migration':
                static::consoleEnv();
                static::createMigration($arg2, $arg3, $arg4);
                break;
            case 'run:migration':
                static::consoleEnv();
                static::runSystemCommand(Console::phpCommand() . 'migrate');
                break;
            case 'migrate':
                static::consoleEnv();
                static::runMigration($arg2, $arg3, $arg4);
                break;
            case 'create:seeder':
                static::consoleEnv();
                static::createSeeder($arg2, $arg3, $arg4);
                break;
            case 'db:seed':
                static::consoleEnv();
                static::runSeeder($arg2, $arg3, $arg4);
                break;
            case 'db:truncate':
                static::consoleEnv();
                static::runSeeder($arg2, $arg3, $arg4);
                break;
            case 'update:engine':
                static::consoleEnv();
                $installOption = 'require ';
                static::runSystemCommand(static::$composerCommand . $installOption . static::$webbyphpEngine);
                break;
            case 'create:jsondb':
                static::consoleEnv();
                static::createJsonDb($arg2, $arg3, $arg4);
                break;
            case 'install:package':
                static::consoleEnv();
                $installOption = 'require ';
                static::runSystemCommand(static::$composerCommand . $installOption . $arg2 . ' ' . $arg3);
                break;
            // case 'publish:package':
            //     static::consoleEnv();
            // break;
            case 'clear:cache':
                static::consoleEnv();
                static::clearCache($arg2, $arg3, $arg4);
                break;
            case 'clear:session':
                static::consoleEnv();
                static::clearSession($arg2, $arg3, $arg4);
                break;
            case 'update:composer':
                static::consoleEnv();
                static::runSystemCommand(static::$composerCommand . 'self-update');
                break;
            default:
                static::noCommand();
                break;
        }
    }

    protected static function createPackage(...$args)
    {
        $name = '';
        $type = '';
        $with = '';

        if (isset($args[0])) {
            $name = $args[0];
        }

        if (isset($args[1])) {
            $with = $args[1];
        }

        $command = Console::phpCommand() . 'create/createpackage/' . $name . '/' . $type . '/' . $with;
        static::runSystemCommand($command);
    }

    protected static function createModule(...$args)
    {
        $module = explode(':', $args[0]);
        $name = '';
        $type = '';
        $with = '';

        if (isset($module[0])) {
            $type = $module[0];
        }

        if (isset($module[1])) {
            $name = $module[1];
        }

        if (isset($args[1])) {
            $with = $args[1];
        }

        $command = Console::phpCommand() . 'create/createmodule/' . $name . '/' . $type . '/' . $with;
        static::runSystemCommand($command);
    }

    protected static function createCommand(...$args)
    {
        $module = $args[0];
        $commandName = '';

        $nonModuleCommand = str_contains($args[0], ':');

        if ($nonModuleCommand !== true) {
            $args[2] = $args[1];
            $args[1] = $args[0];
        }

        $command = str_replace('=', ':', $args[1]);
        $command = explode(':', $command);

        $addCommand = '';

        if ($command[0] === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:command", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if (isset($command[0])) {
            $commandName = $command[0];
        }

        if (isset($args[2])) {
            $addCommand = $args[2];
        }

        if ($nonModuleCommand !== true) {
            $command = Console::phpCommand() . 'create/createnonmodulecommand/' . $commandName . '/' . $addCommand;
            static::runSystemCommand($command);
            return;
        }

        $module = str_replace('=', ':', $module);
        $command = Console::phpCommand() . 'create/createcommand/' . $module . '/' . $commandName . '/' . $addCommand;
        static::runSystemCommand($command);
    }

    protected static function createController(...$args)
    {
        $module = $args[0];
        $controllerName = '';
        $location = "Controllers";
        $lastArg = !empty($args[3]) ? $args[3] : $args[2];

        $nonModuleController = str_contains($module, ':');

        if ($nonModuleController !== true) {
            $vars = $args;
            $args[1] = $vars[0];
            $args[2] = $vars[1];
            $args[3] = $vars[2];
            $args[4] = $vars[3];
        }

        $controller = str_replace('=', ':', $args[1]);
        $controller = explode(':', $controller);

        $addController = '';

        if ($controller[0] === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:controller", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if (isset($controller[0])) {
            $controllerName = $controller[0];
        }

        if (isset($args[2])) {
            $addController = $args[2];
        }

        if (isset($args[2]) && $args[2] == '--dir') {
            $location = $lastArg;
        } elseif (isset($args[3]) && $args[3] == '--dir') {
            $location = '--dir' . ':' . $lastArg;
        }

        if ($nonModuleController !== true) {
            $location = str_replace('/', '_', $location);
            $command = Console::phpCommand() . 'create/createnonmodulecontroller/' . $controllerName . '/' . $addController . '/' . $location;
            static::runSystemCommand($command);
            return;
        }

        $module = str_replace('=', ':', $module);
        $command = Console::phpCommand() . 'create/createcontroller/' . $module . '/' . $controllerName . '/' . $addController;
        static::runSystemCommand($command);
    }

    protected static function createModel(...$args)
    {
        $module = $args[0];
        $modelName = '';
        $location = "Models";
        $lastArg = $args[3];

        $nonModuleModel = str_contains($module, ':');

        if ($nonModuleModel !== true) {
            $vars = $args;
            $args[1] = $vars[0];
            $args[2] = $vars[1];
            $args[3] = $vars[2];
            $args[4] = $vars[3];
        }

        $model = str_replace('=', ':', $args[1]);
        $model = explode(':', $model);

        $modelType = '';
        $removeModel = '';

        if ($model[0] === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:model", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if (isset($model[0])) {
            $modelName = $model[0];
        }

        if (isset($args[2])) {
            $modelType = $args[2];
        }

        if (isset($args[3])) {
            $removeModel = $args[3];
        }

        if (isset($args[3]) && $args[3] == '--dir') {
            $location = $lastArg;
        }

        if ($modelType == '--remove-suffix') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:model", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if ($modelType == '--dir') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:model", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if ($nonModuleModel !== true) {
            $location = str_replace('/', '_', $location);
            $command = Console::phpCommand() . 'create/createnonmodulemodel/' . $modelName . '/' . $modelType . '/' . $removeModel . '/' . $location;
            static::runSystemCommand($command);
            return;
        }

        $module = str_replace('=', ':', $module);
        $command = Console::phpCommand() . 'create/createmodel/' . $module . '/' . $modelName . '/' . $modelType . '/' . $removeModel;
        static::runSystemCommand($command);
    }

    protected static function createService(...$args)
    {
        $module = $args[0];
        $serviceName = '';

        $nonModuleService = str_contains($module, ':');

        $module = ($nonModuleService === false) ? 'empty' : $module;

        if ($module === 'empty') {
            $vars = $args;
            $args[1] = $vars[0];
        }

        $service = str_replace('=', ':', $args[1]);
        $service = explode(':', $service);

        if ($service[0] === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:service", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if (isset($service[0])) {
            $serviceName = $service[0];
        }

        $module = str_replace('=', ':', $module);
        $command = Console::phpCommand() . 'create/createservice/' . $module . '/' . $serviceName;
        static::runSystemCommand($command);
    }

    protected static function createAction(...$args)
    {
        $module = $args[0];
        $actionName = '';

        $nonModuleAction = str_contains($module, ':');

        $module = ($nonModuleAction === false) ? 'empty' : $module;

        if ($module === 'empty') {
            $vars = $args;
            $args[1] = $vars[0];
            $args[2] = $vars[1];
        }

        $action = str_replace('=', ':', $args[1]);
        $action = explode(':', $action);

        $actionType = '';

        if ($action[0] === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:action", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if (isset($action[0])) {
            $actionName = $action[0];
        }

        if (isset($args[2])) {
            $actionType = $args[2];
        }

        $module = str_replace('=', ':', $module);
        $command = Console::phpCommand() . 'create/createaction/' . $module . '/' . $actionName . '/' . $actionType;
        static::runSystemCommand($command);
    }

    protected static function createLibrary(...$args)
    {
        $module = $args[0];
        $libraryName = '';

        $nonModuleLibrary = str_contains($module, ':');

        $module = ($nonModuleLibrary === false) ? 'empty' : $module;

        if ($module === 'empty') {
            $vars = $args;
            $args[1] = $vars[0];
        }

        $library = str_replace('=', ':', $args[1]);
        $library = explode(':', $library);

        if ($library[0] === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax for create:library", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if (isset($library[0])) {
            $libraryName = $library[0];
        }

        $module = str_replace('=', ':', $module);
        $command = Console::phpCommand() . 'create/createlibrary/' . $module . '/' . $libraryName;
        static::runSystemCommand($command);
    }

    protected static function createHelper(...$args)
    {
        $module = $args[0];
        $helperName = '';

        $nonModuleHelper = str_contains($module, ':');

        $module = ($nonModuleHelper === false) ? 'empty' : $module;

        if ($module === 'empty') {
            $vars = $args;
            $args[1] = $vars[0];
            $args[2] = $vars[1];
        }

        $helper = str_replace('=', ':', $args[1]);
        $helper = explode(':', $helper);

        $helperType = '';

        if ($helper[0] === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:helper", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if (isset($helper[0])) {
            $helperName = $helper[0];
        }

        if (isset($args[2])) {
            $helperType = $args[2];
        }

        $module = str_replace('=', ':', $module);
        $command = Console::phpCommand() . 'create/createhelper/' . $module . '/' . $helperName . '/' . $helperType;
        static::runSystemCommand($command);
    }

    protected static function createForm(...$args)
    {
        $module = $args[0];
        $formName = '';

        $form = str_replace('=', ':', $args[1]);
        $form = explode(':', $form);

        if ($form[0] === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:form", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if (isset($form[0])) {
            $formName = $form[0];
        }

        $module = str_replace('=', ':', $module);
        $command = Console::phpCommand() . 'create/createform/' . $module . '/' . $formName;
        static::runSystemCommand($command);
    }

    /**
     * Create Rule
     *
     * @param mixed ...$args
     * @return void
     */
    protected static function createRule(...$args)
    {
        $module = $args[0];
        $ruleName = '';

        $rule = str_replace('=', ':', $args[1]);
        $rule = explode(':', $rule);

        if ($rule[0] === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:rule", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if (isset($rule[0])) {
            $ruleName = $rule[0];
        }

        $module = str_replace('=', ':', $module);
        $command = Console::phpCommand() . 'create/createrule/' . $module . '/' . $ruleName;
        static::runSystemCommand($command);
    }

    protected static function createMiddleware(...$args)
    {
        $name = '';
        $type = '';

        if (isset($args[0])) {
            $name = $args[0];
        }

        if (isset($args[1])) {
            $type = $args[1];
        }

        if ($name === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:middleware", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if (strpos($name, '-') !== false) {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:middleware", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        $command = Console::phpCommand() . 'create/createmiddleware/' . $name . '/' . $type;
        static::runSystemCommand($command);
    }

    protected static function createEnum(...$args)
    {
        $name = '';
        $type = '';

        if (isset($args[0])) {
            $name = $args[0];
        }

        if (isset($args[1])) {
            $type = $args[1];
        }

        if ($name === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:enum", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if (strpos($name, '-') !== false) {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:enum", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        $command = Console::phpCommand() . 'create/createenum/' . $name . '/' . $type;
        static::runSystemCommand($command);
    }

    protected static function createTap(...$args)
    {
        $name = '';
        $type = '';

        if (isset($args[0])) {
            $name = $args[0];
        }

        if (isset($args[1])) {
            $type = $args[1];
        }

        if ($name === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:tap", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if (strpos($name, '-') !== false) {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:tap", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        $command = Console::phpCommand() . 'create/createtap/' . $name . '/' . $type;
        static::runSystemCommand($command);
    }
    protected static function createJsonDb(...$args)
    {
        $name = '';
        $type = '';

        if (isset($args[0])) {
            $name = $args[0];
        }

        $name = str_replace(['-', '='], '', $name);

        if ($name === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:jsondb", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        $command = Console::phpCommand() . 'create/createjsondb/' . $name;
        static::runSystemCommand($command);
    }

    protected static function createMigration(...$args)
    {
        $name = '';
        $type = '';

        if (isset($args[0])) {
            $name = $args[0];
        }

        if (isset($args[1])) {
            $type = $args[1];
        }

        $name = str_replace(['-', '='], '', $name);
        $type = str_replace(['-', '='], '', $type);

        if ($name === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:migration", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        $command = Console::phpCommand() . 'create/createmigration/' . $name . '/' . $type;
        static::runSystemCommand($command);
    }

    protected static function runMigration(...$args)
    {
        $key = $args[0];
        $steps = $args[1];

        if ($key == null) {
            $command = Console::phpCommand() . 'migration/run';
            static::runSystemCommand($command);
            exit;
        }

        if (!empty($steps)) {
            $steps = explode('=', $steps);
        }

        if ($key === '--up' || $key === '--down') {

            if (isset($steps[0]) && $steps[0] !== '--use-file') {
                $output =   " \n";
                $output .=  ConsoleColor::white(" Please check docs for correct syntax to use for migrate --up or --down --use-file", 'light', 'red') . " \n";
                echo $output . "\n";
                exit;
            }

            if (empty($steps[1])) {
                $output =   " \n";
                $output .=  ConsoleColor::white(" Please specify the filename to use for migrate ", 'light', 'yellow') . " \n";
                echo $output . "\n";
                exit;
            }

            $filename = $steps[1];

            if (!empty($filename)) {
                $command = Console::phpCommand() . 'migration/usefile/' . $filename . '/' . $key;
                static::runSystemCommand($command);
                exit;
            }
        }

        if ($key === '--rollback') {

            $step = 0;

            if (!empty($steps[0]) && $steps[0] !== '--step') {
                $output =   " \n";
                $output .=  ConsoleColor::white(" Please check docs for correct syntax to use for migrate --rollback", 'light', 'red') . " \n";
                echo $output . "\n";
                exit;
            }

            if (!empty($steps[1]) && is_numeric($steps[1])) {
                $step = $steps[1];
            }

            if (!empty($step) && !is_string($step) && is_infinite($step)) {
                $command = Console::phpCommand() . 'migration/rollback/' . $step;
                static::runSystemCommand($command);
                exit;
            }

            if (!empty($step) && is_numeric($step)) {
                $command = Console::phpCommand() . 'migration/rollback/' . $step;
                static::runSystemCommand($command);
                exit;
            }

            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to use for migrate --rollback", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if ($key === '--later') {
            $command = Console::phpCommand() . 'migration/future';
            static::runSystemCommand($command);
            exit;
        }

        if ($key === '--status') {
            $command = Console::phpCommand() . 'migration/status';
            static::runSystemCommand($command);
            exit;
        }

        if ($key === '--reset') {
            $command = Console::phpCommand() . 'migration/reset';
            static::runSystemCommand($command);
            exit;
        }

        if ($key === '--latest') {
            $command = Console::phpCommand() . 'migration/latest';
            static::runSystemCommand($command);
            exit;
        }

        if ($key === '--truncate') {
            $command = Console::phpCommand() . 'migration/truncate';
            static::runSystemCommand($command);
            exit;
        }

        if ($key === '--export-schema' || $key === '--xs') {

            $removeTables = '';
            $filename = date('Y-m-d-His');

            $command = Console::phpCommand() . 'migration/exportSchema/' . $filename;

            if (isset($steps[0]) && isset($steps[1])) {
                $filename = ($steps[1]) ?: $filename;
                $command = Console::phpCommand() . 'migration/exportSchema/' . $filename;
            }

            if (isset($args[2]) && !empty($args[2])) {
                $list = explode('=', $args[2]);
                $removeTables = str_replace(',', '__', $list[1]);
                $filename = $steps[1];
                $command = Console::phpCommand() . 'migration/exportSchema/' . $filename . '/' . $removeTables;
            }

            static::runSystemCommand($command);
            exit;
        }

        if ($key === '--dump-database' || $key === '--dd') {

            $name = date('Y-m-d-His');

            $command = Console::phpCommand() . 'migration/dumpDb/' . $name;

            if (isset($args[1]) && !empty($args[1])) {
                $step = explode('=', $args[1]);
                $name = $step[1];
                $command = Console::phpCommand() . 'migration/dumpDb/' . $name;
            }

            static::runSystemCommand($command);
            exit;
        }

        if ($key != null) {
            $steps = explode('=', $key);
        }

        if ($steps[0] === '--step' && !empty($steps[1]) && is_numeric($steps[1])) {
            $steps = $steps[1];
            $command = Console::phpCommand() . 'migration/run/' . $steps;
            static::runSystemCommand($command);
            exit;
        }

        $output =   " \n";
        $output .=  ConsoleColor::white(" Please check docs for correct syntax to use for migrate", 'light', 'red') . " \n";
        echo $output . "\n";
        exit;
    }

    protected static function createSeeder(...$args)
    {
        $name = '';
        $type = '';

        if (isset($args[0])) {
            $name = $args[0];
        }

        if (isset($args[1])) {
            $type = $args[1];
        }

        $name = str_replace(['-', '='], '', $name);
        $type = str_replace(['-', '='], '', $type);

        if ($name === '') {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to create:seeder", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        $command = Console::phpCommand() . 'create/createseeder/' . $name . '/' . $type;
        static::runSystemCommand($command);
    }

    protected static function runSeeder(...$args)
    {
        $key = $args[0];

        if ($key == null) {
            $command = Console::phpCommand() . 'seed/multiple';
            static::runSystemCommand($command);
            exit;
        }

        if (!empty($key)) {
            $key = explode('=', $key);
        }

        if ($key[0] === '--with') {

            $filename = $key[1] ?? '';

            if (!empty($filename)) {
                $command = Console::phpCommand() . 'seed/single/' . $filename;
                static::runSystemCommand($command);
                exit;
            }
        }

        if ($key[0] === '--table') {

            $tablename = $key[1] ?? '';

            if (!empty($tablename)) {
                $command = Console::phpCommand() . 'seed/truncate/' . $tablename;
                static::runSystemCommand($command);
                exit;
            }

            if (empty($tablename)) {
                $output =   " \n";
                $output .=  ConsoleColor::white(" Please check docs for correct syntax to use for db:truncate", 'light', 'red') . " \n";
                echo $output . "\n";
                exit;
            }
        }

        $output =   " \n";
        $output .=  ConsoleColor::white(" Please check docs for correct syntax to use for db:seed", 'light', 'red') . " \n";
        echo $output . "\n";
        exit;
    }

    protected static function createView(...$args)
    {
        $view = $args;
        $filename = '';
        $module = '';

        if (strpos($view[0], ':') !== false) {
            $module = $view[0];
        }

        $viewType = '';

        if (!empty($module) && !empty($view[1])) {
            $filename = $view[1];
        }

        if (empty($module) && !empty($view[1])) {
            $filename = $view[0];
            $viewType = $view[1];
        }

        if (empty($module) && !empty($view[0])) {
            $filename = $view[0];
        }

        if (!empty($view[2])) {
            $viewType = $args[2];
        }

        $filename = str_replace('-', '__', $filename);
        $filename = str_replace('/', '-', $filename);
        $module = empty($module) ? 'empty' : $module;
        $command = Console::phpCommand() . 'create/createview/' . $module . '/' . $filename . '/' . $viewType;

        static::runSystemCommand($command);
    }

    public static function handleTestCommand(...$args) {}

    protected static function clearCache(...$args)
    {
        $type = $args[0];
        $file = $args[1];

        if ($type == null) {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to use for clear:cache", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if ($type == '--files') {

            $type = str_replace('-', '', $type);

            $command = Console::phpCommand() . 'cache/clearpath/' . $type;
            static::runSystemCommand($command);
            exit;
        }

        if ($type == '--arrayz') {

            $type = str_replace('-', '', $type);

            $command = Console::phpCommand() . 'cache/clearpath/' . $type;
            static::runSystemCommand($command);
            exit;
        }

        if ($type == '--plates') {

            $type = str_replace('-', '', $type);

            $command = Console::phpCommand() . 'cache/clearpath/' . $type;
            static::runSystemCommand($command);
            exit;
        }

        if ($type == '--web') {

            $type = str_replace('-', '', $type);

            $command = Console::phpCommand() . 'cache/clearpath/' . $type;
            static::runSystemCommand($command);
            exit;
        }

        if ($type == '--config') {

            $type = str_replace('-', '', $type);

            $command = Console::phpCommand() . 'cache/clearcachedconfig/' . $type;
            static::runSystemCommand($command);
            exit;
        }

        $output =   " \n";
        $output .=  ConsoleColor::white(" Please check docs for correct syntax to use for clear:cache", 'light', 'red') . " \n";
        echo $output . "\n";
        exit;
    }

    protected static function clearSession(...$args)
    {
        $type = $args[0];
        $file = $args[1];

        if ($type == null) {
            $type = '--file';
        }

        if ($type == '--file') {

            $type = str_replace('-', '', $type);

            $command = Console::phpCommand() . 'session/clean/' . $type;
            static::runSystemCommand($command);
            exit;
        }

        if ($type == null) {
            $output =   " \n";
            $output .=  ConsoleColor::white(" Please check docs for correct syntax to use for clear:session", 'light', 'red') . " \n";
            echo $output . "\n";
            exit;
        }

        if ($type == '--db') {

            $type = str_replace('-', '', $type);

            $command = Console::phpCommand() . 'session/clean/' . $type;
            static::runSystemCommand($command);
            exit;
        }

        $output =   " \n";
        $output .=  ConsoleColor::white(" Please check docs for correct syntax to use for clear:session", 'light', 'red') . " \n";
        echo $output . "\n";
        exit;
    }


    /**
     * Check Console Environment
     *
     * @return void
     */
    private static function consoleEnv(): void
    {
        static::$env = static::userConstants()->ENVIRONMENT;

        if (static::$env !== 'development') {
            exit;
        }
    }

    /**
     * Set environment function
     *
     * @return void
     */
    private static function setenv(): void
    {
        static::$rootpath = static::userConstants()->WEBBY_ROOTPATH;

        $envExampleFile =  static::$rootpath . '/.env.example';
        $envFile = static::$rootpath . '/.env';

        if (file_exists($envFile)) {
            echo ConsoleColor::red("Environment file exists already!") . "\n";
            exit;
        }

        // Copy content from .env.example file
        // to .env file
        if (!copy($envExampleFile, $envFile)) {
            echo ConsoleColor::red("Environment was not able to be set!") . "\n";
        } else {
            echo ConsoleColor::green("Environment has been set successfully!") . "\n";
        }
    }

    /**
     * Run system command
     *
     * @param string $command
     * @return mixed
     */
    public static function runSystemCommand($command = ''): mixed
    {
        return system($command);
    }

    /**
     * Serve Webby Application
     *
     * @param array $args
     * @return void
     */
    private static function serve($args = []): void
    {
        static::$rootpath = static::userConstants()->WEBBY_ROOTPATH;

        $host = static::DEFAULT_HOST;
        $port = 0;
        $number = static::DEFAULT_PORT;

        if (isset($args[1]) && $args[2] === '--ssl' && !empty($args[3])) {

            // system('sudo ./ssl-serve ' . $args[3] . ' ' . static::DEFAULT_PORT . ' ' . rand(5000, 6000));
            // system($command);
            static::runSystemCommand('sudo ./ssl-serve ' . $args[3] . ' ' . static::DEFAULT_PORT . ' ' . rand(5000, 6000));
            exit;
        }

        if (isset($args[2]) && $args[2] === '--port') {
            $number = isset($args[3]) ? (int)$args[3] : "";
            $port = (isset($number) && is_int($number)) ? $number : static::DEFAULT_PORT;
            $port = intval($port);
        } else if ((isset($args[2]) && ($args[2] === '--host'))
            && (isset($args[2]) && isset($args[4]) && $args[4] === '--port')
        ) {
            $host = isset($args[3]) ? (string)$args[3] : "localhost";
            $number = isset($args[5]) ? (int)$args[5] : "";
            $port = (isset($number) && is_int($number)) ? $number : static::DEFAULT_PORT;
            $port = intval($port);
        } else if ((isset($args[2]) && ($args[2] === '--host'))) {
            $host = isset($args[3]) ? (string)$args[3] : "localhost";
            $port = static::DEFAULT_PORT;
            $port = intval($port);
        } else {
            $port = $number;
            $port = intval($port);
        }

        if ($port === 0) {
            echo ConsoleColor::red("\n\tPlease choose a valid port number!\n") . "\n";
            exit;
        }

        if ($port < 1024 || $port > 65535) {
            echo ConsoleColor::red("\n\tPort number must be between 1024 and 65535!\n") . "\n";
            exit;
        }

        if (static::checkPort($host, $port)) {
            $oldPort = intval($port);
            $port = $oldPort + 1;
            $portInUse = ConsoleColor::yellow("\n\tPort {$port} has been assigned, {$oldPort} is in use.") . "\n";
        }

        $project_dir = static::$rootpath; //__DIR__;

        $dir = realpath($project_dir . '/public/');

        if ($host === 'expose') {
            $host = self::UIP;
        }

        $output =  ConsoleColor::green("\n\tPHP Built-In Web Server Started for Webby \n\n");
        $output .= ConsoleColor::green("\tNavigate to ");
        $output .= ConsoleColor::cyan("http://{$host}:{$port} ");
        $output .= ConsoleColor::green("to view your project.\n");

        if ($host === self::UIP) {

            $clientIp = static::getLocalIPAddress();

            $output .= ConsoleColor::green("\n\tYou can also access your project at ");
            $output .= ConsoleColor::cyan("http://{$clientIp}:{$port} \n");
        }

        $output .= $portInUse ?? '';

        $output .= ConsoleColor::yellow("\n\tPress Ctrl+C to stop Webby Server!");
        $output .= " \n";

        echo ConsoleColor::green($output) . "\n\n";

        static::runSystemCommand('php -S ' . $host . ':' . $port . ' -t "' . $dir . '"');
    }

    /**
     * Check if a port is open
     *
     * @param string $host
     * @param int $port
     * @return bool
     */
    private static function checkPort(string $host, int $port): bool
    {
        $connection = @fsockopen($host, $port);

        if (is_resource($connection)) {
            fclose($connection);
            return true;
        } else {
            return false;
        }
    }

    private static function getOS()
    {
        return (stripos(PHP_OS, "WIN") === 0) ? "WINDOWS" : "UNIX";
    }

    /**
     * Get the local IP address based on the operating system.
     *
     * @return string The local IP address or '127.0.o.1'.
     */
    private static function getLocalIPAddress()
    {

        if (static::getOS() === 'WINDOWS') {
            // Windows OS
            $output = shell_exec('ipconfig');
            preg_match('/IPv4 Address[.\s]*:\s*([\d.]+)/', $output, $matches);
        } else {
            // Unix-like OS (Linux, macOS)
            $output = shell_exec('ifconfig');
            preg_match('/inet (?!127\.0\.0\.1)(\d+\.\d+\.\d+\.\d+)/', $output, $matches);
        }

        $localIp = isset($matches[1]) ? $matches[1] : '127.0.0.1';

        return $localIp;
    }

    /**
     * Quit Webby Server
     *
     * @param array $args Console arguments
     * @param int $ttq Time given to quit server
     * @return void
     */
    public static function quitServer(array $args = [], int $ttq = 10): void
    {

        $count = $ttq;
        $port = static::DEFAULT_PORT;

        if (isset($args[2]) && $args[2] === '--port') {
            $port = (int)$args[3];
        }

        if (isset($args[2]) && ($args[2] === '--in' || $args[2] === 'in')) {
            $count = (int)$args[3];
        }

        if (isset($args[4]) && ($args[4] === '--in' || $args[4] === 'in')) {
            $count = (int)$args[5];
        }

        echo ConsoleColor::yellow("\n\tQuitting Webby Server on Port: {$port} \n\t\t");

        while ($count > 0) {
            echo ConsoleColor::yellow(".");
            sleep(1);
            $count--;
        }

        echo ConsoleColor::yellow("\n\tDone! \n\n");

        static::consoleEnv();

        if (static::getOS() === "WINDOWS") {
            static::quitWindows($port);
            exit;
        }

        if (static::getOS() === "UNIX") {
            static::quitUnix($port);
            exit;
        }

        exec("fuser -n tcp -k {$port}");
    }

    /**
     * Quit Windows OS
     *
     * @param int $port
     * @return void
     */
    private static function quitWindows($port = self::DEFAULT_PORT)
    {
        // Find the Process ID
        $findPID = "netstat -ano | findstr :{$port}";
        $output = [];
        exec($findPID, $output);

        $PID = 0;
        $pidInfo = [];

        if (count($output) > 0) {
            // Extract PID from the output
            $pidInfo = explode(" ", trim($output[0]));
            $PID = intval(array_pop($pidInfo));
        }

        if ($PID > 0) {
            exec("taskkill /F /PID {$PID}", $ouput);
            $output =   " \n";
            $output .=  ConsoleColor::white("\tPort {$port} stopped sucessfully", 'light', 'green') . " \n";
            echo $output . "\n";
            exit;
        }

        $output =   " \n";
        $output .=  ConsoleColor::white("\tNo process found using port {$port}", 'light', 'red') . " \n";
        echo $output . "\n";
    }

    /**
     * Quit Unix OS
     *
     * @param int $port
     * @return void
     */
    private static function quitUnix($port = self::DEFAULT_PORT)
    {

        $PID = 0;

        // Find the Process ID
        $findPID = "lsof -t -i:{$port}";
        $PID = exec($findPID, $output);

        $output = '';

        if ($PID) {
            exec("kill -9 $PID");
            $output =   " \n";
            $output .=  ConsoleColor::white("\tPort {$port} stopped sucessfully", 'light', 'green') . " \n";
            echo $output . "\n";
            exit;
        }

        $output =   " \n";
        $output .=  ConsoleColor::white("\tNo process found using port {$port}", 'light', 'red') . " \n";
        echo $output . "\n";
    }

    /**
     * Run Webby Console
     *
     * @param array $args
     * @return void
     */
    public static function run(array $args): void
    {
        if (
            (isset($args[2]) && $args[2] === '--host')
            && (isset($args[4]) && $args[4] === '--port')
        ) {
            Console::serve($args);
        } else if (isset($args[2]) && $args[2] === '--ssl') {
            Console::serve($args);
        } else if (isset($args[2]) && $args[2] === '--host') {
            Console::serve($args);
        } else if (isset($args[2]) && $args[2] === '--port' && $args[1] !== 'quit') {
            Console::serve($args);
        } else if (isset($args[1]) && $args[1] === 'serve') {
            Console::serve();
        } else if (isset($args[1]) && $args[1] === 'set' && isset($args[2]) && $args[2] === '--env') {
            Console::setenv();
        } else if (isset($args[1]) && $args[1] === 'quit') {
            Console::quitServer($args);
        } /*else if (isset($args[1]) && $args[1] === '--help') {
            Console::showHelp();
        }*/ else if (!empty($args[1])) {
            Console::executeCommand($args);
        } else if (!isset($args[1])) {
            \Base\Console\Commands\Help::showHelp();
        } else {
            Console::noCommand();
        }
    }
}

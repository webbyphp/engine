<?php

use Base\Helpers\DotEnvWriter;
use Base\Console\ConsoleColor;
use Base\Controllers\ConsoleController;

class Maintenance extends ConsoleController
{

    /**
     * Console keyword
     *
     * @var string
     */
    private $keyword = 'app.mode.on';

    private $maintenanceFile;
    private $maintenanceDir;

    public function __construct()
    {
        parent::__construct();

        $this->maintenanceDir = WRITABLEPATH . 'maintenance' . DS;
        $this->maintenanceFile = $this->maintenanceDir . '/maintenance.lock';
    }

    /**
     * Turn on
     * Used with webby command
     *
     * @return void
     */
    public function on($useFile = '')
    {
        $exists = $this->check();

        if ($exists) {
            $this->turnOn();
        }

        if ($useFile === '--file') {
            $this->maintenanceLock('disable');
            exit;
        }

        echo ConsoleColor::yellow("Application is now online (maintenance mode OFF)") . "\n";
    }

    /**
     * Turn off
     * Used with webby command
     * 
     * @return void
     */
    public function off($useFile = '')
    {
        $exists = $this->check();

        if ($exists) {
            $this->turnOff();
        }

        if ($useFile === '--file') {
            $this->maintenanceLock('enable');
            exit;
        }

        echo ConsoleColor::yellow("Application is now offline (maintenance mode ON)") . "\n";
        $this->showBypassInfo();
    }

    /**
     * Status
     * Used with webby command
     * 
     * @return void
     */
    public function mode($useFile = 'status')
    {
       
        $this->maintenanceLock($useFile);
   

        // echo ConsoleColor::yellow("Application is now offline (maintenance mode ON)") . "\n";
        // $this->showBypassInfo();
    }

    /**
     * Check if key exists
     *
     * @return bool
     */
    private function check()
    {

        $exists = false;

        if ((new DotEnvWriter)->exists($this->keyword)) {
            $exists = true;
        }

        return $exists;
    }

    /**
     * Turn App Mode On
     *
     * @return void
     */
    private function turnOn()
    {
        $dotenv = new DotEnvWriter();

        $mode = $dotenv->getValue($this->keyword);

        if ($mode === "false") {
            $dotenv->setValue($this->keyword, str_replace('"', '', "true"));
        }
    }

    /**
     * Turn App Mode Off
     *
     * @return void
     */
    public function turnOff()
    {
        $dotenv = new DotEnvWriter();

        $mode = $dotenv->getValue($this->keyword);

        if ($mode === "true") {
            $dotenv->setValue($this->keyword, str_replace('"', '', "false"));
        }
    }

    /**
     * Show maintenance bypass information
     *
     * @return void
     */
    public function showBypassInfo()
    {
        echo "\n" . ConsoleColor::cyan("Maintenance Bypass Options:") . "\n";
        echo ConsoleColor::white("- URL Parameter: ") . ConsoleColor::green("?bypass=your_bypass_key") . "\n";
        echo ConsoleColor::white("- HTTP Header: ") . ConsoleColor::green("X-Maintenance-Bypass: your_bypass_key") . "\n";
        echo ConsoleColor::white("- IP Allowlist: ") . ConsoleColor::green("Configure app.maintenance.bypass.ips") . "\n";
        echo ConsoleColor::white("- Admin Session: ") . ConsoleColor::green("Enable app.maintenance.bypass.admin") . "\n";
        echo ConsoleColor::white("- Dev Mode: ") . ConsoleColor::green("Enable app.maintenance.bypass.dev in development") . "\n";
        echo ConsoleColor::white("- Configure bypass key: ") . ConsoleColor::green("app.maintenance.bypass.key in .env") . "\n\n";
    }

    public function maintenanceLock($command)
    {

        switch ($command) {
            case 'enable':
            case 'on':
                $message = $argv[2] ?? '';
                $estimatedTime = $argv[3] ?? '';
                $this->enable($message, $estimatedTime);
                break;
                
            case 'disable':
            case 'off':
                $this->disable();
                break;
                
            case 'status':
            default:
                $this->showStatus();
                break;
        }
    }

    /**
     * Enable maintenance mode
     *
     * @param string $message Optional maintenance message
     * @param string $estimatedTime Optional estimated completion time
     * @return bool
     */
    public function enable(string $message = '', string $estimatedTime = ''): bool
    {
        $data = [
            'enabled_at' => date('Y-m-d H:i:s'),
            'message' => $message ?: 'App is currently under maintenance. Please check back later.',
            'estimated_time' => $estimatedTime,
            'enabled_by' => get_current_user() . '@' . gethostname()
        ];

        $content = "<?php\n";
        $content .= "// Maintenance mode enabled\n";
        $content .= "// DO NOT DELETE THIS FILE WHILE MAINTENANCE IS ACTIVE\n";
        $content .= "return " . var_export($data, true) . ";\n";

        $result = file_put_contents($this->maintenanceFile, $content);
        
        if ($result !== false) {
            echo "Maintenance mode ENABLED\n";
            echo "Enabled at: " . $data['enabled_at'] . "\n";
            echo "Message: " . $data['message'] . "\n";
            if ($estimatedTime) {
                echo "Estimated completion: " . $estimatedTime . "\n";
            }
            return true;
        }
        
        echo "Failed to enable maintenance mode\n";
        return false;
    }

    /**
     * Disable maintenance mode
     *
     * @return bool
     */
    protected function disable(): bool
    {

        if (!$this->isEnabled()) {
            echo ConsoleColor::cyan("Maintenance mode is already disabled") . "\n";
            return true;
        }

        if (unlink($this->maintenanceFile)) {
            echo "Maintenance mode DISABLED\n";
            echo "App is now live!\n";
            return true;
        }
        
        echo "Failed to disable maintenance mode\n";
        return false;
    }

    /**
     * Check if maintenance mode is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return file_exists($this->maintenanceFile);
    }

    /**
     * Get maintenance status and details
     *
     * @return array
     */
    public function getStatus(): array
    {
        if (!$this->isEnabled()) {
            return [
                'enabled' => false,
                'message' => 'App is running normally'
            ];
        }

        $data = include($this->maintenanceFile);
        return array_merge(['enabled' => true], $data);
    }

    /**
     * Display current status
     *
     * @return void
     */
    public function showStatus(): void
    {
        $status = $this->getStatus();
        
        if ($status['enabled']) {
            echo "Maintenance mode is ACTIVE\n";
            echo "Enabled at: " . ($status['enabled_at'] ?? 'Unknown') . "\n";
            echo "Message: " . ($status['message'] ?? 'No message') . "\n";
            echo "Enabled by: " . ($status['enabled_by'] ?? 'Unknown') . "\n";
            if (!empty($status['estimated_time'])) {
                echo "Estimated completion: " . $status['estimated_time'] . "\n";
            }
        } else {
            echo ConsoleColor::green("App is running normally\n");
        }
    }

}

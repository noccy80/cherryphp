<?php

namespace Cherry\Cli;

use \Cherry\Core\ObjectManager;

class ServiceController extends ConsoleApplication {
    private $serviceuri;
    function __construct($serviceuri,$dir) {
        $this->serviceuri = $serviceuri;
        parent::__construct($dir);
    }
    function getApplicationInfo() {
        return [
            "appname" => "ServiceWrapper",
            "description" => "Wrapper for {$this->serviceuri}",
            "version" => "1.0.0"
        ];

    }
    function setup() {
        $this->addArgument("h","help","Show this help");
        $this->addCommand("start", "Start the service if it is not running");
        $this->addCommand("stop", "Stop the service if it is running");
        $this->addCommand("restart", "Restart the service, or start if not running");
        $this->addCommand("reload", "Reload the service by sending it a SIGHUP");
        $this->addCommand("status", "Show the status of the service");
    }
    function main() {
        $svc = ObjectManager::getObject($this->serviceuri);
        if (count($this->parameters) > 0) {
            switch (strtolower($this->parameters[0])) {
                case "start":
                    if ($svc->isRunning()) {
                        $this->write("Already running.\n");
                        return;
                    }
                    $this->write("Starting ... ");
                    $svc->start();
                    $this->write("Done\n");
                    break;
                case "stop":
                    if (!$svc->isRunning()) {
                        $this->write("Not running.\n");
                        return;
                    }
                    $this->write("Stopping ... ");
                    $svc->stop();
                    $this->write("Done\n");
                    break;
                case "restart":
                    $this->write("Restarting service ... ");
                    if ($svc->isRunning()) {
                        $svc->stop();
                    }
                    $svc->start();
                    $this->write("Done\n");
                    break;
                case "status":
                    if ($svc->isRunning())
                        $this->write("Running.\n");
                    else
                        $this->write("Not running.\n");
                    break;
                default:
                    $this->warn("No parameters or arguments found. Try -h or help.\n");
            }
        } else {
            $this->usage();
        }
    }
}

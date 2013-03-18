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
        $this->addCommand("info","Show information on the state of the service");
    }
    function usageinfo() {
        $this->write("Upgrading the service:\n    The service can be upgraded with the -u or --upgrade option\n");

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
                case "reload":
                    if ($svc->isRunning()) {
                        $this->write("Reloading service ... ");
                        $svc->reload();
                        $this->write("Done\n");
                    } else {
                        $this->write("Service not running.\n");
                    }
                    break;
                case "status":
                    if ($svc->isRunning())
                        $this->write("Running.\n");
                    else
                        $this->write("Not running.\n");
                    break;
                case "info":
                    $this->write("Service Information:\n\n");
                    $this->write("    Running . . . . : %s\n", ($svc->isRunning()?"Yes":"No"));
                    if ($svc->isRunning())
                        $this->write("    Process ID. . . : %d\n", $svc->getServicePid());
                    $this->write("    Class . . . . . : %s\n", get_class($svc));
                    $this->write("    Service ID. . . : %s\n", $svc->getServiceId());
                    $this->write("    UUID. . . . . . : %s\n", $svc->getUuid());
                    $this->write("\nProperties:\n\n");
                    $prop = ObjectManager::getObjectProperties($this->serviceuri);
                    $m = 0;
                    foreach($prop as $k=>$v) $m = max($m,strlen($k));
                    foreach($prop as $k=>$v) {
                        if (is_bool($v)) {
                            $v = ($v===true)?"True":"False";
                        } elseif (is_numeric($v)) {

                        } elseif (is_string($v)) {
                            $v = "\"{$v}\"";
                        } elseif (is_null($v)) {
                            $v = "Null";
                        }
                        $this->write("    %-{$m}s : %s\n", $k, $v);
                    }
                    $this->write("\n");
                    break;
                case "propset":
                case "propget":
                default:
                    $this->warn("No parameters or arguments found. Try -h or help.\n");
            }
        } else {
            $this->usage();
        }
    }
}

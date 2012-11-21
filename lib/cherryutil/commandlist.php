<?php

namespace CherryUtil;
use Cherry\Cli\Ansi;

class CommandList {
    static $instance = null;
    private $bundles = array();
    static function getInstance() {
        if (!self::$instance) self::$instance = new CommandList();
        return self::$instance;
    }
    public function registerBundle(CommandBundle $bundle) {
        $this->bundles[] = $bundle;
    }
    public function getCommands() {
        $cmdlist = array();
        foreach($this->bundles as $bundle) {
            $cmdbundle = $bundle->getCommands();
            $cmdlist = array_merge($cmdlist,$cmdbundle);
        }
        return $cmdlist;
    }
    public function findCommand($command) {
        $cl = $this->getCommands();
        foreach($cl as $cmd) {
            if ($cmd->getCommand() == $command) {
                return $cmd;
            }
        }
        return null;
    }
}

$cmds = glob(dirname(__FILE__).'/commands/*.php');
foreach ($cmds as $cmd) require $cmd;

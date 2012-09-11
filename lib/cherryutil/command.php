<?php

namespace cherryutil\commands;

abstract class CommandBundle {
}

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

class Command {

    private $command;
    private $arguments;
    private $description;
    private $cmdfunc;
    private $helptopic;

    function __construct($command,$arguments,$description,$cmdfunc,$helptopic=null) {
        if (!is_callable($cmdfunc))
            throw new \Exception("CommandFunc is not callable");
        
        $this->command = $command;
        $this->arguments = $arguments;
        $this->description = $description;
        $this->cmdfunc = $cmdfunc;
        $this->helptopic = $helptopic;
    }
    
    function __toString() {
        return sprintf("%-20s %s",$this->command,$this->description);
    }
    
    function __invoke() {
        $args = func_get_args();
        return call_user_func_array($this->cmdfunc,$args);
    }
    
    function getCommand() {
        return $this->command;
    }
    
    function getSynopsis() {
        return sprintf("%s %s", $this->command, $this->arguments);
    }
    
    function getDescription() {
        return $this->description;
    }
    
    function getHelp() {
        if (!$this->helptopic) return;
        if (!defined('CHERRY_HELP')) {
            $help = CHERRY_LIB.'/share/help/'.$this->helptopic;
            if (file_exists($help))
                return file_get_contents($help);
        }
    }
    
}

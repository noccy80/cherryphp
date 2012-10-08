<?php

namespace cherryutil\commands;
use Cherry\Cli\Ansi;

abstract class CommandBundle {
    protected function parseOpts(array $args,array $rules) {

        $out = array();
        for($optidx = 0; $optidx < count($args); $optidx++) {
            $opt = $args[$optidx];
            $matched = false;
            foreach($rules as $name=>$rule) {
                if ($rule[strlen($rule)-1] == ':') {
                    $rulestr = substr($rule,0,strlen($rule)-1);
                    if ($opt == $rulestr) {
                        $out[$name] = $args[$optidx+1];
                        $optidx++;
                        $matched = true;
                    }
                } elseif ($rule[0] == '+') {
                    if ($opt == $rule) {
                        $out[$name] = true;
                        $matched = true;
                    }
                }
            }
            if (!$matched) {
                fprintf(STDERR,"Unknown option: %s\n", $opt);
            }
        }
        return $out;

    }

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
        return sprintf(Ansi::setBold()."%-20s".Ansi::clearBold()." %s",$this->command,$this->description);
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

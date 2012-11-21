<?php

namespace CherryUtil;
use Cherry\Cli\Ansi;

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

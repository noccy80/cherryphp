<?php

namespace Cherry\Cli;

abstract class ConsoleApplication extends \Cherry\Application {

    private $arguments = array();
    private $commands = array();

    protected $parameters = array();

    function __construct($app) {
        global $argv;
        parent::__construct($app);

        \cherry\log(\cherry\LOG_DEBUG,'Spawning application');
        $this->init();
        $opts = '';
        $lopts = array();
        foreach($this->arguments as $opt) {
            $opts .= $opt->argument;
            $lopts[] = $opt->longargument;
        }
        // Parse the arguments
        $parsed = getopt($opts,$lopts);
        foreach($this->arguments as $optkey=>$opt) {
            if (array_key_exists($optkey,$parsed)) {
                $optval = $parsed[$optkey];
                if ($opt->type == 'multi') {
                    if (!is_array($opt->value)) $opt->value = array();
                    $opt->value[] = $optval;
                } elseif ($opt->type == 'string') {
                    $opt->value = $optval;
                } else {
                    $opt->value = true;
                }
                if ($opt->bind) {
                    if (is_array($opt->bind)) {
                        list($obj,$prop) = $opt->bind;
                    } else {
                        $obj = $this;
                        $prop = $opt->bind;
                    }
                    $obj->{$prop} = $opt->value;
                }
            }
        }
        //var_dump($this->arguments);
        //var_dump($parsed);
        // Remove all parsed values to end up with just the parameters
        $cparam = array();
        // Start at 1 to skip command name.
        for($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            $copt = null;
            if ($arg[0] == '-') { // Look up arguments
                if ($arg[1] == '-') { // Long argument
                    foreach($this->arguments as $opt) {
                        if ($arg == '--'.$opt->longargument) {
                            printf("Matched %s...\n", $arg);
                            $copt = $opt;
                            break;
                        }
                    }
                } else { // Short arguments
                    foreach($this->arguments as $opt) {
                        if ($arg[1] == $opt->argument[0]) {
                            $copt = $opt;
                            break;
                        }
                    }
                }
            }
            if ($copt) {
                if (strlen($this->arguments) > 1) {
                    $i++; // Skip the next item
                }
            } else {
                $cparam[] = $arg;
            }
        }
        $this->parameters = $cparam;
    }
    protected function hasArgument($arg) {
        if (array_key_exists($arg,$this->arguments)) {
            $opt = $this->arguments[$arg];
            if ($opt->value) return true;
        }
        return false;
    }
    protected function getArgument($arg) {
        if (array_key_exists($arg,$this->arguments))
            return $this->arguments[$arg]->value;
        return null;
    }

    function run() {
        $this->main();
    }

    abstract function main();
    abstract function getApplicationInfo();

    protected function addCommand($command,$info,$bind=null) {
        $co = new \Data\DataBlob(array(
            'command' => $command,
            'info' => $info,
            'bind' => $bind
        ));
        $this->commands[] = $co;
    }
    protected function addArgument($arg,$long,$info,$bind=null) {
        $ak = $arg[0];
        if (strlen($arg) == 3) {
            $type = 'multi';
            $value = array();
        } elseif (strlen($arg) == 2) {
            $type = 'string';
            $value = null;
        } elseif (strlen($arg) == 1) {
            $type = 'boolean';
            $value = false;
        }
        $ao = new \Data\DataBlob(array(
            'argument' => $arg,
            'longargument' => $long,
            'information' => $info,
            'type' => $type,
            'bind' => $bind,
            'value' => $value
        ));
        $this->arguments[$ak] = $ao;
    }

    function init() {
        \cherry\log(\cherry\LOG_DEBUG, 'Warning: application does not override init().');
    }
    function usage() {
        $this->usageheader();
        $this->usagearguments();
        $this->usagecommands();
        $this->usageinfo();
    }
    protected function usageheader() {
        $ai = $this->getApplicationInfo();
        fprintf(STDERR,"%s version %s - %s\n", $ai['appname'], $ai['version'], $ai['description']);
        if (!empty($ai['copyright']))
            fprintf(STDERR,"%s\n\n", $ai['copyright']);
    }
    protected function usagearguments() {
        global $argv;
        $cmd = basename($argv[0]);
        $args = '';
        $argsm = array();
        $argss = array();
        foreach($this->arguments as $key=>$opt) {
            switch($opt->type) {
            case 'multi':
            case 'string':
                $argsm[] = sprintf("[-%s val]", $key);
                break;
            default:
                $argss[] = $key;
                break;
            }
        }
        $args = '[-'.join('',$argss).']';
        if (count($argsm)>0)
            $args.= ' '.join(' ',$argsm);
        fprintf(STDERR,"Usage:  %s %s ..\n\nArguments:\n", $cmd, $args);
        foreach($this->arguments as $key=>$opt) {
            switch($opt->type) {
            case 'multi':
            case 'string':
                $argstr = sprintf("-%s val", $key);
                if ($opt->longargument)
                    $argstr.= sprintf(", --%s val", $opt->longargument);
                break;
            default:
                $argstr = sprintf("-%s", $key);
                if ($opt->longargument)
                    $argstr.= sprintf(", --%s", $opt->longargument);
                break;
            }
            $info = $opt->information;
            fprintf(STDERR,"    %-20s %s\n", $argstr,$info);
        }
        fprintf(STDERR,"\n");
    }
    protected function usagecommands() {
        if (count($this->commands) > 0) {
            fprintf(STDERR,"Commands:\n");
            foreach($this->commands as $cmd) {
                $ow = wordwrap($cmd->info,50);
                $ow = str_replace("\n","\n".str_repeat(" ",34),$ow);
                $ct = $cmd->command;
                fprintf(STDERR,"    %-28s  %s\n",$ct,$ow);
            }
            fprintf(STDERR,"\n");
        }
    }
    protected function usageinfo() {
        fprintf(STDERR,"This application does not provide any additional usageinfo().\n\n");
    }
    
    private static function showError($ca,$type,$message,$file,$line,$log,$bt) {
        if (function_exists('ncurses_end')) @ncurses_end();
        $ca->error("\033[1m%s:\033[22m\n    %s\n",$type,$message);
        $ca->error("\033[1mSource:\033[22m\n    %s (line %d)\n",$file,$line);
        $ca->error("%s\n",join("\n",$this->indent(Debug::getCodePreview($file,$line),4)));
        $ca->error("\033[1mBacktrace:\033[22m\n%s\n", join("\n",$this->indent($bt,4)));
        $ca->error("\033[1mDebug log:\033[22m\n%s\n",join("\n",$this->indent($log,4)));
        $ca->error("(Hint: Change the LOG_LENGTH envvar to set the size of the debug log buffer)\n");
    }

    private function indent(array $arr, $indent) {
        $arro = array();
        foreach($arr as $row) {
            $arro[] = str_repeat(" ",$indent).$row;
        }
        return $arro;
    }
    
    public function handleException(\Exception $exception) {
        \Cherry\debug("Unhandled exception %s in %s on line %d", get_class($exception), $exception->getFile(), $exception->getLine());
        $log = DebugLog::getDebugLog();
        $ca = \Cherry\Cli\Console::getAdapter();

        $bt = Debug::makeBacktrace($exception->getTrace());
        $errfile = $exception->getFile();
        $errline = $exception->getLine();
        $this->showError($ca,'Exception',$exception->getMessage().' ('.$exception->getCode().')',$errfile,$errline,$log,$bt);
    }

}

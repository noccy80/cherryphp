<?php

namespace cherryutil\commands;
use cherryutil\Command;
use cherryutil\CommandBundle;
use cherryutil\CommandList;
use App;

class SysInfoCommands extends CommandBundle {
    
    function getCommands() {
        return array(
            new Command('phpinfo','',
                    'PHP Information.', 
                    array($this,'phpinfo')),
            new Command('print-config','',
                    'Show the cherry configuration',
                    array($this,'cherrycfg')),
            new Command('php-extensions','',
                    'Show available PHP extensions',
                    array($this,'phpextensions')),
            new Command('php-defines','',
                    'Show set PHP defines',
                    array($this,'phpdefines')),
        );
    }
    
    function phpinfo() {
        \phpinfo();
    }
    
    function cherrycfg() {
        $this->con = \cherry\cli\Console::getAdapter();
        $cfg = App::config()->getAll();
        $this->dumpcfg($cfg,0);
        //$con->write(print_r($cfg,true)."\n");
    }
    private function dumpcfg($cfg,$r=0) {
        $pre = str_repeat(" ",$r*2);
        foreach($cfg as $key=>$val) {
            if (is_object($val)) {
                $this->con->write($pre."+ {$key}\n");
                $this->dumpcfg($val,$r+1);
            } elseif (is_array($val)) {
                $this->con->write($pre."- {$key}\n");
                $this->dumpcfg($val,$r+1);
            } else {
                $this->con->write($pre."  {$key} = {$val}\n");
            }
        }
    }
    
    function phpextensions() {
        $con = \cherry\cli\Console::getAdapter();
        $con->putColumns(\get_loaded_extensions(),25);
    }

    function phpdefines() {
        $args = func_get_args();
        $opts = $this->parseOpts($args,array(
            'like' => 'like:'
        ));
        $con = \cherry\cli\Console::getAdapter();
        $defs = \get_defined_constants();
        foreach($defs as $k=>$v) {
            if (
                empty($opts['like'])
                || 
                fnmatch($opts['like'],$k,\FNM_CASEFOLD)
            ) {
                $con->write("%s => %s\n", $k, $v);
            }
        }
    }

}

CommandList::getInstance()->registerBundle(new SysInfoCommands());

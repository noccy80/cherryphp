<?php

namespace cherryutil\commands;
use cherryutil\commands\Command;
use cherryutil\commands\CommandBundle;
use cherryutil\commands\CommandList;

class SysInfoCommands extends CommandBundle {
    
    function getCommands() {
        return array(
            new Command('phpinfo','',
                    'PHP Information.', 
                    array($this,'phpinfo')),
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

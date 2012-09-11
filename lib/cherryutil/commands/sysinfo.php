<?php

namespace cherryutil\commands;
use \cherryutil\commands\Command;
use \cherryutil\commands\CommandBundle;
use \cherryutil\commands\CommandList;

class SysInfoCommands extends CommandBundle {
    
    function getCommands() {
        return array(
            new Command('phpinfo','',
                    'PHP Information.', 
                    array($this,'phpinfo')),
            new Command('php-extensions','',
                    'Show available PHP extensions',
                    array($this,'phpextensions')),
        );
    }
    
    function phpinfo() {
        \phpinfo();
    }
    
    function phpextensions() {
        \var_dump(\get_loaded_extensions());
    }

}

CommandList::getInstance()->registerBundle(new SysInfoCommands());

<?php

namespace cherryutil\commands;
use \cherryutil\commands\Command;
use \cherryutil\commands\CommandBundle;
use \cherryutil\commands\CommandList;

class MvcCommands extends CommandBundle {
    
    function getCommands() {
        return array(
            new Command('view','<url>',
                    'Load the specified url via the MVC application and display the output', 
                    array($this,'view')),
        );
    }
    
    function view($url=null) {
    
    }

}

CommandList::getInstance()->registerBundle(new MvcCommands());

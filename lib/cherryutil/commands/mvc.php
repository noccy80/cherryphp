<?php

namespace CherryUtil\commands;
use cherryutil\Command;
use cherryutil\CommandBundle;
use cherryutil\CommandList;

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

<?php

namespace cherryutil\commands;
use \cherryutil\commands\Command;
use \cherryutil\commands\CommandBundle;
use \cherryutil\commands\CommandList;

class ScaffoldingCommands extends CommandBundle {
    
    function getCommands() {
        return array(); /*array(
            new Command('list-loaders','',
                    'List the available loaders.', 
                    array($this,'listloaders')),
            new Command('list-templates','',
                    'List the available application templates.', 
                    array($this,'listtemplates')),
        );*/
    }

}

CommandList::getInstance()->registerBundle(new ScaffoldingCommands());

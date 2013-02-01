<?php

namespace CherryUtil\commands;
use cherryutil\Command;
use cherryutil\CommandBundle;
use cherryutil\CommandList;

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

<?php

namespace cherryutil\commands;
use \cherryutil\commands\Command;
use \cherryutil\commands\CommandBundle;
use \cherryutil\commands\CommandList;

class HelpCommands extends CommandBundle {
    
    function getCommands() {
        return array(
            new Command('help','<command>',
                    'Show extended help on a command.', 
                    array($this,'help')),
        );
    }

    function help($command=null) {
        if (!$command) {
            $app = \lepton\Lepton::getInstance()->getApplication();
            $app->usage();
        } else {
            $cobj = \cherryutil\commands\CommandList::getInstance()->findCommand($command);
            printf("Command:\n    %s - %s\n\n",$command, $cobj->getDescription());
            printf("Synopsis:\n    %s\n\n",$cobj->getSynopsis());
            if ($help = $cobj->getHelp()) {
                printf("Description:\n");
                $l = explode("\n", $help);
                foreach($l as $line) printf("    %s\n", $line);
            }
        }
    }

}

CommandList::getInstance()->registerBundle(new HelpCommands());

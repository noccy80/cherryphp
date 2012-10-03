<?php

namespace cherryutil\commands;
use cherryutil\commands\Command;
use cherryutil\commands\CommandBundle;
use cherryutil\commands\CommandList;
use cherry\cpr\RepositoryList;

require_once('lib/cherry/cpr/repository.php');

class CpaCommands extends CommandBundle {
    
    function getCommands() {
        return array(
            new Command('cpr','<command> [...]',
                    'Cherry Package Repository commands', 
                    array($this,'cprcommand'),'commands/cpr.txt'),
        );
    }
    
    function cprcommand($cmd=null) {
        $args = func_get_args();
        $con = \cherry\cli\Console::getAdapter();
        switch($cmd) {
            case 'add':
                $file = $args[1];
                $opts = $this->parseOpts(array_slice($args,2),array(
                    'global' => '+global',
                    'verbose' => '+verbose'
                ));
                $manifesturl = $file.'/manifest.json';
                $con->write("Loading %s...\n", $manifesturl);
                $reg = new RepositoryList();
                $reg->addRepository($file);
                break;
            case 'update':
                $opts = $this->parseOpts(array_slice($args,1),array(
                    'global' => '+global',
                    'verbose' => '+verbose'
                ));
                $reg = new RepositoryList();
                foreach($reg as $repository) {
                    $repository->update();
                }
                break;
            default:
                $con->warn("No such cpr sub-comand: %s, try 'help cpr'\n", $cmd);
                break;
        }
    }
}

CommandList::getInstance()->registerBundle(new CpaCommands());

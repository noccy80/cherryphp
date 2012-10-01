<?php

namespace cherryutil\commands;
use cherryutil\commands\Command;
use cherryutil\commands\CommandBundle;
use cherryutil\commands\CommandList;

class InstallCommands extends CommandBundle {
    
    function getCommands() {
        return array(
            new Command('install-all','[+tools] [+verbose] [+replace] [+yes] [to <dest>]',
                    'Install CherryPHP to the system.', 
                    array($this,'installAll'), 'commands/install.install_all.txt'),
            new Command('install-tools','[+verbose]',
                    'Install the cherry utilities for the current user.', 
                    array($this,'installTools'))
        );
    }

    function installAll() {
    
        $con = \cherry\cli\Console::getConsole();
        $foh = new \cherryutil\fileops\FileOpHelper();

        $opts = func_get_args();
        $tools = false;
        $cont = false;
        $dest = '/opt/cherryphp';
        for($optidx = 0; $optidx < count($opts); $optidx++) {
            $opt = $opts[$optidx];
            switch($opt) {
            case '+tools':
                $tools = true;
                break;
            case '+verbose':
                $foh->setVerbose(true);
                break;
            case '+replace':
                $foh->setReplace(true);
                break;
            case '+yes':
                $cont = true;
                break;
            case 'to':
                $dest = $opts[$optidx+1];
                $optidx++;
                break;
            default:
                $con->write("Unknown argument: %s\n", $opt);
                return 1;
            }
        }
    
        $con->write("Installing CherryPHP...\n");

        try {
            $foh->installdir(CHERRY_LIB.'/lib',$dest.'/lib', null);
            $foh->installdir(CHERRY_LIB.'/bin',$dest.'/bin', null);
            $foh->installdir(CHERRY_LIB.'/share',$dest.'/share',null);

            $foh->install(CHERRY_LIB.'/docs/cherrydoc.ini',$dest.'/docs/cherrydoc.ini',null);
            $foh->installdir(CHERRY_LIB.'/docs/manual',$dest.'/docs/manual',null);
            $foh->installdir(CHERRY_LIB.'/docs/template',$dest.'/docs/template',null);
            $foh->installdir(CHERRY_LIB.'/docs/images',$dest.'/docs/images',null);
        } catch (\cherryutil\fileops\FileopException $e) {
            $con->warn($e->getMessage());
            return 1;
        }

        $con->write("Building documentation...\n");
        $ret = null;
        exec("sh -c 'cd ".$dest."/docs ; ../bin/cherrydoc'", $out, $ret);
        if ($ret == 0)
            $con->write("The CherryPHP Documentation can be found at %s/docs/manual.html\n", $dest);

        if ($tools) {
            $this->installTools();
        } else {
            $con->write("Done\n");
        }
        
    }
    
    function installTools() {
        $con = \cherry\cli\Console::getConsole();
        $foh = new \cherryutil\fileops\FileOpHelper();
        $foh->setReplace(true);
        $home = getenv('HOME');
        $bindir = $home.'/bin';

        $con->write("Installing tools...\n");
        $tools = array(
            'cherrydoc',
            'cherry',
            'cherryview'
        );
        foreach($tools as $tool) {
            $foh->install(CHERRY_LIB.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.$tool,$bindir.DIRECTORY_SEPARATOR.$tool,0777);
        }
        $con->write("Done\n");
    }

}

CommandList::getInstance()->registerBundle(new InstallCommands());

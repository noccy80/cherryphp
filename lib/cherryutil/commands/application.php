<?php

namespace cherryutil\commands;
use cherryutil\commands\Command;
use cherryutil\commands\CommandBundle;
use cherryutil\commands\CommandList;

class ApplicationCommands extends CommandBundle {
    
    function getCommands() {
        return array(
            new Command('list-loaders','',
                    'List the available loaders.', 
                    array($this,'listloaders')),
            new Command('list-templates','',
                    'List the available application templates.', 
                    array($this,'listtemplates')),
            new Command('create','<apptemplate> <appns> [name <appname>] [+replace]',
                    'Creates a new application from an application template. Appns is required',
                    array($this,'createapp')),
            new Command('create-config','<type> [to <dest>]',
                    'Creates a new configuration template',
                    array($this,'createcfg')),
        );
    }
    
    function createcfg($type=null) {
        $args = func_get_args();
        $type = $args[0];
        $opts = $this->parseOpts(array_slice($args,1),array(
            'verbose' => '+verbose',
            'dest' => 'to:'
        ));
        if ($type) {
            $this->data = new \stdclass();
            $this->data->htmlroot = exec('pwd');
            $this->data->environment = 'prodution';
            $tpl = require CHERRY_LIB.'/share/configs/'.$type.'.php';
            var_dump($tpl);
        }
    }

    function listloaders() {
        $loaderpath = CHERRY_LIB.'/share/loaders';
        $it = new \FileSystemIterator($loaderpath,\FileSystemIterator::SKIP_DOTS);
        printf("Available loaders:\n");
        foreach($it as $loader) {
            $fn = $loader->__toString();
            if (fnmatch('*.ini',$fn)) {
                $meta = parse_ini_file($fn,true);
                $ename = (empty($meta['loader']['name']))?basename($fn,'.ini'):$meta['loader']['name'];
                $eversion = (empty($meta['loader']['version']))?'(Unknown version)':$meta['loader']['version'];
                printf("    %-16s %s %s\n", basename($fn,'.ini'), $ename, $eversion);
            }
        }
    }
    
    function createapp($template=null,$appns=null) {
        $con = \Cherry\Cli\Console::getConsole();
        if (!$appns) {
            printf("Use: create <template> <appns>\n");
            return 1;
        }

        $args = func_get_args();
        $opts = $this->parseOpts(array_slice($args,2),array(
            'replace' => '+replace',
        ));
        if (empty($opts['replace'])) $opts['replace'] = false;
        
        $con->write("Creating new project %s...\n", $appns);
        $tpath = CHERRY_LIB.'/share/projects/'.$template.'/';

        $rdi = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tpath, \FilesystemIterator::SKIP_DOTS));
        foreach($rdi as $file) {
            if (strpos((string)$file,'PKG-META')!==false) {
                $sub = explode(DIRECTORY_SEPARATOR,substr($file,strpos((string)$file,'PKG-META')+9));
                if ($sub[0] == 'triggers') {
                    require $file;
                }
            } else {
                $dest = str_replace($tpath,'./',$file);
                // Check paths
                $path = dirname($dest);
                if (!file_exists($path)) {
                    mkdir($path,0777,true);
                }
                // Copy file
                copy($file,$dest);
            }
        }
        \Cherry\Base\Event::invoke('cherryutil.application.post-setup', array(
            'approot' => \getcwd(),
            'appns' => $appns,
            'replace' => $opts['replace']
        ), $con);
        $con->write("Done\n");
    }
    
    function listtemplates() {
        $loaderpath = CHERRY_LIB.'/share/projects';
        $it = new \FileSystemIterator($loaderpath,\FileSystemIterator::SKIP_DOTS);
        printf("Available application templates:\n");
        foreach($it as $loader) {
            $fn = $loader->__toString();
            if (fnmatch('*.ini',$fn)) {
                $meta = parse_ini_file($fn,true);
                $ename = (empty($meta['project']['name']))?basename($fn,'.ini'):$meta['project']['name'];
                $eversion = (empty($meta['project']['version']))?'(Unknown version)':$meta['project']['version'];
                printf("    %-16s %s %s\n", basename($fn,'.ini'), $ename, $eversion);
            }
        }
    }

}

CommandList::getInstance()->registerBundle(new ApplicationCommands());

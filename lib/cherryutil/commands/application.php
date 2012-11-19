<?php

namespace cherryutil\commands;
use cherryutil\commands\Command;
use cherryutil\commands\CommandBundle;
use cherryutil\commands\CommandList;
use Cherry\Cli\Ansi;

class ApplicationCommands extends CommandBundle {

    function getCommands() {
        return array(
            new Command('list-loaders','',
                    'List the available loaders.',
                    array($this,'listloaders')),
            new Command('list-templates','',
                    'List the available application templates.',
                    array($this,'listtemplates')),
            new Command('list-configs','',
                    'List the available configuration templates.',
                    array($this,'listconfigs')),
            new Command('create','<apptemplate> <appns> [name <appname>] [+replace]',
                    'Creates a new application from an application template. Appns is required',
                    array($this,'createapp')),
            new Command('create-config','<type> [to <dest>]',
                    'Creates a new configuration template',
                    array($this,'createcfg')),
        );
    }

    function createcfg($type=null) {
        $term = \Cherry\Cli\Console::getAdapter();
        if (!$type) {
            fprintf(STDERR,"No such config. Try ".Ansi::setUnderline()."list-configs".Ansi::clearUnderline()."\n");
            return 1;
        }
        $args = func_get_args();
        $type = $args[0];
        $opts = $this->parseOpts(array_slice($args,1),array(
            'verbose' => '+verbose',
            'dest' => 'to:',
            'force' => '+force'
        ));
        if ($type) {
            $this->data = new TemplateStrings();
            $this->data->htmlroot = exec('pwd');
            $this->data->environment = 'prodution';
            $tpl = require CHERRY_LIB.'/share/configs/'.$type.'.php';
            $meta = parse_ini_file(CHERRY_LIB.'/share/configs/'.$type.'.ini',true);
            if (empty($opts['dest'])) {
                $out = $meta['config']['dest'];
            } else {
                $out = $opts['dest'];
            }
            fprintf(STDOUT,"Writing %s...\n", $out);
            if (file_exists($out)) {
                if (empty($opts['force']) || $opts['force'] == 0) {
                    fprintf(STDERR,"Error: File already exists! To replace use +force\n");
                    return 1;
                }
            }
            file_put_contents($out,trim($tpl)."\n");
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
        $con = \Cherry\Cli\Console::getAdapter();
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

    function listconfigs() {
        $loaderpath = CHERRY_LIB.'/share/configs';
        $it = new \FileSystemIterator($loaderpath,\FileSystemIterator::SKIP_DOTS);
        printf("Available configuration templates:\n");
        foreach($it as $loader) {
            $fn = $loader->__toString();
            if (fnmatch('*.ini',$fn)) {
                $meta = parse_ini_file($fn,true);
                $ename = (empty($meta['config']['name']))?basename($fn,'.ini'):$meta['config']['name'];
                $eversion = (empty($meta['config']['version']))?'(Unknown version)':$meta['config']['version'];
                printf("    %-16s %s %s\n", basename($fn,'.ini'), $ename, $eversion);
            }
        }
    }

}

class TemplateStrings {
    private $data = array();
    public function __get($key) {
        if (array_key_exists($key,$this->data)) return $this->data[$key];
        return sprintf('<%s>',$key);
    }
    public function __set($key,$value) {
        $this->data[$key] = $value;
    }
    public function __unset($key) {
        if (array_key_exists($key,$this->data)) unset($this->data[$key]);
    }
}

CommandList::getInstance()->registerBundle(new ApplicationCommands());

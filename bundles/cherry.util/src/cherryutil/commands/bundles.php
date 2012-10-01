<?php

namespace cherryutil\commands;
use cherryutil\commands\Command;
use cherryutil\commands\CommandBundle;
use cherryutil\commands\CommandList;

class BundleCommands extends CommandBundle {
    
    function getCommands() {
        return array(
            new Command('bundles','',
                    'List the installed bundles.', 
                    array($this,'listbundles'))
        );
    }
    
    function listbundles() {
        $loaderpath = CHERRY_LIB.'/bundles';
        $it = new \FileSystemIterator($loaderpath,\FileSystemIterator::SKIP_DOTS);
        printf("Installed bundles:\n");
        foreach($it as $bundle) {
            $fn = $bundle->__toString();
            if (file_exists($fn._DS_.'manifest.json')) {
                $meta = json_decode(file_get_contents($fn._DS_.'manifest.json'));
                $ename = (empty($meta->name))?basename($fn):$meta->name;
                $eversion = (empty($meta->version))?'(Unknown version)':$meta->version;
                $edescription = (empty($meta->description))?'???':$meta->description;
                printf("    %-16s %s %s\n", basename($fn), $ename, $eversion);
            }
        }
    }
    
    
}
CommandList::getInstance()->registerBundle(new BundleCommands());

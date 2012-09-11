<?php

namespace cherryutil\commands;
use \cherryutil\commands\Command;
use \cherryutil\commands\CommandBundle;
use \cherryutil\commands\CommandList;

class PackageCommands extends CommandBundle {
    
    function getCommands() {
        return array(
            new Command('package','<path> [to <dest.phar>] [with-stub <loaderstub>] [+template|+snapshot] [+extension]',
                    'Package an application into an archive', 
                    array($this,'package'),'commands/package.package.txt'),
            new Command('deploy','<package.phar> [+yes]',
                    'Deploy a phar package and install it.',
                    array($this,'deploy'),'commands/package.deploy.txt'),
            new Command('packageinfo','<package.phar>',
                    'Show information on a package', 
                    array($this,'packageinfo'),'commands/package.packageinfo.txt'),
            new Command('list-stubs','',
                    'List the available loader stubs', 
                    array($this,'liststubs'),'commands/package.liststubs.txt'),
        );
    }

    function package($source=null) {

        $con = \cherry\cli\Console::getConsole();

        if (!$source) {
            $con->write("You need to at least specify the source path.\n");
            return 1;
        }

        $opts = func_get_args();
        $opts = array_slice($opts,1);

        $loader = null;
        $compress = null;
        $includelib = false;
        $dest = $source.'.phar';
        $isExtension = false;
        for($optidx = 0; $optidx < count($opts); $optidx++) {
            $opt = $opts[$optidx];
            if ($optidx < count($opts) - 1) {
                $optarg = $opts[$optidx+1];
            } else {
                $optarg = null;
            }
            switch($opt) {
            case '+template':
                $tools = true;
                break;
            case 'with-stub':
                $loader = $optarg;
                $optidx++;
                break;
            case 'to':
                $dest = $optarg;
                $optidx++;
                break;
            case 'compress':
                $compress = $optarg;
                $optidx++;
                break;
            case '+extension':
                $isExtension = true;
                break;
            case '+standalone':
                $includelib = true;
                break;
            default:
                $con->write("Unknown argument: %s\n", $opt);
                return 1;
            }
        }
        
        $con->write('Scanning %s... ', $source);
        $appcfg = null;
        $extcfg = null;
        $itfile = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS));
        $files = array();
        foreach($itfile as $file) {
            $fn = $file->__toString();
            if (basename($fn) == 'application.ini') {
                $appcfg = parse_ini_file($fn,true);
            }
            if (basename($fn) == 'extension.ini') {
                $isExtension = true;
            }
            $files[] = $fn;
        }
        if (($isExtension) && (!file_exists($source.'/extension.ini'))) {
            $con->warn("Error: This doesn't seem to be an extension.\n");
            return 1;
        }
        
        if ($isExtension) {
            $ext = parse_ini_file($source.'/extension.ini',true);
            $meta = $ext['extension'];
            $meta['type'] = 'extension';
        } elseif (!empty($appcfg)) {
            $meta = $appcfg['application'];
            $meta['type'] = 'application';
        } else {
            $con->warn("Warning: Directory does not appear to contain neither an application or an extension\n");
            $meta = array(
                'name' => 'Unknown Application',
                'version' => '?.?',
                'type' => 'archive'
            );
        }

        $con->write("%s, %d files\n", $meta['type'], count($files));
        if (!empty($appcfg)) {
            $aname = (empty($appcfg['application']['name']))?'Unknown application':$appcfg['application']['name'];
            $aver = (empty($appcfg['application']['version']))?'(Unknown version)':$appcfg['application']['version'];
            $webindex = '/public/';
            $con->write("Preparing to archive %s %s\n",$aname,$aver);
        }
        $cliloader = (empty($appcfg['application']['loader']))?'loader.php':$appcfg['application']['loader'];
        $webroot = (empty($appcfg['application']['webroot']))?'/public/':$appcfg['application']['webroot'];
        $meta['loader'] = $cliloader;

        if ($compress == 'bz2') {
            if (substr($dest,-4,4) != '.bz2') $dest.='.bz2';
        } elseif ($compress == 'gzip') {
            if (substr($dest,-3,3) != '.gz') $dest.='.gz';
        } elseif ($compress == null) { 
        } else {
            $con->write("Unknown compression: %s\n", $compress);
        }

        $con->write("Cleaning up temporary files...\n");
        if (file_exists($dest)) unlink($dest);
        $phar = new \Phar($dest);
        if ($phar->canCompress() && ($compress)) {
            if ($compress == 'bz2') {
                $phar->compress(\Phar::BZ2);
            } elseif ($compress == 'gzip') {
                $phar->compress(\Phar::GZIP);
            } else {
                $con->write("Unknown compression: %s\n", $compress);
            }
        }
        $con->write("Creating package...\n");
        foreach($files as $file) {
            $phar->addFile($file,str_replace($source,'',$file));
        }
        
        if ($loader) {
            $loaderpath = CHERRY_LIB.'/share/stubs';
            $loadercfg = parse_ini_file($loaderpath.DIRECTORY_SEPARATOR.$loader.'.ini',true);
            if (!file_exists($loaderpath.DIRECTORY_SEPARATOR.$loader.'.ini')) {
                $con->write("Stub not found. Try list-stubs to find the available stubs\n");
                return 1;
            }
            $con->write("Embedding loader stub...\n");
            if (file_exists($loaderpath.DIRECTORY_SEPARATOR.$loader.'.stub')) {
                $loaderstub = @file_get_contents($loaderpath.DIRECTORY_SEPARATOR.$loader.'.stub');
            } else {
                $loaderstub = '';
            }
            if (!empty($loadercfg['stub']['defaultstub']) && ($loadercfg['stub']['defaultstub'])) {
                $loaderstub.= \Phar::createDefaultStub($cliloader,$webroot);
            }
            $phar->setStub($loaderstub);
        }

        $phar->addFromString('manifest.json',json_encode($meta));
        $con->write("Done\n");

    }
    
    function packageinfo($package=null) {
    
        $con = \cherry\cli\Console::getConsole();

        if (!$package) {
            $con->warn("No package specified.\n");
            return 1;
        }
    
        $fh = @fopen('phar://'.$package.'/manifest.json','r');
        if (!$fh) {
            $con->write("Not a valid cherry package: %s\n", $package);
            return 1;
        }
        $data = json_decode(fread($fh,65535));
        
        $con->write("Package: %s %s\n", $data->name, $data->version);
        $con->write("Type: %s\n", $data->type);

        $phar = new \Phar($package);
        foreach(new \RecursiveIteratorIterator($phar) as $file) {
            printf("%s (%d bytes)\n", $file, $file->getSize());
        }
    
    }

    function liststubs() {
        $loaderpath = CHERRY_LIB.'/share/stubs';
        $it = new \FileSystemIterator($loaderpath,\FileSystemIterator::SKIP_DOTS);
        printf("Available stubs:\n");
        foreach($it as $loader) {
            $fn = $loader->__toString();
            if (fnmatch('*.ini',$fn)) {
                $meta = parse_ini_file($fn,true);
                $ename = (empty($meta['stub']['name']))?basename($fn,'.ini'):$meta['stub']['name'];
                $eversion = (empty($meta['stub']['version']))?'(Unknown version)':$meta['stub']['version'];
                printf("    %-16s %s %s\n", basename($fn,'.ini'), $ename, $eversion);
            }
        }
    }
    
    function deploy() {
    
    }

}

CommandList::getInstance()->registerBundle(new PackageCommands());

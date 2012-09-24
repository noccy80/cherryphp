<?php

namespace Cherry\Extension;

use Cherry\Autoloader\Autoloaders;
use Cherry\Autoloader\Autoloader;

class ExtensionManager {
    
    private static $instance = null;
    private static $extensions = array();

    public static function getInstance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public static function __callstatic($cmd,$args) {
        return call_user_func_array(array(self::getInstance(),$cmd),$args);
    }
    
    public function load($extension) {
        if (empty(self::$extensions[$extension])) {
            $bpath = CHERRY_EXTDIR._DS_.$extension;
            \Cherry\log(\Cherry\LOG_DEBUG, "Attempting to load extension %s from '%s'", $extension, $bpath);
            if (file_exists($bpath._DS_.'manifest.json')) {
                $extn = require_once $bpath._DS_.'extension.php';
                $info = json_decode(file_get_contents($bpath._DS_.'manifest.json'));
                // var_dump($info);
                if (is_object($extn)) {
                    $extn->initialize();
                }
                $bundlesrc = $bpath._DS_.'src';
                Autoloaders::register(new Autoloader($bundlesrc));
                self::$extensions[$extension] = $extn;
            } else {
                throw new ExtensionException("Extension ".$extension." not found.");
            }
        }
    }

}

class ExtensionException extends \Exception { }

abstract class Extension { }

<?php

namespace Cherry\Extension;

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
                // var_dump($info);
                if (is_object($extn)) {
                    $extn->initialize();
                }
                self::$extensions[$extension] = $extn;
            } else {
                throw new ExtensionException("Extension ".$extension." not found.");
            }
        }
    }

}

class ExtensionException extends \Exception { }

abstract class Extension { }

<?php

namespace Cherry\Autoloader;

class Autoloaders {
    
    private static $loaders = array();
    private static $registered = false;
    private static $lastloader = null;
    
    static function register(Autoloader $loader, $addtotop = false) {
        if (!self::$registered) {
            spl_autoload_register(array(__CLASS__,'_spl_autoload'),true,true);
        }
        \cherry\log(\cherry\LOG_DEBUG,'Autoloader: Registered loader %s', $loader);
        self::$loaders[] =& $loader;
    }

    public static function _spl_autoload($class) {
        \cherry\log(\cherry\LOG_DEBUG,'Autoload request: %s', $class);
        if (self::$lastloader) {
            if (self::$lastloader->autoload($class) === true) {
                if (class_exists($class)) {
                    return true;
                }
            }
        }
        foreach(self::$loaders as $loader) {
            if ($loader !== self::$lastloader) {
                if ($loader->autoload($class) === true) {
                    if (class_exists($class)) {
                        self::$lastloader =& $loader;
                        return true;
                    } else {
                        throw new \Exception('Autoloaded file, but class not found.');
                    }
                }
            }
        }
        return false;
    }
    
}

class Autoloader {
    
    private $path;
    
    function __construct($path) {

        $this->path = $path;
        
    }
    
    function __tostring() {
        return "[".$this->path."]";
    }
    
    function autoload($class) {

        \cherry\log(\cherry\LOG_DEBUG,' .. searching %s', $this->path);
        $file = \Cherry\unipath($this->path.DIRECTORY_SEPARATOR.strtolower(str_replace('\\',DIRECTORY_SEPARATOR,$class)).'.php');
        if ( file_exists($file) ){
            \cherry\log(\cherry\LOG_DEBUG,' .. found %s',$file);
            include_once $file;
            return true;
        }
        return false;
        
    }
    
}
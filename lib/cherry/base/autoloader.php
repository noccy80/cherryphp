<?php

namespace Cherry\Autoloader;

class Autoloaders {
    
    private static $loaders = array();
    private static $registered = false;
    
    static function register(Autoloader $loader, $addtotop = false) {
        if (!self::$registered) {
            spl_autoload_register(array(__CLASS__,'_spl_autoload'),true,true);
        }
        self::$loaders[] =& $loader;
    }

    public static function _spl_autoload($class) {
        foreach(self::$loaders as $loader) {
            if ($loader->autoload($class)) return true;
        }
        return false;
    }
    
}

class Autoloader {
    
    function __construct($path) {

        $this->path = $path;        
        
    }
    
    function autoload($class) {

        \cherry\log(\cherry\LOG_DEBUG,'Autoload request: %s', $class);
        $file = \Cherry\unipath($this->path.DIRECTORY_SEPARATOR.strtolower(str_replace('\\',DIRECTORY_SEPARATOR,$class)).'.php');
        if ( @include_once $file ) {
            \cherry\log(\cherry\LOG_DEBUG,'Included %s',$file);
            return true;
        }
        return false;
        
    }
    
}
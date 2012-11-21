<?php

namespace Cherry\Autoloader;

class Autoloaders {

    private static $loaders = array();
    private static $registered = false;
    private static $lastloader = null;

    const
            AL_DEFAULT  = 0,
            AL_PSR_0    = 1,
            AL_CHERRY   = 2,
            AL_LEPTON   = 3;

    static function add($match,$data,$type = self::AL_DEFAULT) {

    }

    static function register(Autoloader $loader, $addtotop = false) {
        if (!self::$registered) {
            spl_autoload_register(array(__CLASS__,'_spl_autoload'),true,true);
        }
        \cherry\log(\cherry\LOG_DEBUG,'Autoloader: Registered loader %s', $loader);
        self::$loaders[] =& $loader;
    }

    public static function _spl_autoload($class) {
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

        $file = \Cherry\unipath($this->path._DS_.strtolower(str_replace('\\',_DS_,$class)).'.php');
        \cherry\log(\cherry\LOG_DEBUG,' .. searching %s for %s', $this->path, $file);
        $afile = dirname($file).'/_autoload.php';
        if ( file_exists($afile)) {
            \cherry\log(\cherry\LOG_DEBUG,'Autoload request matched _autoload: %s (%s)', $class, $this->path);
            // \cherry\log(\cherry\LOG_DEBUG,' .. found %s',$file);
            include_once $afile;
            return true;
        } elseif ( file_exists($file) ){
            \cherry\log(\cherry\LOG_DEBUG,'Autoload request: %s (%s)', $class, $this->path);
            // \cherry\log(\cherry\LOG_DEBUG,' .. found %s',$file);
            include_once $file;
            return true;
        }
        return false;

    }

}

class AutoloaderException extends \Exception { }

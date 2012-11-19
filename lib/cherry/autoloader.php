<?php

namespace Cherry;

/**
 * @brief PSR-0 compatible autoloader
 *
 *
 *
 * @code
 *  $al = new \Cherry\Autoloader('Cherry','system/cherry/','Cherry');
 *  $al->register();
 * @endcode
 *
 * This loader supports both relative and absolute paths:
 *
 *  - ./system/cherry/ is expected to live in the application root.
 *  - system/cherry/ points to same as above
 *  - /opt/cherryphp/system/cherry/ would indicate absolute path
 */
class Autoloader {

    private
            $paths = [];

    /**
     *
     * @param string $namespace
     * @param string $path
     * @param string $root
     */
    public function __construct($namespace, $path, $root=null) {
        if (substr($path,-1) != _DS_) $path.= _DS_;
        $this->paths[] = [
            $namespace,
            $path,
            $root
        ];
    }

    public function addPaths(array $path, array $path2 = null) {
        $args = func_get_args();
        foreach ($args as $arg) {
            if (is_string($arg)) {
                $this->paths[] = $arg;
            } elseif (count($arg) == 1) {
                $this->paths[] = [ null, $arg[0], null];
            } elseif (count($arg) == 2) {
                $this->paths[] = [ $arg[0], $arg[1], null];
            } elseif (count($arg) == 3) {
                $this->paths[] = [ $arg[0], $arg[1], $arg[2]];
            } else {
                $argstr = join(',',(array)$arg);
                user_error("Bad request to addPaths (in argument '{$argstr}'");
            }
        }
    }

    public function register($throw=false) {
        spl_autoload_register([$this, '_autoload'],$throw);
    }

    public function unregister() {
        spl_autoload_unregister([$this, '_autoload']);
    }

    public static function _autoload($class) {
        // Replace underscores in Pear_Style_ClassNames if needed
        if (strpos($class,_NS_)===false) {
            $lpath = str_replace('_',_DS_,$class);
        } else {
            $lpath = str_replace('_',_NS_,$class);
        }
        $lpath = join(_DS_,[ APP_ROOT , 'vendor' , $lpath ]);
    }

}



Autoloader::add('Cherry\*', CHERRY_LIB._DS_.'lib', 'Cherry' );

<?php

namespace xenon;

class xenon {
    private static $fwapi = null;
    private static $config = [
        'framework.preload' => [],
        'framework.debuglevel' => -1
    ];
    public static function autoloader($path,$base=null) {
        if (!self::$fwapi) die("xenon: you must call framework() prior to calling autoloader()\n");
        self::$fwapi->addAutoloader($path,$base);
    }
    public static function config($key,$value=null) {
        if (is_array($key)) {
            self::$config = array_merge((array)self::$config, (array)$key);
        } else {
            if (func_num_args()==1) {
                if (array_key_exists($key,self::$config))
                    return self::$config[$key];
                return null;
            } else {
                if (is_array($value)) {
                    if (!array_key_exists($key,self::$config) || !is_array(self::$config)) {
                        self::$config[$key] = [];
                    }
                    self::$config[$key] = array_merge(self::$config[$key],$value);
                }
                self::$config[$key] = $value;
            }
        }
    }
    public static function framework($framework,$version="*") {
        if ($framework) {
            if (XENON_DEBUG) echo "xenon: loading {$framework}...\n";
            self::$fwapi = require XENON_PATH . "/frameworks/" . $framework . ".php";
        }
        if (!self::$fwapi) {
            $apiclass = XENON_FWAPI;
            self::$fwapi = new $apiclass();
        }
        return self::$fwapi;
    }
    public static function application(callable $app) {
        $ret = call_user_func($app)?:0;
        exit($ret);
    }
}

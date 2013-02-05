<?php

namespace xenon;

class xenon {
    private static $fwapi = null;
    public static function autoloader($path,$base=null) {
        self::$fwapi->addAutoloader($path,$base);
    }
    public static function framework($framework,$version="*") {
        if ($framework) {
            if (XENON_DEBUG) echo "xenon: loading {$framework}...\n";
            require XENON_PATH . "/frameworks/" . $framework . ".php";
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

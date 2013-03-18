<?php

namespace Cherry\Traits;

trait TStaticDebug {

    protected static function debug($str) {
        $class = get_called_class();
        if (defined("DEBUG_IGNORE_CLASSES")) {
            static $ignore;
            if (!$ignore) $ignore = explode(",",DEBUG_IGNORE_CLASSES);
            if (in_array($class,$ignore)) return;
        }
        $args = func_get_args();
        $fmt = array_shift($args);
        $fmt = "\033[1m".$class."\033[21m: ".$fmt;
        array_unshift($args,$fmt);
        call_user_func_array("\cherry\debug",$args);
    }


}

<?php

namespace Cherry\Traits;

trait TDebug {

    protected function debug($str) {
        $args = func_get_args();
        $fmt = array_shift($args);
        $fmt = "\033[1m".get_called_class()."\033[21m: ".$fmt;
        array_unshift($args,$fmt);
        call_user_func_array("\debug",$args);
    }


}

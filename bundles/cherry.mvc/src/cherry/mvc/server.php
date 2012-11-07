<?php

namespace Cherry\Mvc;

class Server {

    public function __construct() {

    }

    public function log($str) {
        $args = func_get_args();
        if (count($args)>1) {
            $out = call_user_func_array('sprintf',$args);
        } else {
            $out = $str;
        }
        error_log($out);
    }

}

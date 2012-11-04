<?php

namespace Cherry\Base;

class Diag {

    static function benchmark($times,$function,array $args=null) {
        $t = microtime(true);
        for($n = 0; $n < $times; $n++)
            call_user_func_array($function,(array)$args);
        $e = microtime(true);
        return [ $e - $t, ($e - $t) / $times ];
    }


}

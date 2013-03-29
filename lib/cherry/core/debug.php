<?php

namespace Cherry\Core;

class Debug {
    static function getCaller($idx=1) {
        $bt = debug_backtrace(null,5);
        return $bt[$idx];
    }
}

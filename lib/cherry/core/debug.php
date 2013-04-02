<?php

namespace Cherry\Core;

class Debug {
    static function getCaller($idx=1) {
        $bt = debug_backtrace(null,$idx);
        $idx = min($idx,count($bt)-1);
        return $bt[$idx];
    }
}

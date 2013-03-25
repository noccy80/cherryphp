<?php

namespace Cherry\Util;

class Timer {

    private $_start = null;
    private $_marks = [];

    public function __construct() {
        $this->_start = microtime(true);
    }

    public function mark($label) {
        $this->_marks[$label] = microtime(true);
    }

    public function __get($label) {
        if (array_has_key($label,$this->_marks))
            return ($this->_marks[$label] - $this->_start);
        return null;
    }

    public function __toString() {
        $out = [];
        foreach($this->_marks as $mark=>$time) {
            $out[] = sprintf("%s: %.4fs", $mark, ($time - $this->_start));
        }
        return join(" ",$out);
    }

}

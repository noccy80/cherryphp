<?php

namespace Cherry\Util;

class ScopedObserver {
    private
            $callback = null,
            $invokeobj = null;
            
    public function __construct($callback,$invokeobj=null) {
        $this->callback = $callback;
        $this->invokeobj = $invokeobj;
    }
    public function __call($mtd,$args) {
        call_user_func_array([ $this->invokeobj, $mtd ],$args);
    }
    public function __destruct() {
        call_user_func($this->callback);
    }
}

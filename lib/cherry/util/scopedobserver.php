<?php

namespace Cherry\Util;

class ScopedObserver {
    private
            $callback = null;
    public function __construct($callback) {
        $this->callback = $callback;
    }
    public function __destruct() {
        call_user_func($this->callback);
    }
}

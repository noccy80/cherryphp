<?php

namespace Cherry\Traits;

trait Logging {

    private $_logcb = null;

    protected function log($str) {
        if (func_num_args()>1)
            $str = call_user_func_array("sprintf",func_get_args());
        if (!$this->_logcb)
            \debug(__CLASS__.': '.$str);
        else
            call_user_func($this->_logcb,__CLASS__.': '.$str);
    }

    public function setLogCallback(callable $cb = null) {
        $this->_logcb = $cb;
    }
    public function getLogCallback() {
        return $this->_logcb;
    }


}

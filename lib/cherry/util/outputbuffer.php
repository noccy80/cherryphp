<?php

namespace Cherry\Util;

class OutputBuffer {

    private
            $options = [
                'autostart' => true,
            ],
            $bufferfunc = null,
            $buffer = null;

    function __construct($bufferfunc = null, array $options = null) {
        if ($bufferfunc)
            $this->bufferfunc = $bufferfunc;
        if ($options)
            $this->options = array_merge($this->options,$options);
    }

    public function __toString() {
        return $this->buffer;
    }

    public function _obfunc_cb($data, $flags = null) {
        return $this->bufferfunc($data);
    }

    public function start() {
        ob_start(array($this,'_obfunc_cb'));
    }

    public function stop() {
        ob_end_clean();
    }

    public function flush($end = false) {
        if ($end)
            ob_end_flush();
        else
            ob_flush();
    }

    public function clean($end = false) {
        if ($end)
            ob_end_clean();
        else
            ob_clean();
    }

}

$b = new OutputBuffer();
$b->flush();

$b = new OutputBuffer(function($data) {
    return strtoupper($data);
}, [
    'autostart' => true
]);

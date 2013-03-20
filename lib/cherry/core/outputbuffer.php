<?php

namespace Cherry\Core;

/*
 * class OutputBuffer
 */

class OutputBuffer {
    private $buf = "";
    private $started = false;
    function __construct() {
        
    }
    function __destruct() {
        if ($this->started)
            ob_end_clean();
    }
    function begin() {
        if ($this->started) return;
        ob_start();
        $this->started = true; 
    }
    function end() {
        if (!$this->started) return;
        $this->buf .= ob_get_clean();
        ob_end_clean();
        $this->started = false;
    }
    function getBuffer() {
        return $this->buf;
    }
    function __toString() {
        return $this->buf;
    }
}

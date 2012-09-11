<?php

namespace cherry\proc;

abstract class BaseProcess {
}

class Process extends BaseProcess {

    private $ph = null;
    private $pipes = null;
    private $cmd = null;
    private $hdesc = null;

    function __construct($cmd, $flags = 0x00, array $descriptors = null) {
        if (!$descriptors) {
            $this->hdesc = array(
                0 => array('pipe','r'),
                1 => array('pipe','w'),
                2 => STDERR
            );
        } else {
            $this->hdesc = $descriptors;
        }
        $this->cmd = $cmd;
    }
    function run() {
        $pipes = array();
        $ph = proc_open($this->cmd, $this->hdesc, $pipes);
        if (!$ph) return false;
        $this->ph = $ph;
        $this->pipes = $pipes;
        return true;
    }

    function getPipe($index) {
        if (!empty($this->pipes[$index])) return $this->pipes[$index];
    }

    public function isRunning() {
        $stat = proc_get_status($this->ph);
        $this->running = $stat['running'];
        return $this->running;
    }

    function readLine() {
        return fgets($this->pipes[1]);
    }

    function writeLine($line) {
    
    }

    function __destruct() {
        if (!$this->ph) {
            @proc_close($this->ph);
        }
    }

}

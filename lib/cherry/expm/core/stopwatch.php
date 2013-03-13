<?php

namespace Cherry\Expm\Core;

class StopWatch {

    private $ms = false;
    private $tstart = null;
    private $tstartc = null;
    private $tstops = [];

    public function __construct($ms=false) {
        $this->ms = $ms;
    }
    
    public static function create($start=false,$ms=true) {
        $sw = new StopWatch($ms);
        if ($start) $sw->start();
        return $sw;
    }

    public function start() {
        $this->tstart = microtime($this->ms);
        $this->tstartc = $this->tstart;
        $this->tstops = [];
    }
    
    public function stop() {
        $et = microtime($this->ms);
        $t = $et - $this->tstartc;
        $this->tstops[] = $t;
        $this->tstartc = microtime($this->ms);
        return $t;
    }
    
    public function getTimes() {
        return $this->tstops;
    }
}

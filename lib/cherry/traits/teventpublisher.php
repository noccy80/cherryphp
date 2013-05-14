<?php

namespace Cherry\Traits;

trait TEventPublisher {
    private $events = [];
    private function publishEvent($event) {
        $this->events[$event] = true;
    }
    public function __call($mtd,$args) {
        $mtdl = strtolower($mtd);
        if (substr($mtdl,0,2) == "on") {
            $evt = substr($mtdl,2);
            if (array_key_exists($evt,$this->events)) {
                $this->events[$evt] = $args[0];
                return;
            }
        }
        if (substr($mtdl,0,2) == "do") {
            $evt = substr($mtdl,2);
            if (array_key_exists($evt,$this->events)) {
                if (is_callable($this->events[$evt])) {
                    return call_user_func_array($this->events[$evt],$args);
                }
            }
        }
    
        @parent::__call($mtd,$arg);
    }
}


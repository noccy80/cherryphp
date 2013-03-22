<?php

namespace Cherry\Core;

trait TEventEmitter {

    private $handlers = array();

    public function on($event,$callback,$replace=false) {
        if (!is_callable($callback)) throw new \cherry\Base\EventException(_('Callback function is not callable'));
        if((!array_key_exists($event,$this->handlers)) || ($replace)) {
            $this->handlers[$event] = array();
        }
        $this->handlers[$event][] = $callback;
        //if (is_callable([$this,'debug'])) $this->debug("Event hooked: <%s>", $event);
    }

    protected function emit($event,array $data) {
        if (array_key_exists($event,$this->handlers)) {
            if (is_callable([$this,'debug'])) $this->debug("Emiting event '%s' to %d listeners", $event, count($this->handlers[$event]));
            // Re-emit events straight away
            if ((count($data)>0) && (reset($data) instanceof Event))
                $evt = $args;
            else
                $evt = new Event($this,null,$event,$data);
            // Send the event to the handlers
            foreach($this->handlers[$event] as $cb) {
                $ret = call_user_func($cb,$evt);
                if (!$evt->propagate) return $ret;
                if ($ret) return $ret;
            }
        } else {
            if (DEBUG_VERBOSE) if (is_callable([$this,'debug'])) $this->debug("Event '%s' has no listeners so not emited", $event);
        }
    }

}

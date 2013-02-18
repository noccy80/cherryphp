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
        if (DEBUG_VERBOSE) \cherry\log(\cherry\LOG_DEBUG,"EventEmitter[%s]: Hooked event <%s>", get_class($this),$event);
    }

    protected function emit($event,$args=null) {
        $args = func_get_args();
        $args = array_slice($args,1);
        if (array_key_exists($event,$this->handlers)) {
            \cherry\log(\cherry\LOG_DEBUG,"EventEmitter[%s]: Emiting event <%s> to %d listeners", get_class($this), $event, count($this->handlers[$event]));
            // If only one arg, and arg is array we use that as the argument.
            if ((count($args)==1) && (is_array($args[0])))
                $args = $args[0];
            // Re-emit events straight away
            if ($args instanceof Event)
                $evt = $args;
            else
                $evt = new Event($this,null,$event,$args);
            // Send the event to the handlers
            foreach($this->handlers[$event] as $cb) {
                $ret = call_user_func($cb,$evt);
                if ($ret) return $ret;
            }
        } else {
            if (DEBUG_VERBOSE) \cherry\log(\cherry\LOG_DEBUG,"EventEmitter[%s]: Event <%s> has no listeners so not emited", get_class($this), $event);
        }
    }

}

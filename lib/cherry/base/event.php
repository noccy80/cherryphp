<?php

namespace cherry\base;

/**
 * @brief Class to wrap a callback.
 *
 * Might be obsolete.
 */
class CallableStub {
    private $cb = null;
    function __construct($cb) {
        if (func_num_args()==2) {
            $cb = func_get_args();
        }
        if (!is_callable($cb))
            throw new \UnexpectedValueException(_('Callback function is not callable'));
        $this->cb = $cb;
    }
    function __invoke() {
        $args = func_get_args();
        return call_user_func_array($this->cb, $args);
    }
}

class Event {

    const EP_FULL_INVOCATION = 'full';

    private static $handlers = array();
    private static $eventprops = array();
    /**
     *
     *
     *
     * @param string $event The event name.
     * @param callable $callback The callback to handle the event.
     * @param bool $full Invoke (and concatenate) all the event handlers, not just
     *      the first handlers up to where one returns a non-null value.
     */
    static function observe($event,$callback) {
        if (!is_callable($callback)) throw new \cherry\Base\EventException(_('Callback function is not callable'));
        if (!array_key_exists($event,self::$handlers)) {
            self::$handlers[$event] = array();
        }
        $handler = new \Data\DataBlob(array(
            'uid' => uniqid(true),
            'callback' => $callback
        ));
        self::$handlers[$event][] = $handler;
        return $handler->uid;
    }

    static function setEventProp($event,$prop,$value) {
        if (empty(self::$eventprops[$event])) {
            self::$eventprops = array();
        }
        self::$eventprops[$prop] = $value;
    }

    static function getEventProp($event,$prop) {
        if (empty(self::$eventprops[$event])) {
            return null;
        }
        return self::$eventprops[$prop];
    }

    static function invoke($event,$args=null) {
        // Extract only the args
        $args = func_get_args();
        $args = array_slice($args,1);
        // Check if we got any handlers for this event
        if (!array_key_exists($event,self::$handlers))
            return;
        // Find the handlers and call them
        $out = array();
        $full = self::getEventProp($event,self::EP_FULL_INVOCATION);
        foreach(self::$handlers[$event] as $handler) {
            $ret = call_user_func_array($handler->callback,$args);
            if ($full) {
                $out[] = $ret;
            } else {
                if ($ret)
                    return $ret;
            }
        }
        if ($full)
            return $out;
    }

}

abstract class EventEmitter {

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
            foreach($this->handlers[$event] as $cb) {
                $ret = call_user_func_array($cb,$args);
                if ($ret) return $ret;
            }
        } else {
            if (DEBUG_VERBOSE) \cherry\log(\cherry\LOG_DEBUG,"EventEmitter[%s]: Event <%s> has no listeners so not emited", get_class($this), $event);
        }
    }

}

class EventException extends \Exception { }


<?php

namespace cherry\Base {

    class Event {

        private static $handlers = array();
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
        static function invoke($event,$args=null) {
            // Extract only the args
            $args = func_get_args();
            $args = array_slice($args,1);
            // Check if we got any handlers for this event
            if (!array_key_exists($event,self::$handlers))
                return;
            // Find the handlers and call them
            foreach(self::$handlers[$event] as $handler) {
                if (call_user_func_array($handler->callback,$args) === true)
                    return true;
            }
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
            \cherry\log(\cherry\LOG_DEBUG,"EventEmitter[%s]: Hooked event <%s>", get_class($this),$event);
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
                \cherry\log(\cherry\LOG_DEBUG,"EventEmitter[%s]: Event <%s> has no listeners so not emited", get_class($this), $event, count($this->handlers[$event]));
            }
        }

    }

    class EventException extends \Exception { }

}

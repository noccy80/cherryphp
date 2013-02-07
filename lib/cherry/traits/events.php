<?php

namespace Cherry\Traits;

/**
 *
 *
 * @code
 *  class Foo {
 *      use Events;
 *      use Events::__get as __events_get;
 *      function bar() {
 *          $this->emitEvent("bork")
 *      }
 *      function __get($name) {
 *          $r = $this->__events_get($name);
 *          if ($r!==false) return $r;
 *          // Rest of code here
 *      }
 *  }
 *
 *
 *
 */
trait Events {


    /**
     * Returns true if the name is a registered event
     */
    private function isEvent($name) {

    }
    private function setEventCallback($name,callable $callback,$reset=false) {
        $this->eventCallbacks[$name][] = $callback;
    }
    private function emitEvent($name,$data) {

    }
    public function onEvent($name,callable $callback) {
        $this->setEventCallback($name,$callback);
    }
    /**
     * Getter. Remember to import this.
     *
     */
    public function __get($name) {
        if ($this->isEvent($name)) return $this->getEventCallbacks($name);
        return false;
    }
}

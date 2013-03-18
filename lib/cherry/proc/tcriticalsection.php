<?php

namespace Cherry\Proc;

trait TCriticalSection {

    private $mutex = null;

    private function enterCriticalSection($key) {
        $this->mutex = new Semaphore($key);
        $this->mutex->acquire();
    }

    private function leaveCriticalSection() {
        $this->mutex->release();
    }

}

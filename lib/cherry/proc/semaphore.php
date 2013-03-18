<?php

namespace Cherry\Proc;

class Semaphore {
    use \Cherry\Traits\TDebug;

    private
        $semaphore = null,
        $acquired = false,
        $start = null;

    public function __construct($key, $max=1, $perm=0666) {
        $this->semaphore = sem_get($key, $max, $perm, true);
        $this->debug("Setting up semaphore 0x%x",$key);
    }

    public function __destruct() {
        $this->release();
    }

    public function acquire() {
        if (!$this->acquired) {
            $t = microtime(true);
            $this->debug("Acquiring semaphore");
            sem_acquire($this->semaphore);
            $te = microtime(true) - $t;
            $this->debug("Semaphore acquired (+%.4fs)", $te);
            $this->start = microtime(true);
            $this->acquired = true;
        }
    }

    public function release() {
        if ($this->acquired) {
            $rl = microtime(true) - $this->start;
            $this->debug("Releasing semaphore (+%.4fs)", $rl);
            sem_release($this->semaphore);
            $this->acquired = false;
        }
    }

}

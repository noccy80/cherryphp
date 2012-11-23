<?php

namespace Cherry\Proc;

class Semaphore {
    
    private
        $semaphore = null,
        $acquired = false;
    
    public function __construct($key, $max=1, $perm=0666) {
        $this->semaphore = sem_get($key, $max, $perm, true);
    }
    
    public function __destruct() {
        $this->release();
    }
    
    public function acquire() {
        if (!$this->acquired) {
            sem_acquire($this->semaphore);
            $this->acquired = true;
        }
    }
    
    public function release() {
        if ($this->acquired) {
            sem_release($this->semaphore);
            $this->acquired = false;
        }
    }
    
}
<?php

namespace Cherry\Proc;

class SharedMem {

    const SCOPE_APP = 1;
    const SCOPE_PID = 2;

    private
            $shm = null,
            $key = null,
            $fh = null;

    public function __construct($file,$scope = self::SCOPE_APP, $size = 65535) {
        $mf = $file . ($scope==self::SCOPE_PID)?'.'.getmypid():'';
        if (!file_exists($mf))
            touch($mf);
        $this->key = fileinode($mf);
        \Cherry\Debug("SHM key: %d", $this->key);
        $this->shm = shm_attach($this->key, $size, 0700);
    }

    public function __destruct() {
        shm_detach($this->shm);
    }

    public function get($index) {
        $this->data[$index] = shm_get_var($this->shm,$index);
        return $this->data[$index];
    }

    public function set($index,$value,$overwrite=false) {
        if (($overwrite) ||
            (!shm_has_var($this->shm,$index)) ||
            ($this->data[$index] == shm_get_var($this->shm,$index))) {
            shm_put_var($this->shm, $index, $value);
            return true;
        } else {
            return false;
        }
    }

}

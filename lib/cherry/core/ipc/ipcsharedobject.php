<?php

namespace Cherry\Core\Ipc;

class IpcSharedObject extends IpcObject {

    use \Cherry\Proc\TCriticalSection;

    private $props = [];
    private $hashes = [];
    private $shm = null;

    /**
     *
     *
     *
     */
    public function setup($format) {
        foreach($format as $k=>$v) { $format[$k] = strtolower($v); }
        $idx = range(0,count($format)-1);
        $this->props = \array_combine($format,$idx);
        $this->hashes = \array_combine($format,array_fill(0,count($format),null));
    }

    public function isOpen() {
        return (!empty($this->shm));
    }

    public function open() {
        $this->shm = shm_attach($this->ipckey,256000,0666);
        if (!$this->shm)
            throw new \Exception("Could not open shared memory segment");
    }

    public function close() {
        shm_detach($this->shm);
        $this->shm = null;
    }

    public function get($key) {
        if (!$this->isOpen()) $this->open();
        $this->enterCriticalSection($this->ipckey);
        $this->debug("SHM get: {$key}");
        $key = strtolower($key);
        $idx = $this->props[$key];
        if (shm_has_var($this->shm, $idx)) {
            $var = shm_get_var($this->shm, $idx);
        } else $var = null;
        $this->hashes[$key] = md5($var);
        $this->leaveCriticalSection();
        return $var;
    }

    public function set($key,$value,$no_cas=false) {
        if (!$this->isOpen()) $this->open();
        $this->enterCriticalSection($this->ipckey);
        $this->debug("SHM set: {$key} = {$value}");
        $key = strtolower($key);
        $idx = $this->props[$key];
        if ((!$no_cas) && (shm_has_var($this->shm, $idx)) && (!empty($this->hashes[$key]))) {
            $var = shm_get_var($this->shm, $idx);
            $check = md5($var);
            if ($this->hashes[$key] == $check) {
                $this->debug("CAS check: Key not modified: {$key}");
                shm_put_var($this->shm, $idx, $value);
                $ok = true;
            } else {
                $this->debug("CAS check: Key modified, write blocked: {$key}");
                $ok = false;
            }
        } else {
            $this->debug("CAS check: Check disabled for set: {$key}");
            $ok = true;
            shm_put_var($this->shm, $idx, $value);
        }
        if ($ok) {
            $hash = md5($value);
            $this->hashes[$key] = $hash;
            $this->debug("CAS hash for {$key} is now {$hash}");
        }
        $this->leaveCriticalSection();
        return $ok;
    }

    public function __get($key) {
        return $this->get($key);
    }

    public function __set($key,$value) {
        $this->set($key,$value);
    }

    public function destroy() {
        if (!$this->isOpen()) {
            $this->open();
        }
        shm_remove($this->shm);
        $this->close();
    }

}

/*
class IpcChannel extends IpcObject {

}
*/

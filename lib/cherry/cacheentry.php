<?php

namespace Cherry;

class CacheEntry {

    private $cachekey = null;

    public function __construct($cachekey) {
        $this->key = $cachekey;
        try {
            $this->data = Cache::get($cachekey);
            $this->set = true;
        } catch (CacheException $e) {
            $this->data = null;
            $this->set = false;
        }
    }

    public function __toString() {
        return (string)($this->data);
    }

    public function isEmpty() {
        return (!$this->set);
    }

    public function update($value) {
        $this->data = $value;
        Cache::set($this->key,$this->data);
    }

    public function get() {
        return $this->data;
    }

}

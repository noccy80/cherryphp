<?php

namespace Cherry\Mvc;

use App;

class Session {

    private
            $init = false;

    public function __construct() {

    }

    public function has($key) {
        $this->init();
        return (array_key_exists($key,$_SESSION));
    }

    public function get($key,$default=null) {
        $this->init();
        if (!$this->has($key)) return $default;
        return $_SESSION[$key];
    }

    public function hasKey($key,$subkey) {
        $this->init();
        return (array_key_exists($key,$_SESSION) && array_key_exists($subkey,(array)$_SESSION[$key]));
    }

    public function getKey($key,$subkey,$default=null) {
        $this->init();
        if (!$this->hasKey($key,$subkey)) return $default;
        return $_SESSION[$key][$subkey];
    }

    public function set($key,$value) {
        $this->init();
        $_SESSION[$key] = $value;
    }

    public function setKey($key,$subkey,$value) {
        $this->init();
        if (!array_key_exists($key,$_SESSION))
            $_SESSION[$key] = [];
        if (!is_array($key,$_SESSION))
            $_SESSION[$key] = [];
        $_SESSION[$key][$subkey] = $value;
    }

    public function init() {
        static $init = false;
        if ($init) return;
        session_start();
        $init = true;
    }

}

App::extend('session', new Session());

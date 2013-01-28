<?php

namespace Cherry\Traits;

trait SingletonAccess {

    private static $instance = null;

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __clone() {
        user_error("Cloning not allowed: ".__CLASS__);
    }

    public function __wakeup() {
        user_error("Unserialization not allowed: ".__CLASS__);
    }

    public function __sleep() {
        user_error("Serialization not allowed: ".__CLASS__);
    }

}

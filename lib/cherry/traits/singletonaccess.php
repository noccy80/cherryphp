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
        throw new \RuntimeException("Cloning ".__CLASS__." not allowed with SingletonAccess");
    }

    //public function __wakeup() {
        //throw new \RuntimeException("Unserializing ".__CLASS__." not allowed with SingletonAccess");
    //}

    //public function __sleep() {
        //throw new \RuntimeException("Serializing ".__CLASS__." not allowed with SingletonAccess");
    //}

}

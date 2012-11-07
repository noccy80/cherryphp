<?php

namespace Cherry\Traits;

define('HAS_TRAITS',true);

use Reflection;
use ReflectionClass;
use ReflectionMethod;

trait Extendable {

    protected $_extensions = [];

    public function extend($object) {
        $rc = new ReflectionClass($object);
        $ml = $rc->getMethods();
        $m = new ReflectionMethod();
        foreach($ml as $m) {
            if ($m->isPublic()) {
                $this->_extensions[$m->getName] = [ $object, $m->getName() ];
            }
        }
    }

    public function __call($method,$args) {
        if (!array_key_exists($method,$this->_extensions))
            user_error("No such method (local or extended): ".$method);
        array_unshift($args,$this);
        return call_user_func_array($this->_extensions[$method],$args);
    }

}

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

trait ImmutableProperties {

    protected $_props = [];

    protected function addProperty($prop,$value=null,$constraint=null,$readonly=false) {
        $this->_props[$prop] = [
            'property' => $prop,
            'value' => $value,
            'constraint' => $constraint,
            'readonly' => $readonly
        ];
    }

    public function __get($prop) {
        if (!array_key_exists($prop,$this->_props))
            throw new \UnexpectedValueException("No such property: ".$prop);
        return $this->_props[$prop]['value'];
    }

    public function __set($prop,$value) {
        if (!array_key_exists($prop,$this->_props))
            throw new \UnexpectedValueException("No such property: ".$prop);
        if ($this->_props[$prop]['readonly'])
            throw new \UnexpectedValueException("Property is read-only: ".$prop);
        $this->_props[$prop]['value'] = $value;
    }

}

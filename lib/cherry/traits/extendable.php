<?php

namespace Cherry\Traits;

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

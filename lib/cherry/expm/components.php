<?php

namespace Cherry\Expm;

class Components {
    private static $registry;
    static function registry() {
        if (!self::$registry) self::$registry = new ComponentRegistry();
        return self::$registry;
    }
    static function set($key,$value) {
        self::registry()[$key] = $value;
    }
    static function get($key) {
        return self::registry()[$key];
    }
}

class ComponentRegistry implements \ArrayAccess {
    private $components = [];
    public function offsetGet($offset) {
        if (!array_key_exists($offset,$this->components)) return null;
        $this->checkOffset($offset);
        return $this->components[$offset];
    }
    public function offsetSet($offset,$value) {
        if (!$offset) throw new \UnexpectedValueException("Not a valid offset.");
        $this->checkOffset($offset);
        $this->components[$offset] = $value;
    }
    public function offsetUnset($offset) {
        if (!array_key_exists($offset,$this->components)) return;
        $this->checkOffset($offset);
        unset($this->components[$offset]);
    }
    public function offsetExists($offset) {
        $this->checkOffset($offset);
        return (array_key_exists($offset,$this->components));
    }
    private function checkOffset($offset) {
        if (strpos($offset,":")===false)
            throw new \UnexpectedValueException("Offset does not contain namespace.");
    }

}

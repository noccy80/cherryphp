<?php

namespace Cherry\Traits;

trait TImmutableProperties {

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

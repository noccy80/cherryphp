<?php

namespace Cherry\Mvc;

abstract class Widget {

    protected $properties = array();
    protected $propvalues = array();
    protected $id = null;

    public function init($id, array $properties = null, array $defaults = null) {
        $this->id = $id;
        $this->properties = (array)$properties;
        $this->propvalues = $defaults;
        foreach($this->properties as $k=>$v) {
            if (empty($this->propvalues[$k]))
                $this->propvalues[$k] = null;
        \Cherry\debug('Widget: Registered property %s', $k);
        }
    }

    public function __set($key, $value) {
        if (empty($this->properties[$key]))
            throw new \Exception("Invalid property assignment for widget");
        switch(strtolower($this->properties[$key])) {
            case 'int':
            case 'integer':
                $this->propvalues[$key] = intval($value);
                break;
            case 'float':
                $this->propvalues[$key] = floatval($value);
                break;
            default:
                $this->propvalues[$key] = $value;
                break;

        }
    }

    public function __get($key) {
        if (empty($this->properties[$key]))
            throw new \Exception("Invalid property assignment for widget");
        return $this->propvalues[$key];
    }

    public function __call($name,$args) {
        if (substr($name,0,3) == 'set') {
            $key = strtolower(substr($name,3));
            if (!empty($this->properties[$key]))
                $this->{$key} = $args[0];
            return;
        } elseif (substr($name,0,3) == 'get') {
            $key = strtolower(substr($name,3));
            if (!empty($this->properties[$key]))
                return $this->{$key};
        }
        throw new \BadMethodCallException("Method ".$name." is not callable");
    }

    abstract public function render();

}

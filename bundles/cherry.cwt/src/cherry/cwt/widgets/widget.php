<?php

namespace Cherry\Cwt\Widgets;

/**
 *
 */
abstract class Widget extends \Cherry\Base\EventEmitter {

    protected $properties = array();
    protected $propvalues = array();

    protected function initprops(array $properties = null, array $defaults = null) {
        $this->properties = (array)$properties;
        $this->propvalues = (array)$defaults;
        foreach($this->properties as $k=>$v) {
            if (empty($this->propvalues[$k]))
                $this->propvalues[$k] = null;
        }
    }

    protected function registerProp($prop,$type,$default=null) {
        $this->properties[$prop] = $type;
        $this->propvalues[$prop] = $default;
    }

    public function __set($key, $value) {
        if (!array_key_exists($key,$this->properties))
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
        if (!array_key_exists($key,$this->properties))
            throw new \Exception("Invalid property assignment for widget");
        return $this->propvalues[$key];
    }

    /**
     *
     */
    abstract function update();

    /**
     *
     */
    abstract function hittest($x,$y);

}

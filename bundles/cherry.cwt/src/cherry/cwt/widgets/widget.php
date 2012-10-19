<?php

namespace Cherry\Cwt\Widgets;

/**
 *
 */
abstract class Widget extends \Cherry\Base\EventEmitter {

    const ON_CLICK = 'cwt:mouse.click';
    const ON_MOUSE_DOWN = 'cwt:mouse.down';
    const ON_MOUSE_UP = 'cwt:mouse.up';

    protected $window = null;
    protected $properties = array();
    protected $propvalues = array();

    protected $left = 0;
    protected $top = 0;
    protected $width = null;
    protected $height = null;

    protected function wnd($renew=false) {
        if ($renew) {
            if ($this->window) {
                ncurses_delwin($this->window);
            }
            $this->window = null;
        }
        if (!$this->window) {
            $this->window = ncurses_newwin($this->height,$this->width,$this->top,$this->left);
            \Cherry\debug('New window: %dx%d+%d+%d', $this->left, $this->top, $this->width, $this->height);
        }
        return $this->window;
    }

    public function __destroy() {
        if ($this->window)
            ncurses_delwin($this->window);
    }

    public function resize($width,$height) {
        $this->width = $width;
        $this->height = $height;
        $this->wnd(true);
    }

    public function moveTo($left,$top,$width=null,$height=null) {
        $this->left = $left;
        $this->top = $top;
        if ($width!==null) $this->width = $width;
        if ($height!==null) $this->height = $height;
        $this->wnd(true);
    }

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

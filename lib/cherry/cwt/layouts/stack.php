<?php

namespace Cherry\Cwt\Layouts;

abstract class Stack extends \Cherry\Cwt\Widgets\Widget {

    public function hittest($x,$y) { }

    public function update() { }
    
    public abstract function draw();

}

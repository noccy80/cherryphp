<?php

namespace Cherry\Expm\Cwt\Widget;

use \Cherry\Expm\Cwt\Context;

class Desktop extends Widget {

    public function onCreate() {
        $this->setPosition(0,0);
        list($w,$h) = Context::getInstance()->getDimensions();
        $this->setSize($w,$h);
    }

    public function addWindow(Widget $widget) {
        $widget->setParent($this);
        $this->children[] = $widget;
    }

}

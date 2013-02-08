<?php

namespace Cherry\Expm\Cwt\Widget\Traits;

use \Cherry\Expm\Cwt\Widget\Widget;

trait VerticalFlow {
    public function pushWidget(Widget $widget) {
        $widget->setParent($this);
        $this->children[] = $widget;
    }
    public function onDraw() {

    }
    public function onResize($w,$h) {

        parent::onResize($w,$h);
    }
}

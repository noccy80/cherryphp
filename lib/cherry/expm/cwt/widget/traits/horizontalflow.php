<?php

namespace Cherry\Expm\Cwt\Widget\Traits;

use \Cherry\Expm\Cwt\Widget\Widget;

trait HorizontalFlow {
    public function pushWidget(Widget $widget) {
        \debug("%s[%s] pushWidget (%s)", __CLASS__, __TRAIT__,\get_class($widget));
        $widget->setParent($this);
        $this->children[] = $widget;
    }
    public function onDraw() {
        \debug("%s[%s] onDraw", __CLASS__, __TRAIT__);

    }
    public function onResize($w,$h) {
        \debug("%s[%s] onResize: %dx%d", __CLASS__, __TRAIT__, $w, $h);
        parent::onResize($w,$h);
    }
}

<?php

namespace Cherry\Expm\Cwt\Widget;

class Button extends Widget {

    public $label = null;

    public function onMeasure() {
        return [ strlen($label)+4,1 ];
    }

    public function onDraw() {
        $w = $this->getParent()->getWindow();
        ncurses_mvwaddstr($w,10,5,"< ".$label." >");
        ncurses_wrefresh($w);
    }

}

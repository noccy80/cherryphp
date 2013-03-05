<?php

namespace Cherry\Expm\Cwt\Widget;

use \Cherry\Expm\Cwt\Context;

class Dialog extends Widget {
    use \Cherry\Expm\Cwt\Widget\Traits\HorizontalFlow;
    public function onCreate() {
        list($w,$h) = $this->onMeasure();
        $ctx = Context::getInstance();
        list($dw,$dh) = $ctx->getDimensions();
        \debug("Dialog::onCreate() measured to %dx%d", $w, $h);
        \debug("Dialog::onCreate() display size is %dx%d", $dw, $h);
        $this->setPosition(($dw - $w) / 2, ($dh - $h) / 2);
        $this->setSize($w, $h);
        list($x,$y) = $this->getPosition();
        list($w,$h) = $this->getSize();
        $this->window = \ncurses_newwin($h,$w,$y,$x);
        parent::onCreate();
    }
    public function onDestroy() {
        \ncurses_delwin($this->window);
        parent::onDestroy();
    }
    public function onDraw() {
        if ($this->window) {
            ncurses_wborder($this->window,0,0, 0,0, 0,0, 0,0);
            ncurses_mvwaddstr($this->window, 0, 3, "[ x ]");
            ncurses_wrefresh($this->window);
        }
        parent::onDraw();
    }
}

<?php

namespace Cherry\Cwt;

use Cherry\Base\EventEmitter;
use Cherry\Cwt\Widgets\Widget;

class Cwt extends EventEmitter {

    function __construct() {
        ncurses_init();

    }

    function __destruct() {
        ncurses_end();

    }

    function setDesktop(Widget $foo) {

    }

}

class Rect {
    private $l, $t, $w, $h;
    function __construct($l,$t,$w,$h) {
        $this->l = $l;
        $this->t = $t;
        $this->w = $w;
        $this->h = $h;
    }
}
function rect($l,$t,$w,$h) {

}

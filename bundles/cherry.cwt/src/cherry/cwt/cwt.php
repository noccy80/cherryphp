<?php

namespace Cherry\Cwt;

use Cherry\Base\EventEmitter;

class Cwt extends EventEmitter {

    function __construct() {
        ncurses_init();

    }

    function __destruct() {
        ncurses_end();

    }

}

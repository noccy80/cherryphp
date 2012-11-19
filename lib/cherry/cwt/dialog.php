<?php

namespace Cherry\Cwt;

use Cherry\Cwt\Layouts\VerticalStack;
use Cherry\Base\EventEmitter;

class Dialog extends EventEmitter {

    protected $layout = null;
    protected $props = array(
        'title' => 'Dialog'
    );

    function __construct() {
        $this->layout = new VerticalStack();
    }

    public function __get($key) {

    }

    public function __set($key,$value) {

    }

}

<?php

namespace Cherry\Expm\Mvc;

use \Cherry\Crypto\Uuid;

/*
 * @class Fragment
 * @brief A partial view that can be manipulated separately.
 */
abstract class Fragment extends View {
    protected $backend = 'memcached';
    public function __construct($fragmentid=null) {
        if (!$fragmentid) {
            $this->createFragment();
        } else {
            $this->loadFragment();
        }
    }
    public function __destruct() {
        $this->saveFragment();
    }
    protected function createFragment() {
        $this->fragmentid = Uuid::getInstance()->generate(Uuid::UUID_V4);
    }
    protected function loadFragment() { }
    protected function saveFragment() { }
    abstract public function render();
    abstract public function modified();
}

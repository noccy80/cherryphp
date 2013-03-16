<?php

namespace Cherry\Core;

class Event {
    use \Cherry\Traits\TDebug;
    public $sender = null;
    public $target = null;
    public $type = null;
    public $data = [];
    public $propagate = true;
    public function __construct($sender, $target, $type, array $data) {
        $this->sender = $sender;
        $this->target = $target;
        $this->type = $type;
        $this->data = $data;
        $fromstr = ($this->sender)?get_class($this->sender):'*';
        $tostr = ($this->target)?get_class($this->target):'*';
        $this->debug("Spawned event [%s]->[%s] type=%s", $fromstr,$tostr,$type);
    }
    public function stop() {
        $this->propagate = false;
    }
}

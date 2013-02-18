<?php

namespace Cherry\Core;

class Event {
    public $sender = null;
    public $target = null;
    public $type = null;
    public $data = [];
    public function __construct($sender, $target, $type, array $data) {
        $this->sender = $sender;
        $this->target = $target;
        $this->type = $type;
        $this->data = $data;
        $fromstr = ($this->sender)?get_class($this->sender):'*';
        $tostr = ($this->target)?get_class($this->target):'*';
        \debug("Event: Spawned [%s]->[%s] type=%s", $fromstr,$tostr,$type);
    }
}

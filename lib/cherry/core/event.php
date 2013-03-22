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
        $this->data = (object)$data;
        $fromstr = ($this->sender)?get_class($this->sender):'*';
        $tostr = ($this->target)?get_class($this->target):'*';
        $this->debug("Event '%s' spawned (%s)".\Cherry\Cli\Glyph::getGlyph("&#x2192;")."(%s)",$type,$fromstr,$tostr);
    }
    public function stop() {
        $this->propagate = false;
    }
}

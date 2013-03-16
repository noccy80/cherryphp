<?php

namespace Cherry\Net\Socket\Transport;

abstract class Transport extends EventEmitter {

    protected $socket;
    public $new_transport;
    protected $is_upgrading = false;

    public function __construct($tos) {
        if ($tos instanceOf Transport) {
            $this->doUpgrade($tos);
        } elseif ($tos instanceOf Socket) {
            $this->socket = $tos;
        }
    }

    public function __destruct() {
        if ($this->is_upgrading) return;
    }

    abstract function initialize();

    abstract function read();

    protected function beginUpgrade(Transport $transport) {
        $this->new_transport = $transport;
        // Set is_upgrading to true so we don't do cleanup in destructor
        $this->is_upgrading = true;
    }

    protected abstract function doUpgrade(Transport $transport);

}


<?php

namespace Cherry\Net\Socket\Transport;

use cherry\base\Event;
use cherry\base\EventEmitter;

abstract class Transport extends EventEmitter {

    abstract function initialize();
    abstract function read();

}

class HttpTransport extends Transport {

    private $headers = array();

    function initialize() {
    }

    function read() {
        // If this is a websocket request, we go ahead and upgrade to a
        // websocket connection.
        if (!empty($this->headers['upgrade'])) {
            if ($this->headers['upgrade'] == 'websocket') {
                $this->emit('upgrade', new \cherry\net\socket\transport\HttpTransport(), $headers);
            }
        }
    }

}

class WebSocketTransport extends Transport {

    function initialize() {
    }
    function read() {
    }

}

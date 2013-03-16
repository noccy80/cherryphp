<?php

namespace Cherry\Net\Socket\Transport;

class WebSocketTransport extends Transport {

    function initialize() {
    }
    function read() {
    }
    public function doUpgrade(Transport $t) {
        if ($t instanceOf HttpTransport) {
            // Pull HTTP headers out
            $key = $t->request->websocketKey;
        }
    }

}

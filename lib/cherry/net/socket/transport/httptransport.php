<?php

namespace Cherry\Net\Socket\Transport;

use \Cherry\Web\Request;
use \Cherry\Web\Response;

class HttpTransport extends Transport {

    public $request;
    public $response;

    function initialize() {
        $this->request = new Cherry\Web\Request();
        $host = stream_socket_get_name($this->socket, true);
        $this->request->setRemoteIp($host);
    }

    public function onData($data) {

        $this->request->createFromString($data,true);
        // Is there more to read before considering this request complete?
        if (!$this->request->isRequestComplete())
            return;

        // Create the response
        $this->response = $this->request->createResponse();
        $rsp->server = "Cherry Higgs/1.0";

        // If this is a websocket request, we go ahead and upgrade to a
        // websocket connection.
        if ($this->request->hasHeader("upgrade")) {
            if ($this->request->upgrade == "websocket") {
                $wst = new WebSocketTransport($this);
                $this->upgrade($wst);
            }
        } else {
            // We got a get request! request and response is already there.
            $this->onRequest();
            if ($this->response instanceOf Response)
                $this->sendResponse();
        }
        
    }
    
    protected function sendResponse($response) {
        fwrite($this->socket, $response->asHttpResponse(true));
    }
    
    abstract protected function onRequest();

}


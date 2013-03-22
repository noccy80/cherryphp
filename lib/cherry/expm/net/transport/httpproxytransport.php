<?php

namespace Cherry\Expm\Net\Transport;

use \Cherry\Expm\Net\Socket;
use \Cherry\Expm\Net\SocketTransport;
use \Cherry\Web\Response;
use \Cherry\Web\Request;

abstract class HttpProxyTransport extends SocketTransport {

    protected $request = null;
    protected $response = null;

    abstract protected function onHttpRequest();

    public function onAccept($socket,$peer,$endpoint) {

        $this->debug("<%s> [PROXY] Accepted connection from %s on %s", $this->getUuid(), $peer, $endpoint);
        parent::onAccept($socket, $peer, $endpoint);
        // Create the request object
        $this->request = new Request();
        $this->request->setRemoteIp($peer);
        // Empty the response object
        $this->response = null;
    }

    /**
     * onData is what our main application calls when the socket has received
     * data.
     */
    public function onDataWaiting() {

        // Read data from the socket
        $data = $this->read(8192);
        $this->debug("<%s> Read %d bytes of data ", $this->getUuid(), strlen($data));
        if (empty($data)) {
            $this->onDisconnect();
            return;
        }

        // Read until we got the whole request. The isRequestComplete() method
        // will return true once it has detected a full request.
        if(!$this->request->isRequestComplete()) {
            $this->request->createFromString($data,true);
            if (!$this->request->isRequestComplete()) return;
        }


    }

    public function onProcess() { }

}

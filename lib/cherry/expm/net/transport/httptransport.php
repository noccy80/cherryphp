<?php

namespace Cherry\Expm\Net\Transport;

use \Cherry\Expm\Net\Socket;
use \Cherry\Web\Response;
use \Cherry\Web\Request;

abstract class HttpTransport extends Socket {

    protected $request = null;
    protected $response = null;

    /**
     * onConnect is called when our socket is connected to a client.
     */
    public function onConnect($peer) {
        parent::onConnect($peer);
        $this->debug("Connected to {$peer}");
        $this->request = new Request();
        $this->request->setRemoteIp($peer);
        $this->response = null;
    }

    abstract protected function onRequest();

    private function logHit() {
        // App::app()->getHttpLogger()->logHit($this->request,$this->response);
    }

    /**
     * onData is what our main application calls when the socket has received
     * data.
     */
    public function onDataWaiting() {
        $data = $this->read(8192);
        if (empty($data)) {
         $this->disconnect();
         return;
        }
        $len = strlen($data);
        $gh = $this->request->isRequestComplete();
        $this->debug("{$this->uuid}: incoming data, len={$len}, gotheaders={$gh}");

        // Read until we got the whole request. The isRequestComplete() method
        // will return true once it has detected a full request.
        if(!$this->request->isRequestComplete()) {
            $this->request->createFromString($data,true);
            if (!$this->request->isRequestComplete()) return;
        }
        if (!$this->response) $this->response = $this->request->createResponse();

        $this->onRequest();

        $this->write($this->response->asHttpResponse());
        // If we got content we send the content
        if ($this->response->hasContent()) {
            $this->write($this->response->getContent());
            $this->disconnect();
            $this->logHit();
        }

    }

    /**
     * onTick is called once every loop and is responsible for dispatching the
     * message to the clients as they are received.
     */
    public function onTick() { }

}

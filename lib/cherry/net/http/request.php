<?php

namespace Cherry\Net\Http;
use Cherry\Net\Http\Client\StreamClient;
use Cherry\Base\EventEmitter;

class Request extends EventEmitter {

    private
        $client = null;

    public function __construct($url,$method='GET') {
        $this->client = new StreamClient();
        $this->client->setUrl($url);
        $this->client->setMethod($method);
    }

    public function execute() {
        if ($this->client->execute() == 200) {

        }
    }

    public function getResponseJson() {
        return json_decode($this->client->getResponse());
    }

    public function getResponseText() {
        return $this->client->getResponse();
    }

}

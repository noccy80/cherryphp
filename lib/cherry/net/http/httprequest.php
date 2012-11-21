<?php

namespace Cherry\Net\Http;
use Cherry\Net\Http\Client\StreamClient;
use Cherry\Base\EventEmitter;

class HttpRequest extends EventEmitter {

    private
        $client = null,     ///< HttpClient instance
        $status = null;     ///< HTTP status code

    public function __construct($url,$method='GET',$postdata=null,$contenttype=null) {
        $this->client = new StreamClient();
        $this->client->setUrl($url);
        $this->client->setMethod($method);
        if (($postdata) && ($contenttype)) {
            $this->client->setPostData($contenttype, $postdata);
        }
    }

    public function execute() {
        if ($this->status !== null) return;
        $this->status = $this->client->execute();
        if ($this->status == 200) {
            return true;
        }
        return false;
    }

    public function getStatus() {
        if ($this->status === false) {
            return 0;
        } else {
            return $this->status;
        }
    }

    public function getHeader($header) {
        if ($this->status === null)
            user_error("Need to execute() before accessing response");

    }

    public function getResponseJson() {
        if ($this->status === null)
            user_error("Need to execute() before accessing response");
        return json_decode($this->client->getResponse());
    }

    public function getResponseText() {
        if ($this->status === null)
            user_error("Need to execute() before accessing response");
        return $this->client->getResponse();
    }

}

<?php

namespace Cherry\Net\Http;
use Cherry\Net\Http\Client\StreamClient;
use Cherry\Base\EventEmitter;

/**
 *
 *
 * @x-event httprequest:before [$this] Fired before the request is executed
 * @x-event httprequest:error [$status,$this] Fires on error (not 200)
 * @x-event httprequest:success [$responsetext,$headers,$this] Fires on success (200)
 */
class HttpRequest extends EventEmitter {

    private
        $client = null,     ///< HttpClient instance
        $status = null;     ///< HTTP status code

    public function __construct($url=null,$method='GET',$postdata=null,$contenttype=null) {
        $this->client = new StreamClient();
        $this->client->on('httprequest:before', function() { $this->emit('httprequest:before', $this); });
        $this->client->on('httprequest:complete', function($status) {
            if ($status == 200) { $this->emit('httprequest:success', $this->getResponseText(), $this->getAllHeaders(), $this); }
            else { $this->emit('httprequest:error',$status,$this); }
        });
        if ($url) $this->client->setUrl($url);
        $this->client->setMethod($method);
        if (($postdata) && ($contenttype)) {
            $this->client->setPostData($contenttype, $postdata);
        }
    }

    public function open($method, $url, $useragent=null) {
        $this->client->setUrl($url);
        $this->client->setMethod($method);
        if ($useragent) $this->client->setHeader('User-Agent',$useragent);
    }

    public function setHeader($header,$value) {
        return $this->client->setHeader($header,$value);
    }

    public function send($postdata=null,$contenttype=null) {
        \Cherry\debug("Sending request...");
        if (($postdata) && ($contenttype)) {
            $this->client->setPostData($contenttype, $postdata);
        }
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

    public function getAllHeaders() {
        return $this->client->getHeaders();
    }

    public function getResponseJson() {
        if ($this->client->getStatus() === null)
            user_error("Need to execute() before accessing response");
        return json_decode($this->client->getResponse());
    }

    public function getResponseText() {
        if ($this->client->getStatus() === null)
            user_error("Need to execute() before accessing response");
        return $this->client->getResponse();
    }

}

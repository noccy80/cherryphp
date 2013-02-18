<?php

namespace Cherry\Net\Http;
use Cherry\Net\Http\Client\StreamClient;
use Cherry\Core\TEventEmitter;
use Cherry\Cache\CacheObject;

/**
 * @class HttpRequest
 * @brief Perform HTTP requests.
 *
 * This class is designed to resemble the XmlHttpRequest class in JavaScript.
 * It implements caching of requests, cookies and more, making it a reliable
 * class for pulling RSS-feeds and other syndication data while respecting the
 * caching wishes of the server.
 *
 */
class HttpRequest {

    use TEventEmitter;

    const ON_ERROR = 'httprequest:error';
    const ON_SUCCESS = 'httprequest:success';
    const ON_BEFORE = 'httprequest:before';
    const ON_COMPLETE = 'httprequest:complete';
    const ON_CACHEHIT = 'HttpRequest:cachehit';

    const OPT_CACHEPOLICY = 'http.cachepolicy';
    const OPT_COOKIEJAR = 'http.cookiejar';
    const OPT_STREAMRESPONSE = 'http.streamresponse';

    const CACHEPOLICY_AUTO = 'auto'; ///< Determine what to do based on HTTP headers
    const CACHEPOLICY_NEVER = 'never'; ///< Never cache, always request the content
    const CACHEPOLICY_ALWAYS = 'always'; ///< Always cache the response

    protected
        $client = null,     ///< HttpClient instance
        $status = null,     ///< HTTP status code
        $options = [
            self::OPT_CACHEPOLICY => self::CACHEPOLICY_AUTO,
            self::OPT_COOKIEJAR => null
        ],
        $response = null,
        $url = null;

    public function __construct($url=null,$method='GET',$postdata=null,$contenttype=null) {
        $this->client = new StreamClient();
        $this->client->on('httprequest:before', function($e) {
            $this->emit(HttpRequest::ON_BEFORE, $e);
        });
        $this->client->on('httprequest:complete', function($e) {
            if ($e->data[0] == 200) {
                $this->emit(HttpRequest::ON_SUCCESS, [
                    'response' => $this->getResponseText(),
                    'headers' => $this->getAllHeaders(),
                    'status' => $e->data[0]
                ]);
            } else {
                $this->emit(HttpRequest::ON_ERROR,[
                    'status' => $e->data[0]
                ]);
            }
        });
        if ($url) $this->url = $url;
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
        \debug("HttpRequest: Sending request");
        if (($postdata) && ($contenttype)) {
            $this->client->setPostData($contenttype, $postdata);
        }
        if ($this->status !== null) return;
        $ret = $this->execute();
        return $ret;
    }

    private function execute() {
        if ($this->status !== null) return null;
        // Check cache policy
        if ($this->options[self::OPT_CACHEPOLICY] == self::CACHEPOLICY_AUTO) {
            $use_cache = true;
        }
        // Don't cache post requests
        if ($this->client->getMethod() == "POST")
            $use_cache = false;
        //$this->client->setUrl($this->url);
        if ($use_cache) {
            $flags = CacheObject::CO_USE_DISK|CacheObject::CO_COMPRESS;
            $co = new CacheObject($this->url,$flags,function($url){
                \debug("HttpRequest: Refreshing cache object...");
                $status = $this->client->execute();
                \debug("HttpRequest: Response gave status {$status} when updating cache object");
                //if ($status == 200) {
                    $doc = $this->client->getResponse();
                    $ct = $this->client->getContentType();
                    // Calculate expiry here
                    return [ $doc, $ct, '30m'];
                //}
                //return null;
            });
            if ($co->isCached()) {
                \debug("HttpRequest: Returning content from cache");
                $this->emit(self::ON_CACHEHIT);
            }
            $this->response = $co->getContent();
            $this->contenttype = $co->getContentType();
            $this->status = 200;
        } else {
            \debug("HttpRequest: Sending direct request");
            if (($postdata) && ($contenttype)) {
                $this->client->setPostData($contenttype, $postdata);
            }
            $this->status = $this->client->execute();
            $this->response = $this->client->getResponse();
            $this->headers = $this->client->getHeaders();
            $this->contenttype = $this->client->getHeaders();
        }
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
            throw new \RuntimeException("Need to execute() before accessing response");
    }

    public function getAllHeaders() {
        return $this->client->getAllHeaders();
    }

    public function getResponseJson() {
        if ($this->client->getStatus() === null)
            throw new \RuntimeException("Need to execute() before accessing response");
        return json_decode($this->response);
    }

    public function getResponseText() {
        if ($this->client->getStatus() === null)
            throw new \RuntimeException("Need to execute() before accessing response");
        return $this->response;
    }

    public function setOption($option,$value) {

    }
    public function setOptions(array $options) {

    }
    public function getOption($option) {

    }
    public function getOptions() {

    }
}

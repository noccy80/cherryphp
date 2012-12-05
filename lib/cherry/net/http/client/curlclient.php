<?php

namespace Cherry\Net\Http\Client;

class CurlClient extends ClientBase {

    private $url = null,
            $server = null,
            $hcurl = null,
            $postdata = null,
            $method = 'GET',
            $reqheaders = [],
            $resheaders = [],
            $resbody = null,
            $resmeta = [];

    public function __construct($method='GET') {
        $this->hcurl = curl_init();
        $this->method = $method;
    }

    public function __destruct() {
        curl_close($this->hcurl);
    }

    public function setRequestUrl($url,$server) {
        $this->url = $url;
        $this->server = $server;

        curl_setopt($this->hcurl, CURLOPT_URL, $url);
    }

    public function setPostData($data) {
        $this->postdata = $data;
    }

    public function setRequestHeader($key,$value) {
        $this->reqheaders[$key] = $value;
    }

    public function getRequestHeader($key) {
        return (empty($this->reqheaders[$key])?null:$this->reqheaders[$key]);
    }

    public function getResponseHeader($key) {
        return (empty($this->resheaders[$key])?null:$this->reqheaders[$key]);
    }

    public function execute() {
        curl_setopt_array($this->hcurl, [
            CURLOPT_VERBOSE => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_AUTOREFERER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_MAXREDIRS => 10,

        ]);

        switch($this->method) {
            case 'head':
                curl_setopt_array($this->hcurl, [
                    CURLOPT_NOBODY => 1
                ]);
                break;
            case 'get':
                curl_setopt_array($this->hcurl, [
                    CURLOPT_RETURNTRANSFER => 1
                ]);
                break;
            case 'post':
                curl_setopt_array($this->hcurl, [
                    CURLOPT_RETURNTRANSFER => 1
                ]);
                break;
            case 'put':
                break;
            default:
                fprintf(STDERR,"Bad request method: %s\n",$this->method);
        }

        $response = curl_exec($this->hcurl);

        // Then, after your curl_exec call:
        $header_size = curl_getinfo($$this->hcurl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $this->resheaders = explode("\r\n",$header);
        $this->resbody = substr($response, $header_size);
        $this->resmeta = curl_getinfo($this->hcurl);
        $this->rescode = curl_getmeta($this->hcurl,CURLINFO_HTTP_CODE);
    }

}

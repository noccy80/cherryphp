<?php

namespace Cherry\Net\Http\Client;

use Cherry\Base\EventEmitter;

abstract class ClientBase extends EventEmitter {
    abstract public function setMethod($method);
    abstract public function setPostData($contenttype, $postdata);
    abstract public function setUrl($url);
    abstract public function getUrl();
    abstract public function execute();
}

/**
 * @class StreamClient
 * @brief HTTP Client over PHP streams.
 *
 * This class only supports HTTP 1.0 as PHPs internal http stream is not able
 * to handle chunked content-encoding.
 */
class StreamClient extends ClientBase {

    private
        $request_method     = 'GET',
        $proxy              = null,
        $postdata           = null,
        $contenttype        = null,
        $request_headers    = [],
        $bytes_max          = null,
        $bytes_transferred  = null,
        $response_data      = null,
        $response_meta      = null,
        $response_status    = null,
        $response_headers   = [],
        $response_protocol  = null,
        $response_message   = null;

    public function setMethod($method) {
        $this->request_method = strtoupper($method);
    }

    public function setUrl($url) {
        // if (stream_is_local($url))
        //     user_error("StreamClient can't open local resources");
        $this->url = (string)$url;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setPostData($contenttype, $postdata) {
        $this->contenttype = $contenttype;
        $this->postdata = $postdata;
    }

    public function setHeader($header,$value) {
        switch(strtolower($header)) {
            case 'user-agent':
                $this->useragent = $value;
                break;
            case 'content-type':
                $this->contenttype = $value;
                break;
            default:
            $this->request_headers[$header] = $value;
        }
    }

    private function buildHeaders() {
        $hdrl = $this->request_headers;
        $hdr = []; foreach($hdrl as $k=>$v) $hdr[] = str_replace(' ','-',ucwords(str_replace('-',' ',$k))).': '.$v;
        if ($this->contenttype)
            $hdr[] = 'Content-Type: '.$this->contenttype;
        return (count($hdr)>0)?join("\r\n",$hdr):null;
    }

    private function createContext() {
        $ctxopts = [
            'http' => [
                'method' => $this->request_method,
                'user_agent' => ($this->useragent)?:'CherryPHP StreamClient/1.0',
                'header' => $this->buildHeaders(),
                'content' => null,
                'proxy' => null,
                'follow_location' => 1,
                'max_redirects' => 20,
                'ignore_errors' => true
            ]
        ];
        $ctxparams = [
            'notification' => [ $this, '_cb_notification' ]
        ];
        //var_dump($ctxopts);
        $ctx = stream_context_create($ctxopts, $ctxparams);
        return $ctx;
    }

    public function getResponse() {
        return $this->response_data;
    }

    public function getHeaders() {
        return $this->response_headers;
    }

    public function getStatus() {
        return (int)$this->response_status;
    }

    public function execute() {
        $this->emit('httprequest:before');
        $ctx = $this->createContext();
        if (!($stream = @fopen($this->url, 'rb', false, $ctx))) {
        $this->emit('httprequest:complete', (int)0);
            return false;
        }
        $this->response_data = stream_get_contents($stream);
        $this->response_meta = stream_get_meta_data($stream);
        $wd = $this->response_meta['wrapper_data'];
        $headers = array_slice($wd,1);
        list($this->response_protocol, $this->response_status, $this->response_message) = explode(' ', $wd[0], 3);
        foreach($headers as $header) {
            list($k, $v) = explode(': ', $header, 2);
            $this->response_headers[$k] = $v;
        }
        $this->emit('httprequest:complete', (int)$this->response_status);
        return (int)$this->response_status;
        //var_dump($headers);
        //var_dump($data);
        //var_dump($meta);
    }

    public function _cb_notification($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
        switch($notification_code) {
            case STREAM_NOTIFY_RESOLVE:
            case STREAM_NOTIFY_AUTH_REQUIRED:
            case STREAM_NOTIFY_COMPLETED:
            case STREAM_NOTIFY_FAILURE:
            case STREAM_NOTIFY_AUTH_RESULT:
                //var_dump($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max);
                /* Ignore */
                break;

            case STREAM_NOTIFY_REDIRECTED:
                //echo "Being redirected to: ", $message;
                break;

            case STREAM_NOTIFY_CONNECT:
                //echo "Connected...";
                break;

            case STREAM_NOTIFY_FILE_SIZE_IS:
                $this->bytes_max = $bytes_max;
                //echo "Got the filesize: ", $bytes_max;
                break;

            case STREAM_NOTIFY_MIME_TYPE_IS:
                //echo "Found the mime-type: ", $message;
                break;

            case STREAM_NOTIFY_PROGRESS:
                $this->bytes_transferred = $bytes_transferred;
                //$this->emit('streamclient.progress', $bytes_transferred, $bytes_max);
                //echo "Made some progress, downloaded ", $bytes_transferred, " so far";
                break;
        }
    }

}

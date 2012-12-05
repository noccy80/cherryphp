<?php

namespace Cherry\Net\Http\Client;

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
        $response_message   = null,
        $useragent          = null,
        $timings            = [];

    public function setMethod($method) {
        $this->request_method = strtoupper($method);
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
        // Check which cookies should be included for the request
        $cookies = $this->getCookiesForRequest();
        foreach($cookies as $cookie)
            $hdr[] = 'Cookie: '.$cookie;
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
    
    public function getLastError() {
        return "Unknown error.";
    }

    public function execute() {
        $this->emit('httprequest:before');
        $this->timings = ['started' => microtime(true)];
        \Cherry\Debug('StreamClient: Creating context and opening connection...');
        $ctx = $this->createContext();
        if (!($stream = @fopen($this->url, 'rb', false, $ctx))) {
            $this->timings['request_sent'] = microtime(true);
            $this->emit('httprequest:complete', (int)0);
            return false;
        }
        $this->timings['request_sent'] = microtime(true);
        $this->response_meta = stream_get_meta_data($stream);
        if (!empty($this->response_meta['unread_bytes'])) {
            $bytes = $this->response_meta['unread_bytes'];
            \Cherry\Debug('StreamClient: Getting contents (%d bytes)', $bytes);
            $this->response_data = stream_get_contents($stream, $bytes);
        } else {
            \Cherry\Debug('StreamClient: Getting contents (Unknown length)');
            $this->response_data = stream_get_contents($stream);
        }
        \Cherry\Debug('StreamClient: Parsing response headers');
        $wd = $this->response_meta['wrapper_data'];
        $headers = array_slice($wd,1);
        list($this->response_protocol, $this->response_status, $this->response_message) = explode(' ', $wd[0], 3);
        foreach($headers as $header) {
            if (strpos($header,': ')!==false) {
                list($k, $v) = explode(': ', $header, 2);
                if ($k == 'Set-Cookie') $this->setCookieRaw($v);
                if (array_key_exists($k,$this->response_headers)) {
                    if (!is_array($this->response_headers[$k]))
                        $this->response_headers[$k] = [$this->response_headers[$k]];
                    $this->response_headers[$k][] = $v;
                } else {
                    $this->response_headers[$k] = $v;
                }
            }
        }
        $this->emit('httprequest:complete', (int)$this->response_status);
        \Cherry\Debug('StreamClient: Returning response status');
        return (int)$this->response_status;
        //var_dump($headers);
        //var_dump($data);
        //var_dump($meta);
    }
    
    public function getTimings() {
        $start = $this->timings['started'];
        $out = [ 'started' => 0 ];
        $maxv = 0;
        foreach($this->timings as $k=>$v) {
            if ($v > $maxv) $maxv = $v;
            if ($k != 'start') $out[$k] = ($v - $start);
        }
        $out['completed'] = ($maxv - $start);
        return $out;
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
                $this->timings['connected'] = microtime(true); 
                //echo "Connected...";
                break;

            case STREAM_NOTIFY_FILE_SIZE_IS:
                $this->bytes_max = $bytes_max;
                //echo "Got the filesize: ", $bytes_max;
                break;

            case STREAM_NOTIFY_MIME_TYPE_IS:
                $this->timings['headers'] = microtime(true); 
                //echo "Found the mime-type: ", $message;
                break;

            case STREAM_NOTIFY_PROGRESS:
                if (empty($this->timings['content_begins']))
                    $this->timings['content_begins'] = microtime(true);
                if ($bytes_transferred >= $bytes_max)
                    $this->timings['content_ends'] = microtime(true);
                $this->bytes_transferred = $bytes_transferred;
                
                //$this->emit('streamclient.progress', $bytes_transferred, $bytes_max);
                //echo "Made some progress, downloaded ", $bytes_transferred, " so far";
                break;
        }
    }

}

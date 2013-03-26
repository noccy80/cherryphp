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
        $request_headers    = [],
        $request_headers_u  = [],
        $proxy              = null,
        $postdata           = null,
        $contenttype        = null,
        $bytes_max          = null,
        $bytes_transferred  = null,
        $response_data      = null,
        $response_meta      = null,
        $response_status    = null,
        $response_headers   = [],
        $response_protocol  = null,
        $response_message   = null,
        $response_type      = null,
        $useragent          = null,
        $timings            = [],
        $verify_cert        = true,
        $verify_fp          = null;

    public function setMethod($method) {
        $this->request_method = strtoupper($method);
    }

    public function getMethod() {
        return $this->request_method;
    }

    public function setPostData($contenttype, $postdata) {
        $this->contenttype = $contenttype;
        $this->postdata = $postdata;
    }

    public function setRequestHeader($header,$value) {
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

    public function getRequestHeader($header) {
        $hl = explode("\r\n",$this->request_headers_u);
        if (array_key_exists($header,$hl))
            return $hl[$header];
        return null;
    }

    public function getRequestHeaders() {
        return explode("\r\n",$this->request_headers_u);
    }

    private function buildHeaders() {
        $hdrl = $this->request_headers;
        $hdr = ['Connection: Close']; foreach($hdrl as $k=>$v) $hdr[] = str_replace(' ','-',ucwords(str_replace('-',' ',$k))).': '.$v;
        if ($this->contenttype)
            $hdr[] = 'Content-Type: '.$this->contenttype;
        // Check which cookies should be included for the request
        $cookies = $this->getCookiesForRequest();
        foreach($cookies as $cookie)
            $hdr[] = 'Cookie: '.$cookie;
        return (count($hdr)>0)?join("\r\n",$hdr):null;
    }

    private function createContext() {
        $this->request_headers_u = $this->buildHeaders();
        $ctxopts = [
            'http' => [
                'method' => $this->request_method,
                'user_agent' => ($this->useragent)?:'CherryPHP StreamClient/1.0',
                'header' => $this->request_headers_u,
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

    public function getAllHeaders() {
        $ho = [];
        foreach($this->response_headers as $hdr=>$v) {
            if (is_array($v))
                foreach($v as $vi) $ho[] = $hdr.': '.$vi;
            else
                $ho[] = $hdr.': '.$v;
        }
        return $ho;
    }

    public function getStatus() {
        return (int)$this->response_status;
    }

    public function getLastError() {
        return "Unknown error.";
    }

    public function execute() {
        if (!$this->url) throw new \Exception("Request has not been opened.");
        $this->emit('httprequest:before');
        $this->timings = ['started' => microtime(true)];
        // \Cherry\Debug('StreamClient: Creating context and opening connection...');
        $ctx = $this->createContext();
        $stream = fopen($this->url, 'rb', false, $ctx);
        if (!$stream) {
            $this->timings['request_sent'] = microtime(true);
            $this->emit('httprequest:complete', (int)0);
            return false;
        }
        $this->timings['request_sent'] = microtime(true);
        $this->response_meta = stream_get_meta_data($stream);
        $this->response_data = stream_get_contents($stream);
        $this->response_headers = [];
        // \Cherry\Debug('StreamClient: Parsing response headers');
        $headers = $this->response_meta['wrapper_data'];
        foreach($headers as $header) {
            if (strpos($header,': ')!==false) {
                list($k, $v) = explode(': ', $header, 2);
                $k = strtolower($k);
                if ($k == 'set-cookie') $this->setCookieRaw($v);
                if ($k == 'content-type') $this->response_type = $v;
                if (array_key_exists($k,$this->response_headers)) {
                    if (!is_array($this->response_headers[$k]))
                        $this->response_headers[$k] = [$this->response_headers[$k]];
                    $this->response_headers[$k][] = $v;
                } else {
                    $this->response_headers[$k] = $v;
                }
            } else {
                list($this->response_protocol, $this->response_status, $this->response_message) = explode(' ', $header, 3);
                // We probably got redirected somewhere
            }
        }
        $this->emit('httprequest:complete', (int)$this->response_status);
        // \Cherry\Debug('StreamClient: Returning response status');
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
            case STREAM_NOTIFY_AUTH_REQUIRED:
                throw new \Cherry\Net\Http\HttpException("Authentication required");
            case STREAM_NOTIFY_RESOLVE:
                $this->timings['resolve'] = microtime(true); break;
            case STREAM_NOTIFY_COMPLETED:
                $this->timings['completednf'] = microtime(true); break;
            case STREAM_NOTIFY_FAILURE:
            case STREAM_NOTIFY_AUTH_RESULT:
                //$this->setError("{$message}");
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
                // echo "Got the filesize: ", $bytes_max, "\n";
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

    public function setOption($koa,$v=null) {
        if (is_array($koa)) { foreach($koa as $k=>$v) setOption($k,$v); return; }
        switch($koa) {
            case ClientBase::HTTP_PROXY:
                $this->proxy = $v;
                break;
            case ClientBase::HTTPS_VERIFY_CERT:
                $this->verify_cert = ($v == true);
                break;
            case ClientBase::HTTPS_VERIFY_FP:
                $this->verify_fp = $v;
                break;
        }
    }

    public function getOption($k) {
        switch($k) {
            case ClientBase::HTTP_PROXY:
                return $this->proxy;
            case ClientBase::HTTPS_VERIFY_CERT:
                return $this->verify_cert;
            case ClientBase::HTTPS_VERIFY_FP:
                return $this->verify_fp;
            default:
                return null;
        }
    }

    public function getContentType() {
        return $this->response_type;
    }

}

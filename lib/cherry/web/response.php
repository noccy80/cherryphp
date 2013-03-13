<?php

namespace Cherry\Web;

class Response {
    private $headers = null;
    private $httpcode = 200;
    private $protocol = null;
    public $content = null;
    public function __construct($protocol=null) {
        if (!_IS_CLI_SERVER) {
            $this->headers = [];
        }
        if ($protocol)
            $this->protocol = $protocol;
        else
            $this->protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
    }
    public function getStatus() {
        return $this->httpcode;
    }
    public function setStatus($status) {
        if (($status >= 100) && ($status <= 599))
            $this->httpcode = $status;
        else
            throw new \UnexpectedValueException("Invalid HTTP response code: {$status}");
    }
    public function setProtocol($protocol) {
        $this->protocol = $protocol;
    }
    public function getHeaders() {
        if ($this->headers === null)
            return headers_list();
        else
            return $this->headers;
    }
    public function getHeader($header) {
        $header = strtolower($header);
        if ($this->headers === null) {
            foreach(headers_list() as $header) {
                list($hname,$value) = explode(":",$header,2);
                if (strtolower($hname) == strtolower($header)) {
                    return $value;
                }
            }
            return null;
        } else {
            if (array_key_exists($header,$this->headers)) {
                return $this->headers[strtolower($header)];
            }
            return null;
        }
    }
    public function setHeader($header,$value,$replace=true,$httpcode=null) {
        if ($this->headers === null) {
            header("{$header}: {$value}", $replace, $httpcode);
            return;
        } else {
            if ($httpcode) $this->httpcode = $httpcode;
            $this->headers[strtolower($header)] = $value;
        }
    }
    public function clearHeader($header) {
        if ($this->headers === null) {
            header("{$header}: ", true);
            return true;
        } else {
            if (array_key_exists($header,$this->headers)) {
                unset($this->headers[strtolower($header)]);
                return true;
            } else {
                return false;
            }
        }
    }
    public function asHttpResponse() {
        $httptext = $this->getHttpStatusText($this->httpcode);
        // Check for some of the required headers
        if (empty($this->headers['content-type'])) {
            $fi = finfo_open();
            $ct = finfo_buffer($fi,$this->content,\FILEINFO_MIME_TYPE);
            finfo_close($fi);
            $this->headers['content-type'] = $ct;
            \debug("Notice: asHttpResponse() set content type from content to {$ct}");
        }
        $str = "{$this->protocol} {$this->httpcode} {$httptext}\r\n";
        foreach($this->getHeaders() as $k=>$v) {
            $hn = str_replace(" ","-",ucwords(str_replace("-"," ",$k)));
            $str.= "{$hn}: {$v}\r\n";
        }
        $str.= "\r\n";
        return $str;
    }

    public function asText() {
        $out = [];
        foreach($this->getHeaders() as $header=>$value) {
            $hstr = str_replace(' ', '-', ucwords(str_replace('-', ' ', $header)));
            $out[] = "{$hstr}: {$value}";
        }
        return join("\r\n",$out);
    }

    public function asHtml() {
        $httptext = $this->getHttpStatusText($this->httpcode);
        $out = [
            "<span style=\"font-weight:bold;\">{$this->protocol} {$this->httpcode}</span> <span style=\"color:#c00\">{$httptext}</span>",
        ];
        foreach($this->getHeaders() as $k=>$v) {
            $hn = str_replace(" ","-",ucwords(str_replace("-"," ",$k)));
            $len = strlen($v);
            $out[] = "<span style=\"font-weight:bold;\">{$hn}</span>: '<span style=\"color: #c00;\">{$v}</span>' <span style=\"font-style:italic;\">(length={$len})</span>";
        }
        return "<pre>".join("\r\n",$out)."</pre>";
    }
    
    
    public function getHttpStatusText($httpcode) {
        switch ($httpcode) {
            case 100: $text = 'Continue'; break;
            case 101: $text = 'Switching Protocols'; break;
            case 200: $text = 'Content Follows'; break;
            case 201: $text = 'Created'; break;
            case 202: $text = 'Accepted'; break;
            case 203: $text = 'Non-Authoritative Information'; break;
            case 204: $text = 'No Content'; break;
            case 205: $text = 'Reset Content'; break;
            case 206: $text = 'Partial Content'; break;
            case 300: $text = 'Multiple Choices'; break;
            case 301: $text = 'Moved Permanently'; break;
            case 302: $text = 'Moved Temporarily'; break;
            case 303: $text = 'See Other'; break;
            case 304: $text = 'Not Modified'; break;
            case 305: $text = 'Use Proxy'; break;
            case 400: $text = 'Bad Request'; break;
            case 401: $text = 'Unauthorized'; break;
            case 402: $text = 'Payment Required'; break;
            case 403: $text = 'Forbidden'; break;
            case 404: $text = 'Not Found'; break;
            case 405: $text = 'Method Not Allowed'; break;
            case 406: $text = 'Not Acceptable'; break;
            case 407: $text = 'Proxy Authentication Required'; break;
            case 408: $text = 'Request Time-out'; break;
            case 409: $text = 'Conflict'; break;
            case 410: $text = 'Gone'; break;
            case 411: $text = 'Length Required'; break;
            case 412: $text = 'Precondition Failed'; break;
            case 413: $text = 'Request Entity Too Large'; break;
            case 414: $text = 'Request-URI Too Large'; break;
            case 415: $text = 'Unsupported Media Type'; break;
            case 500: $text = 'Internal Server Error'; break;
            case 501: $text = 'Not Implemented'; break;
            case 502: $text = 'Bad Gateway'; break;
            case 503: $text = 'Service Unavailable'; break;
            case 504: $text = 'Gateway Time-out'; break;
            case 505: $text = 'HTTP Version not supported'; break;
            default:
                exit('Unknown http status code "' . htmlentities($httpcode) . '"');
                break;
        }
        return $text;
    }
    public function redirect($url,$httpcode=302) {
        $this->setHeader("Location",$url, true, $httpcode);
        exit;
    }
    public function sendFile($file) {
        $lastmod = filemtime($file);
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $ifmod = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            if ($ifmod >= $lastmod) {
                header('Not Modified',true,304);
                return 304;
            }
        }
        $ct = null;
        // Apply content type
        foreach([
            '*.css' => 'text/css',
            '*.js' => 'text/javascript'
        ] as $ptn => $ct)
            if (fnmatch($ptn,$file))
                $ctype = $ct;
        // If no match, try to determine
        if (empty($ctype)) $ctype = mime_content_type($file);
        // Set headers
        header('Content-Type: '.$ctype);
        header('Content-Length: '.filesize($file));
        header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', $lastmod));
        $this->contentLength = filesize($file);
        readfile($file);
        return 200;
    }
    public function __set($key,$value) {
        $kn = "";
        for($n = 0; $n < strlen($key); $n++) {
            if (ctype_upper($key[$n])) $kn.="-".strtolower($key[$n]);
            else $kn.=$key[$n];
        }
        $this->setHeader($kn,$value);
    }
    public function __get($key) {
        $kn = "";
        for($n = 0; $n < strlen($key); $n++) {
            if (ctype_upper($key[$n])) $kn.="-".strtolower($key[$n]);
            else $kn.=$key[$n];
        }
        return $this->getHeader($kn);
    }
    public function __unset($key) {
        $kn = "";
        for($n = 0; $n < strlen($key); $n++) {
            if (ctype_upper($key[$n])) $kn.="-".strtolower($key[$n]);
            else $kn.=$key[$n];
        }
        return $this->clearHeader($kn);
    }

}

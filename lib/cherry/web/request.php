<?php

namespace Cherry\Web;

/*
 * @class Request
 *
 */
class Request implements \ArrayAccess, \IteratorAggregate {
    private $headers = [];
    private $requester = null;
    public function __construct() {
        if (_IS_CLI_SERVER) {
            foreach($_SERVER as $k=>$v) {
                if (substr($k, 0, 5) == 'HTTP_') {
                    $header = str_replace('_', '-', strtolower(substr($k, 5)));
                    $this->headers[$header] = $v;
                }
            }
            $this->requester = [
                'protocol' => $_SERVER['SERVER_PROTOCOL'],
                'method' => $_SERVER['REQUEST_METHOD'],
                'url' => $_SERVER['REQUEST_URI'],
                'remoteip' => $_SERVER['REMOTE_ADDR'],
                'remotehost' => (!empty($_SERVER['REMOTE_HOST']))?
                    $_SERVER['REMOTE_HOST']:
                    null,
                'remoteport' => $_SERVER['REMOTE_PORT'],
                'timestamp' => $_SERVER['REQUEST_TIME']
            ];

        } elseif (function_exists('getallheaders')) {
            // Apache etc
        } else {
            $this->requester = [
                'protocol' => null,
                'method' => null,
                'url' => null,
                'remoteip' => null,
                'remotehost' => null,
                'remoteport' => null,
                'timestamp' => time()
            ];
        }
    }
    
    public function getTimestamp() {
        return $this->requester["timestamp"];
    }
    
    public function getRequestMethod() {
        return $this->requester["method"];
    }
    
    public function getRequestUrl() {
        return $this->requester["url"];
    }
    
    public function setRemoteIp($ip) {
        if (strpos($ip,":")!==false) {
            list($ip,$port) = explode(":",$ip,2);
            $this->requester['remoteport'] = $port;
        }  else {
            $this->requester['remoteport'] = null;
        }
        $this->requester['remoteip'] = $ip;
        $this->requester['remotehost'] = null;
    }
    
    public function getRemoteIp() {
        return $this->requester['remoteip'];
    }

    public function getRemoteHost() {
        if (!$this->requester['remotehost']) {
            if (!empty($this->requester['remoteip']))
                $this->requester['remotehost'] = gethostbyaddr($this->requester['remoteip']);
            else
                return null;
        }
        return $this->requester['remotehost'];
    }

    public function getRemotePort() {
        return $this->requester['remoteport'];
    }

    public function getHeader($header) {
        $header = strtolower($header);
        if (!empty($this->headers[$header]))
            return $this->headers[$header];
        return null;
    }
    
    public function getHeaders() {
        return $this->headers;
    }
    public function offsetGet($index) {
        return $this->getHeader($index);
    }
    public function offsetSet($index,$value) {

    }
    public function offsetUnset($index) { }
    public function offsetExists($index) { }
    public function getIterator() {
        return new \ArrayIterator($this->headers);
    }

    public function __get($key) {
        if (array_key_exists($key,$this->requester))
            return $this->requester[$key];
        return null;
    }

    public function __toString() {
        return $this->asText();
    }

    public function asText() {
        $out = [];
        foreach($this->headers as $header=>$value) {
            $hstr = str_replace(' ', '-', ucwords(str_replace('-', ' ', $header)));
            $out[] = "{$hstr}: {$value}";
        }
        return join("\r\n",$out);
    }
    public function setFromText($text) {
        $headers = split("\r\n",$text);
        if (strpos($headers[0],":") === false) {
            $request = array_shift($headers);
            list($this->requester['method'],$this->requester['url'],$this->requester['protocol']) = explode(" ",$request,3);
        }
        foreach($headers as $header) {
            if ($header) {
                list($k,$v) = explode(":",$header,2);
                $v = trim($v);
                // \debug("Request: setFromText: Got header %s: %s ", $k, $v);
                $this->headers[strtolower($k)] = trim($v);
            }
        }
        
    }
    public function asHtml() {
        $protocol = $this->requester['protocol'];
        $method = $this->requester['method'];
        $url = $this->requester['url'];
        if (strpos($url,"?")!==false) {
            list($url,$qs) = explode("?",$url,2);
            $qs = "?".$qs;
        } else {
            $qs = "";
        }
        $out = [
            "<span style=\"color:#b99\">Request from ".$this->getRemoteIp().':'.$this->getRemotePort()."[".$this->getRemoteHost()."]</span>",
            "<span style=\"font-weight:bold;\">{$protocol} {$method}</span> <span style=\"color:#c00\">{$url}</span><span style=\"color:#c40; font-style:italic\">{$qs}</span>"
        ];
        foreach($this->headers as $header=>$value) {
            $hstr = $this->formatHeader($header);
            $len = strlen($value);
            $out[] = "<span style=\"font-weight:bold;\">{$hstr}</span>: '<span style=\"color: #c00;\">{$value}</span>' <span style=\"font-style:italic;\">(length={$len})</span>";
        }
        return "<pre>".join("\r\n",$out)."</pre>";
    }

    private function formatHeader($header) {
        return str_replace(' ', '-', ucwords(str_replace('-', ' ', $header)));
    }

    public function getRawPostData() {
        return file_get_contents("php://input");
    }

}

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
                'remoteport' => $_SERVER['REMOTE_PORT']
            ];

        } elseif (function_exists('getallheaders')) {
            // Apache etc
        }
    }

    public function getRemoteIp() {
        return $this->requester['remoteip'];
    }

    public function getRemoteHost() {
        if (!$this->requester['remotehost'])
            $this->requester['remotehost'] = gethostbyaddr($this->requester['remoteip']);
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
            "<span style=\"font-weight:bold;\">{$protocol} {$method}</span>: <span style=\"color:#c00\">{$url}</span><span style=\"color:#c40; font-style:italic\">{$qs}</span>",
            ""
        ];
        foreach($this->headers as $header=>$value) {
            $hstr = $this->formatHeader($header);
            $len = strlen($value);
            $out[] = "<span style=\"font-weight:bold;\">{$hstr}</span>: '<span style=\"color: #c00;\">{$value}</span>' <span style=\"font-style:italic;\">(length={$len})</span>";
        }
        $out[] = "";
        $out[] = join(" ", [
            "<strong>Remote IP:</strong> ".$this->getRemoteIp().':'.$this->getRemotePort(),
            "<i>(".$this->getRemoteHost().")</i>"
        ]);
        return "<pre>".join("\r\n",$out)."</pre>";
    }

    private function formatHeader($header) {
        return str_replace(' ', '-', ucwords(str_replace('-', ' ', $header)));
    }

    public function getRawPostData() {
        return file_get_contents("php://input");
    }

}

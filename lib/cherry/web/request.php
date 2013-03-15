<?php

namespace Cherry\Web;

/*
 * @class Request
 *
 */
class Request implements \ArrayAccess, \IteratorAggregate {
    private $headers = [];
    private $complete = false;
    private $requester = null;
    
    /**
     *
     *
     */
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
                'timestamp' => $_SERVER['REQUEST_TIME'],
                'server' => $_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT']
            ];
            $this->complete = true;
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
                'timestamp' => time(),
                'server' => null
            ];
        }
    }
    
    public function setServer($server,$port=80) {
        if (strpos($server,':')!==false)
            list($server,$port) = explode(":",$server,2);
        $this->requester["server"] = $server.':'.(int)$port;
    }

    /**
     *
     * @param string $string The HTTP stream
     * @param bool $append If true, the data will be appended if data already 
     *    exist in the buffer.
     */
    public function createFromString($string, $append=false) {
        if ((!$append) || (empty($this->parser))) {
            $this->complete = false;
            $this->parser = (object)[
                "buffer" => $string,
                "headers" => false,
                "rawheader" => null,
                "rawdata" => null,
                "datapos" => null
            ];
        } else {
            if ($this->parser->headers)
                $this->parser->rawdata.= $string;
            $this->parser->buffer.= $string;
        }
        if ((!$this->parser->headers) && (strpos($this->parser->buffer,"\r\n\r\n")!==false)) {
            $this->parser->datapos = strpos($this->parser->buffer,"\r\n\r\n");
            $this->parser->rawheader = substr($this->parser->buffer,0,$this->parser->datapos);
            $this->parser->rawdata = substr($this->parser->buffer,$this->parser->datapos);
            $hbuf = explode("\r\n",trim($this->parser->rawheader));
            foreach($hbuf as $hstr) {
                if (strpos($hstr,":")!==false) {
                    list($k,$v) = explode(":",str_replace(": ",":",$hstr),2);
                    $this->setHeader(strtolower($k),$v);
                } elseif (strpos(strtoupper($hstr),"HTTP")!==false) {
                    list($method,$url,$proto) = explode(" ",$hstr,3);
                    $this->setRequestMethodInfo($proto,$method,$url);
                }
            }
            $this->complete = true;
        }
        if (($append) && empty($string)) $this->complete = true;
    }

    /**
     *
     * @return bool True if the request has been fully received
     */
    public function isRequestComplete() {
        return $this->complete;
    }

    /**
     *
     * @param string $proto The protocol of the HTTP request
     * @param string $method The method of the HTTP request
     * @param string $url The requested URL
     */
    public function setRequestMethodInfo($proto,$method,$url) {
        $this->requester["protocol"] = $proto;
        $this->requester["method"] = $method;
        $this->requester["url"] = $url;
    }
    
    /**
     * @brief Get the timestamp of the request.
     * Also available as $request["timestamp"]
     *
     * @return int The timestamp
     */
    public function getTimestamp() {
        return $this->requester["timestamp"];
    }
    
    /**
     * @brief Get the HTTP method used for the request
     * Also available as $request["method"]
     *
     * @return string The request method
     */
    public function getRequestMethod() {
        return $this->requester["method"];
    }
    
    /**
     * @brief Get the URL of the request
     * Also available as $request["url"]
     *
     * @return string The URL
     */
    public function getRequestUrl() {
        /* $url = new \Cherry\Net\Url("http://".$this->request["server"]);
        echo "URL: {$url}\n"; */
        return $this->requester["url"];
    }
    
    /**
     *
     */
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
    
    /**
     *
     */
    public function getRemoteIp() {
        return $this->requester['remoteip'];
    }

    /**
     *
     */
    public function getRemoteHost() {
        if (!$this->requester['remotehost']) {
            if (!empty($this->requester['remoteip']))
                $this->requester['remotehost'] = gethostbyaddr($this->requester['remoteip']);
            else
                return null;
        }
        return $this->requester['remotehost'];
    }

    /**
     *
     */
    public function getRemotePort() {
        return $this->requester['remoteport'];
    }

    /**
     *
     */
    public function getHeader($header) {
        $header = strtolower($header);
        if (!empty($this->headers[$header]))
            return $this->headers[$header];
        return null;
    }
    
    public function setHeader($header,$value) {
        $header = strtolower($header);
        $this->headers[$header] = $value;
        if ($header == "host") {
            $this->setServer($value);
        }
        return null;
        
    }
    
    /**
     *
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Return the formatted request as text
     */
    public function asText() {
        $out = [];
        $out[] = $this->requester["method"]." ".$this->requester["url"]." ".$this->requester["protocol"];
        foreach($this->headers as $header=>$value) {
            $hstr = str_replace(' ', '-', ucwords(str_replace('-', ' ', $header)));
            $out[] = "{$hstr}: {$value}";
        }
        return join("\r\n",$out);
    }

    /**
     * Return the formatted request as HTML
     */
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

    /**
     *
     */
    private function formatHeader($header) {
        return str_replace(' ', '-', ucwords(str_replace('-', ' ', $header)));
    }

    /**
     *
     */
    public function getRawPostData() {
        if (!empty($this->parser))
            return $this->parser->rawdata;
        return file_get_contents("php://input");
    }
    
    public function getRequestDataFile() {
        $fn = tempnam(null,"upload");
        file_put_contents($fn,$this->parser->rawdata);
        return $fn;
    }
    
   /**
    * Create a Response object prepared with information from the request such
    * as the request protocol, the request URL (for the Location header), and
    * a default content-type of text/html.
    */
   public function createResponse() {
        $rsp = new Response($this->requester["protocol"], $this->requester["url"]);
        $rsp->host = $this["host"];
        $rsp->contentType = "text/html";
        return $rsp;
    }



    /**
     *
     */
    public function offsetGet($index) {
        return $this->getHeader($index);
    }
    public function offsetSet($index,$value) { }
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


}

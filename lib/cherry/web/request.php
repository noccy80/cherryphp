<?php

namespace Cherry\Web;

define("WEB_REQUEST_MAX_MEMORY",8000000);

/*
 * @class Request
 *
 */
class Request implements \ArrayAccess, \IteratorAggregate {
    
    use \Cherry\Traits\TDebug;
    
    private $headers = [];
    private $complete = false;
    private $requester = null;
    private $cachefile = null;
    private $hcachefile = null;
    
    private $http_protocol;
    private $http_method;
    private $http_uri;
    
    private $remoteip;
    private $remotehost;
    private $remoteport;
    private $servername;
    private $timestamp;

    private $rawrequest;
    private $parserstate;
    
    /**
     * Create a new request.
     *
     * For the CLI_SERVER and Apache, the request will be populated from the
     * current request. for CLI and any other sapi, the request will be blank
     * until it has been parsed via the createFromString() method.
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
            $this->protocol = $_SERVER['SERVER_PROTOCOL'];
            $this->http_method = $_SERVER['REQUEST_METHOD'];
            $this->url = $_SERVER['REQUEST_URI'];
            $this->remoteip = $_SERVER['REMOTE_ADDR'];
            $this->remotehost = (!empty($_SERVER['REMOTE_HOST']))?
                $_SERVER['REMOTE_HOST']:
                null;
            $this->remoteport = $_SERVER['REMOTE_PORT'];
            $this->timestamp = $_SERVER['REQUEST_TIME'];
            $this->server = $_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT'];
            $this->complete = true;
        } elseif (function_exists('getallheaders')) {
            // Apache etc
        } 
    }

    /**
     * Set the server IP and port
     *
     * @todo Deprecate: Makes more sense to set the server from the endpoint URI.
     * 
     * @param string $server The IP address of the server instance
     * @param int $port The port of the server instance.
     */
    public function setServer($server,$port=80) {
        if (strpos($server,':')!==false)
            list($server,$port) = explode(":",$server,2);
        $this->servername = $server.':'.(int)$port;
    }

    /**
     * Create a request from raw HTTP request data.
     *
     * @todo Break at custom size and drop the buffer to disk. Set a flag to
     *      indicate that the request was too big to be kept in memory, and
     *      provide access to the parsed data on the disk, with headers kept
     *      in memory.
     *
     * @param string $string The HTTP stream
     * @param bool $append If true, the data will be appended if data already
     *    exist in the buffer.
     */
    public function createFromString($string, $append=false) {

        // Buffer for the raw request data        
        if (!$this->rawrequest) $this->rawrequest = new FlushableBuffer();
        $this->rawrequest->write($string);
        
        // Parser state and stuff
        if (!$this->parserstate) {
            $this->parserstate = (object)[
                "headers" => false,
                "raw_header" => null,
                "raw_data" => null,
                "data_offset" => null,
                "data_length" => 0
            ];
        }
        
        // If we haven't got headers, look for them in the buffer
        if (!$this->parserstate->headers) {
            // 8KB should be enough for most HTTP requests.
            $buf = $this->rawrequest->getBytes(0,8192);
            $sep = "\r\n\r\n";
            if (strpos($buf,$sep)!==false) {
                $this->debug("Parsing headers from request (%d bytes in buffer)", $this->rawrequest->getLength());
                // Locate the data part of the buffer, the header is 
                $this->parserstate->data_offset = strpos($buf,$sep)+strlen($sep);
                $this->parserstate->raw_header = substr($buf,0,$this->parserstate->data_offset);
                $this->debug("Data starts at %d", $this->parserstate->data_offset);
                // Parse the header part of the buffer
                $hbuf = explode("\r\n",trim($this->parserstate->raw_header));
                foreach($hbuf as $hstr) {
                    $this->debug(" -> %s", trim($hstr));
                    if (strpos($hstr,":")!==false) {
                        list($k,$v) = explode(":",str_replace(": ",":",$hstr),2);
                        $this->setHeader(strtolower($k),$v);
                    } elseif (strpos(strtoupper($hstr),"HTTP")!==false) {
                        list($method,$url,$proto) = explode(" ",$hstr,3);
                        $this->setRequestMethodInfo($proto,$method,$url);
                    }
                }
                $this->debug("Parsing %s", $this->http_method);
                if ($this->http_method == "GET")
                    $this->complete = true;
                $this->parserstate->headers = true;
            } else {
                if (strlen($buf)>=8192) {
                    // This should be an invalid request
                }
            }
        }

        // If header not parsed and break found, then parse the headers.
        if ($this->http_method == "POST") {
            $clen = $this->headers['content-length'];
            $dlen = $this->rawrequest->getLength() - $this->parserstate->data_offset;
            $this->debug("POST info: content-length=%d bytes, parsed-length=%d bytes", $clen, $dlen);
            if ($dlen >= $clen) {
                $this->complete = true;
            }
            
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

    public function getRequestDataAsFile() {
        return $this->rawrequest->getBufferFile();
    }
    
    /**
     *
     * @param string $proto The protocol of the HTTP request
     * @param string $method The method of the HTTP request
     * @param string $url The requested URL
     */
    public function setRequestMethodInfo($proto,$method,$uri) {
        $this->http_protocol = $proto;
        $this->http_method = $method;
        $this->http_uri = $uri;
    }

    /**
     * @brief Get the timestamp of the request.
     * Also available as $request["timestamp"]
     *
     * @return int The timestamp
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * @brief Get the HTTP method used for the request
     * Also available as $request["method"]
     *
     * @return string The request method
     */
    public function getRequestMethod() {
        return $this->http_method;
    }
    
    public function getRequestProtocol() {
        return $this->http_protocol;
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
        return $this->http_uri;
    }
    
    public function getRequestUrlSegment($index) {
        $seg = explode("/",$this->http_uri);
        if (count($seg) > $index + 1)
            return $seg[$index+1];
        return null;
    }

    /**
     *
     */
    public function setRemoteIp($ip) {
        if (strpos($ip,":")!==false) {
            list($ip,$port) = explode(":",$ip,2);
            $this->remoteport = $port;
        }  else {
            $this->remoteport = null;
        }
        $this->remoteip = $ip;
        $this->remotehost = null;
    }

    /**
     *
     */
    public function getRemoteIp() {
        return $this->remoteip;
    }

    /**
     *
     */
    public function getRemoteHost() {
        if (!$this->remotehost) {
            if (!empty($this->remoteip))
                $this->remotehost = gethostbyaddr($this->remoteip);
            else
                return null;
        }
        return $this->remotehost;;
    }

    /**
     *
     */
    public function getRemotePort() {
        return $this->remoteport;
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
        $out[] = $this->http_method." ".$this->http_uri." ".$this->http_protocol;
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
        $protocol = $this->http_protocol;
        $method = $this->http_method;
        $url = $this->http_uri;
        if (strpos($url,"?")!==false) {
            list($url,$qs) = explode("?",$url,2);
            $qs = "?".$qs;
        } else {
            $qs = "";
        }
        $out = [
            "<div>Request from ".$this->getRemoteIp().':'.$this->getRemotePort()."[".$this->getRemoteHost()."]</div>",
            "<div><span style=\"font-weight:bold;\">{$protocol} {$method}</span> <span style=\"color:#c00\">{$url}</span><span style=\"color:#c40; font-style:italic\">{$qs}</span>"
        ];
        foreach($this->headers as $header=>$value) {
            $hstr = $this->formatHeader($header);
            $len = strlen($value);
            $out[] = "<span style=\"font-weight:bold;\">{$hstr}</span>: '<span style=\"color: #c00;\">{$value}</span>' <span style=\"font-style:italic;\">(length={$len})</span>";
        }
        $out[] = "</div>";
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


   /**
    * Create a Response object prepared with information from the request such
    * as the request protocol, the request URL (for the Location header), and
    * a default content-type of text/html.
    */
   public function createResponse() {
        $rsp = new Response($this->http_protocol, $this->http_uri);
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

    public function __toString() {
        return $this->asText();
    }


}

/**
 * Flushable Buffer: 
 */
class FlushableBuffer {
    private $maxmem = null;
    private $mbuffer = null;
    private $dbuffer = null;
    private $hdbuf = null;
    private $buflen = 0;
    
    /**
     * Create the buffer
     *
     * @param int $maxmem The maximum amount of memory to use before swapping to disk.
     */
    function __construct($maxmem=4000000) {
        $this->maxmem = $maxmem;
    }
    
    /**
     * Clean up, the buffer only lives as long as the buffer lives!
     */
    public function __destruct() {
        if ($this->hdbuf)
            fclose($this->hdbuf);
        if ($this->dbuffer)
            unlink($this->dbuffer);
    }
    
    /**
     * Retrieve a file containing the buffer. Will force a flush, and
     * subsequently written data (if any) will be written to the disk.
     *
     * @return mixed The file name of the buffer on disk.
     */
    public function getBufferFile() {
        $this->checkFlush(true);
        return $this->dbuffer;
    }
    
    /**
     * Retrieve a set of bytes from the buffer
     *
     * @param int $start The byte offset to start reading from
     * @param int $length The number of bytes to read out
     * @return mixed The data from the buffer
     */
    public function getBytes($start, $length) {
        if (($start + $length) < $this->maxmem) {
            return substr($this->mbuffer,$start,$length);
        } else {
            fseek($this->hdbuf,$start,\SEEK_SET);
            return fread($this->hdbuf, $length);
        }
    }
    
    /**
     * Return the full length of the buffer
     *
     * @return integer The length of the buffer
     */
    public function getLength() {
        return $this->buflen;
    }
    
    /**
     * Write data to the buffer
     *
     * @param mixed $data The data to write
     */
    public function write($data) {
        $this->buflen += strlen($data);
        if ($this->dbuffer) {
            fseek($this->hdbuf,0,\SEEK_END);
            fwrite($this->hdbuf,$data);
            fflush($this->hdbuf);
        } else {
            $this->mbuffer.= $data;
            $this->checkFlush();
        }
    }
    
    /**
     * Check size of data in buffer, and flush if it exceeds maxmem.
     *
     * @param bool $force If true, the buffer is always moved to disk.
     */
    private function checkFlush($force=false) {
        if ((!$force) && ($this->buflen<$this->maxmem)) return;
        // Flush to file: Generate temporary name
        $this->dbuffer = tempnam(null,"fbuf");
        // Write the buffer to file.
        $this->hdbuf = fopen($this->dbuffer,"wb");
        fwrite($this->hdbuf,$this->mbuffer);
    }
}

<?php

namespace cherry\Mvc;

use Cherry\Base\Event;

class Request {

    // Constants
    const CTX_APACHE = 'apache';
    const CTX_FCGI = 'fcgi';
    const CTX_OTHER = '??';

    // Private properties
    private
            $context = null,
            $uri = null,
            $method = null,
            $remoteip = null,
            $remotehost = null,
            $remoteport = null,
            $sapi = null,
            $protocol = null;

    public function __construct($context=null) {
        $this->context = $context;
        Event::invoke(\Cherry\Mvc\EventsEnum::REQUEST_CREATE,$this);
        $this->sapi = php_sapi_name();
        switch($this->sapi) {
            case 'cli-server':
                $this->server = $_SERVER['HTTP_HOST'];
                $this->uri = $_SERVER['REQUEST_URI'];
                $this->method = $_SERVER['REQUEST_METHOD'];
                $this->remoteip = $_SERVER['REMOTE_ADDR'];
                $this->remoteport = $_SERVER['REMOTE_PORT'];
                $this->protocol = $_SERVER['SERVER_PROTOCOL'];
                $this->accept = new HttpAcceptHeader($_SERVER['HTTP_ACCEPT']);
                break;
            case 'cli':
                $this->server = getenv('REQUEST_HOST')?:'localhost';
                $this->protocol = 'HTTP/1.1';
                $this->accept = new HttpAcceptHeader('*/*');
            default:
                if (empty($_SERVER['REQUEST_URI'])) {
                    if ($requri = getenv('REQUEST_URI')) {
                        $this->uri = $requri;
                        $this->method = 'GET';
                    }
                } else {
                    $this->uri = $_SERVER['REQUEST_URI'];
                    $this->method = (empty($_SERVER['REQUEST_METHOD']))?'GET':$_SERVER['REQUEST_METHOD'];
                }
        }
        $this->uri = ($this->uri)?:'/';
        $this->method = ($this->method)?:'GET';
    }

    public function __toString() {
        return sprintf("%s %s %s", $this->protocol, $this->method, $this->uri);

    }

    public function getUri() {
        return $this->uri;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getHeader($header) {
        $key = 'HTTP_'.strtoupper(str_replace('-','_',$header));
        if (!empty($_SERVER[$key]))
            return $_SERVER[$key];
        return null;
    }

    public function getProtocol() {
        return $this->protocol;
    }

}

class HttpAcceptHeader {

    private
            $accept = null,
            $fragments = [];

    public function __construct($string) {
        $this->accept = $string;
        $fragments = explode(',',$this->accept);
        foreach($fragments as $fragment) {
            if (strpos($fragment,';q=')!==false)
                list($contenttype,$q) = explode(';q=',$fragment);
            else
                list($contenttype,$q) = [ $fragment, 1.0 ];
            $this->fragments[$contenttype] = $q;
        }
    }

    public function getPreferedType(array $types) {

    }

    public function getAcceptedTypes() {
        return $this->fragments;
    }

}

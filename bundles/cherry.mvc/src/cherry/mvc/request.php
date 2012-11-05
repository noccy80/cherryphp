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
            $sapi = null;

    public function __construct($context=null) {
        $this->context = $context;
        Event::invoke(\Cherry\Mvc\EventsEnum::REQUEST_CREATE,$this);
        $this->sapi = php_sapi_name();
        switch($this->sapi) {
            case 'cli-server':
                $this->server = $_SERVER['HTTP_HOST'].':'.$_SERVER['HTTP_PORT'];
                $this->uri = $_SERVER['REQUEST_URI'];
                $this->method = $_SERVER['REQUEST_METHOD'];
                $this->remoteip = $_SERVER['REMOTE_ADDR'];
                $this->remoteport = $_SERVER['REMOTE_PORT'];
                break;
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
        return sprintf("%s (HTTP %s)", $this->uri, $this->method);

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

}

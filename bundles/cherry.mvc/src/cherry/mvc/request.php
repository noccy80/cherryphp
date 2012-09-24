<?php

namespace cherry\Mvc;

use Cherry\Base\Event;

class Request {

    // Constants
    const CTX_APACHE = 'apache';
    const CTX_FCGI = 'fcgi';
    const CTX_OTHER = '??';

    // Private properties
    private $context = null;
    private $url = null;
    private $method = null;

    public function __construct($context=null) {
        $this->context = $context;
        Event::invoke(\Cherry\Mvc\EventsEnum::REQUEST_CREATE,$this);
        
        if (empty($_SERVER['REQUEST_URI'])) {
            if ($requri = getenv('REQUEST_URI')) {
                $this->url = $requri;
                $this->method = 'GET';
            }
        } else {
            $this->url = $_SERVER['REQUEST_URI'];
            $this->method = (empty($_SERVER['REQUEST_METHOD']))?'GET':$_SERVER['REQUEST_METHOD'];
        }
        if (!$this->url) $this->url = '/';
    }
    
    public function __toString() {
        return sprintf("%s (HTTP %s)", $this->url, $this->method);
        
    }
    
    public function getRequestUrl() {
        return $this->url;
    }

}

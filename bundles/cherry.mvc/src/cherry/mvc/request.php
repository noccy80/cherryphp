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

    public function __construct($context=null) {
        $this->context = $context;
        Event::invoke(\Cherry\Mvc\EventsEnum::REQUEST_CREATE,$this);
        
        if (empty($_SERVER['REQUEST_URI'])) {
            $this->url = '/cherrypanel/foo';
        } else {
            $this->url = $_SERVER['REQUEST_URI'];
        }
    }
    
    public function getRequestUrl() {
        return $this->url;
    }

}

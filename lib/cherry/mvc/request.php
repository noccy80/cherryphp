<?php

namespace cherry\Mvc;

class Request {

    // Constants
    const CTX_APACHE = 'apache';
    const CTX_FCGI = 'fcgi';
    const CTX_OTHER = '';

    // Private properties
    private $context = null;

    public function __construct($context=null) {
        $this->context = $context;
    }
    
    public function getRequestUrl() {
    
    }

}

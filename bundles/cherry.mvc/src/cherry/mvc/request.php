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
            $protocol = null,
            $cache_control = null;

    public function __construct($context=null) {
        $this->context = $context;
        Event::invoke(\Cherry\Mvc\EventsEnum::REQUEST_CREATE,$this);
        $this->sapi = php_sapi_name();
        if (!empty($_SERVER['SERVER_PROTOCOL']))
            $this->protocol = $_SERVER['SERVER_PROTOCOL'];
        switch($this->sapi) {
            case 'cli-server':
                $this->server = $_SERVER['HTTP_HOST'];
                $this->uri = $_SERVER['REQUEST_URI'];
                $this->method = $_SERVER['REQUEST_METHOD'];
                $this->remoteip = $_SERVER['REMOTE_ADDR'];
                $this->remoteport = $_SERVER['REMOTE_PORT'];
                $this->protocol = $_SERVER['SERVER_PROTOCOL'];
                $this->accept = new HttpAcceptRequestDirective($_SERVER['HTTP_ACCEPT']);
                $this->cache_control = new HttpCacheRequestDirective($_SERVER['HTTP_CACHE_CONTROL']);
                break;
            case 'cli':
                $this->server = getenv('REQUEST_HOST')?:'localhost';
                $this->protocol = 'HTTP/1.1';
                $this->accept = new HttpAcceptRequestDirective('*/*');
                $this->method = getenv('REQUEST_METHOD')?:'GET';
                $this->uri = getenv('REQUEST_URI')?:'/';
            default:
                if (!empty($_SERVER['REQUEST_URI'])) {
                    $this->uri = $_SERVER['REQUEST_URI'];
                }
                if (!$this->method) $this->method = (empty($_SERVER['REQUEST_METHOD']))?'GET':$_SERVER['REQUEST_METHOD'];
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

class HttpCacheRequestDirective {
    
    private
            $directives = [],
            $extensions = [],
            $header = null;
            
    public function __construct($string) {
        $directives = explode(',',$string);
        $this->header = $string;
        foreach($directives as $directive) {
            $directive = trim($directive);
            if (strpos($directive,'='))
                list($dname,$dvalue) = explode('=',$directive);
            else
                list($dname,$dvalue) = [ $directive, true ];
            switch(strtolower($dname)) {
                case 'no-cache':
                case 'no-store':
                case 'max-age':
                case 'max-stale':
                case 'min-fresh':
                case 'no-transform':
                case 'only-if-cached':
                    $this->directives[$dname] = $dvalue;
                    break;
                default:
                    $this->extensions[$dname] = $dvalue;
                    break;
            }
            
        }
        
    }
    
    public function __toString() {
        return $this->header;
    }
    
}

class HttpAcceptRequestDirective {

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

    public function getPreferedType(array $types, array $offers) {
        // Calculate weights, eg:
        // Client:  text/xml;q=1.0,text/html;q=0.8
        // Offers:  text/xml (0.5), text/html(1.0)
        // Actual:  text/xml = 1.0*0.5 = 0.5,
        //          text/html = 0.8*1.0 = 0.8 <- winner!
    }

    public function getAcceptedTypes() {
        return $this->fragments;
    }

}

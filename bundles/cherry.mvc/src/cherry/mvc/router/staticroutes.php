<?php

namespace Cherry\Mvc\Router;

use Cherry\Mvc\Request;

class StaticRoutes {

    private static $instance = null;
    private $routes = array();
    
    public static function getInstance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function addRoute($url,$controller,array $opts=null) {
        $this->routes[$url] = array(
            $controller, $opts
        );
    }
    
    public function checkRoute(Request $request) {
        
        $ru = explode('/',$request->getRequestUrl());
        foreach($this->routes as $url => $target) {
            $call_controller = $target[0];
            $call_method = 'index';
            $call_args = array();
            $uu = explode('/',$url);
            $matched = true;
            for($n = 0; $n < count($ru); $n++) {
                if ($uu[$n] == '') {
                    //
                } else if ($uu[$n][0] == ':') {
                    switch($uu[$n]) {
                        case ':method':
                            $call_method = $ru[$n];
                            break;
                        case ':controller':
                            $call_controller = $ru[$n];
                            break;
                        case ':args':
                            $call_args = array_slice($ru,$n+1);
                            break;
                    }
                } else if ($ru[$n] != $uu[$n]) {
                    $matched = false;
                    break;
                }
            }
            if ($matched) {
                $ci = new $call_controller($request);
                $ci->method = $call_method;
                $ci->args = $call_args;
                return $ci;
            }
            
        }
        
    }
    
}


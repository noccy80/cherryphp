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
        
        \Cherry\debug('Checking static route for request: %s', $request);
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
                \Cherry\debug('Request %s matched static route %s', $request, $url);
                // Extract method from the call if present, and if not use call_method
                list($cclass,$cmethod) = explode(':',$call_controller.':'.$call_method);
                \Cherry\debug('Controller: %s, Action: %s', $cclass,$cmethod);
                $ci = \Cherry\Mvc\Controller\Base::factory($cclass,$request);
                $ci->method = $cmethod;
                $ci->args = $call_args;
                return $ci;
            }
            
        }
        
    }
    
}


<?php

namespace cherry\Mvc\Router;

abstract class Base {

    abstract function route(\cherry\Mvc\Request $request);
    protected function checkStaticRoutes(\Cherry\Mvc\Request $request) {
        $rt = StaticRoutes::getInstance();
        $dest = $rt->checkRoute($request);
        if (is_object($dest)) {
            \Cherry\Log(\Cherry\LOG_DEBUG,"Static route found for request: %s", $request);
            return $dest;
        }
        return null;
    }
    
}


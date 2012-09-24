<?php

namespace cherry\Mvc\Router;

abstract class Base {

    abstract function route(\cherry\Mvc\Request $request);
    protected function checkStaticRoutes(\Cherry\Mvc\Request $request) {
        $rt = StaticRoutes::getInstance();
        $dest = $rt->checkRoute($request);
        if (is_object($dest)) {
            $dest->invoke();
        }
        return true;
    }
    
}


<?php

namespace Cherry\Expm\Mvc;

use \Cherry\Base\PathResolver;
use \Cherry\Data\Ddl\SdlTag;
use \Cherry\Web\Request;
use \Cherry\Web\Response;

class Router {
    private $routes = [];
    private $params = null;
    public function __construct() {
        $cfg = PathResolver::getInstance()->getPath("{APP}/config/routes.sdl");
        if (!file_exists($cfg) || !is_readable($cfg)) {
            \debug("Could not read routelist from {$cfg}.");
        } else {
            $root = new SdlTag();
            $root->loadFile($cfg);
            $this->routes = $root->spath("/routes/route");
            $num = count($this->routes);
            \debug("Parsed {$num} routes from {$cfg}");
        }

    }

    public function route($uri) {

        $fspath = PathResolver::getInstance()->getPath("{PUBLIC}".$uri);
        if (_IS_CLI_SERVER && is_file($fspath)) {
            $r = new \Cherry\Web\Response();
            return $r->sendFile($fspath);
        }

        // Loop through every route and compare it with the URI
        foreach ($this->routes as $route) {

            // Create a route with all identifiers replaced with ([^/]+) regex syntax
            // E.g. $route_regex = shop-please/([^/]+)/moo (originally shop-please/:some_identifier/moo)
            $route_regex = preg_replace('@:[^/]+@', '([^/]+)', $route[0]);

            // Check if URI path matches regex pattern, if so create an array of values from the URI
            if(!preg_match('@^' . $route_regex . '$@', $uri, $matches)) continue;

            // Create an array of identifiers from the route
            preg_match('@^' . $route_regex . '$@', $route[0], $identifiers);

            // Decode the matches
            $matches = array_map(function($str){return urldecode($str); },$matches);
            $identifiers = array_map(function($str){return substr($str,1); },$identifiers);

            // Combine the identifiers with the values
            $params = array_combine($identifiers, $matches);
            array_shift($params);
            $action = $route->action;

            $this->dispatch($action,$params);

            return 200;
        }
        return 404;

    }

    public function dispatch($action,array $params) {
        if (strpos($action,".")!==false) {
            list ($class,$action) = explode(".",$action);
            $action = "{$action}Action";
        } else {
            $class = $action;
            if (!empty($params['action'])) {
                $action = $params['action'].'Action';
            } else {
                $action = "defaultAction";
            }
        }
        if (strpos($class,"\\")===false) {
            $class = APP_NS."\\Controllers\\".ucwords($class)."Controller";
        }
        $request = new Request();
        $response = new Response();
        if (!class_exists($class))
            throw new \Exception("Could not find controller class {$class}");
        $dobj = new $class($request,$response,$params);
        if (!is_callable([$dobj,$action]))
            throw new \Exception("Could not find action {$action} in {$class}");
        call_user_func_array([$dobj,$action],$params);
    }
}

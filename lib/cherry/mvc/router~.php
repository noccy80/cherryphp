<?php

namespace Cherry\Mvc;
use Cherry\Traits\SingletonAccess;

class Router {
    use SingletonAccess;
    private
            $request = null,
            $response = null,
            $routes = [],
            $passthru = [];

    public function __construct() {
        $this->request = new Request();
        $this->response = new Response($this->request->getProtocol());
    }

    public function route() {
        $uri = $this->request->getUri();
        foreach($this->passthru as $rule=>$target) {
            if (fnmatch($rule,$uri)) {
                if ($target == null) {
                    $file = APP_PUBLIC._DS_.$uri;
                } else {
                    $file = APP_ROOT._DS_.$target.$uri;
                }
                if (file_exists($file)) {
                    $this->response->sendFile($file);
                } else {
                    $this->response->setStatus(404,'File not found');
                    echo "File not found.";
                }
                \App::server()->log('%s: %s', (string)$this->request, (string)$this->response);
                return;
            }
        }
        foreach($this->routes as $rule=>$route) {
            $re = str_replace('/','\/',$rule);
            $re = str_replace(':str','[a-zA-Z\.,\-]*',$re);
            $match = [];
            if (preg_match("/^{$re}/",$uri,$match)) {
                if (strpos($route,':')!==false)
                    list($tctl,$tparms) = explode(':',$route);
                else
                    list($tctl,$tparms) = [ $route, []];
                $tcclass = explode('/',$tctl);
                $tcmethod = array_pop($tcclass);
                $tparms = explode(',',$tparms);
                $tcargs = [];
                foreach($tparms as $parm) {
                    if ($parm[0] == '$') {
                        $tcargs[] = $match[intval(substr($parm,1))];
                    }
                }
                require_once 'controllers'._DS_.strtolower(join(_DS_,$tcclass)).'.php';
                if (count($tcclass) == 1) {
                    // Append app namespace
                    $tcclass = $tcclass[0].'Controller';
                } else {
                    $tcclass = "\\".join("\\",$tcclass).'Controller';
                }
                $tcclass = APP_NS."\\Controllers\\".$tcclass;
                \App::server()->log('%s => %s:%s [%s]', (string)$this->request, ucwords($tcclass),$tcmethod,join(',',$tcargs));
                $ctl = new $tcclass($this->request, $this->response);
                $ctl->invoke($tcmethod,$tcargs);
                return true;
            }
        }
    }
    public function addRoutes($routes) {
        $routes = (array)$routes;
        $this->routes = array_merge($this->routes,$routes);
    }
    public function addPassthru($routes) {
        $routes = (array)$routes;
        $this->passthru = array_merge($this->passthru,$routes);
    }
}

<?php

namespace cherry\Mvc;
use Cherry\Traits\SingletonAccess;

class Router {
    use SingletonAccess;
    private
            $request = null,
            $response = null,
            $routes = [],
            $passthru = [],
            $so = null;

    public function __construct() {
        $this->request = new Request();
        $this->response = new Response($this->request->getProtocol());
        if (defined('IS_PROFILING')) $this->so = \App::profiler()->enter('Routing request');
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
                    $this->response->setCacheControl('public,max-age=3600');
                    $this->response->sendFile($file);
                } else {
                    $this->response->send404($file);
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
                for($n = 1; $n < count($match); $n++) {
                    $route = str_replace('$'.$n,$match[$n],$route);
                }
                if (strpos($route,':')!==false)
                    list($tctl,$tparms) = explode(':',$route);
                else
                    list($tctl,$tparms) = [ $route, []];
                $tcclass = explode('/',$tctl);
                $tcmethod = array_pop($tcclass);
                $tparms = (array)explode(',',$tparms);
                $tcargs = [];
                foreach($tparms as $parm) {
                    if (($parm) && ($parm[0] == '$')) {
                        $tcargs[] = $match[intval(substr($parm,1))];
                    }
                }
                require_once 'controllers'._DS_.strtolower(join(_DS_,$tcclass)).'.php';
                if (count($tcclass) == 1) {
                    // Append app namespace
                    $tcclass = ucwords($tcclass[0]).'Controller';
                } else {
                    $tcclass = "\\".join("\\",$tcclass).'Controller';
                }
                $tcclass = str_replace("\\\\","\\",APP_NS."\\Controllers\\".$tcclass);
                if (!$tcmethod) $tcmethod = 'index';
                \App::server()->log('%s => %s:%s [%s] (%s)', (string)$this->request, ucwords($tcclass),$tcmethod,join(',',$tcargs),substr($this->request->getHeader('User-Agent'),0,40));
                if (defined('IS_PROFILING')) \App::profiler()->log('Calling controller');
                $ctl = new $tcclass($this->request, $this->response);
                $ctl->invoke($tcmethod,$tcargs);
                return true;
            }
        }
    }
    public function addRoutes($routes,$prepend=false) {
        $routes = (array)$routes;
        if ($prepend)
            $this->routes = array_merge($routes,$this->routes);
        else
            $this->routes = array_merge($this->routes,$routes);
    }
    public function addPassthru($routes,$prepend=false) {
        $routes = (array)$routes;
        if ($prepend)
            $this->passthru = array_merge($routes,$this->passthru);
        else
            $this->passthru = array_merge($this->passthru,$routes);
    }
}

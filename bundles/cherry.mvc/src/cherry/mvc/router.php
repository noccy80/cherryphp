<?php

namespace cherry\Mvc;
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
        $this->response = new Response();
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
                    \App::server()->log('%s %s == %s (%s)', $this->request->getMethod(), $uri, $file, '200');
                    $ct = null;
                    foreach([
                        '*.css' => 'text/css',
                        '*.js' => 'text/javascript'
                    ] as $ptn => $ct)
                        if (fnmatch($ptn,$file))
                            $ctype = $ct;
                    if (!$ct) $ct = mime_content_type($file);
                    header('Content-Type: '.$ctype);
                    header('Content-Length: '.filesize($file));
                    readfile($file);
                    return;
                } else {
                    \App::server()->log('%s %s == %s (%s)', $this->request->getMethod(), $uri, $file, '404');
                    header($this->request->getProtocol()." 404 Not found", true, 404);
                    echo "File not found.";
                    return;
                }
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
                \App::server()->log('%s %s -> %s:%s [%s]', $this->request->getMethod(), $uri, ucwords($tcclass),$tcmethod,join(',',$tcargs));
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

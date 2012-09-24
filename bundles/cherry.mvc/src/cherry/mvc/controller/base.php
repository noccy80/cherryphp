<?php

namespace cherry\Mvc\Controller;

abstract class Base {

    protected $app = null;
    protected $request = null;
    protected $cmethod = null;
    protected $cargs = array();

    function __construct(\cherry\Mvc\Request $request) {
        $this->app = \cherry\Application::getInstance();
        $this->request = $request;
        if (is_callable(array($this,'initialize'))) $this->initialize();
        \Cherry\Base\Event::invoke('cherry:mvc.controller.create',$this);
    }

    public function __get($key) {
        switch($key) {
            case 'method': return $this->cmethod; break;
            case 'args': return $this->cargs; break;
            default: return null;
        }
    }

    public function __set($key,$val) {
        switch($key) {
            case 'method': $this->cmethod = $val; break;
            case 'args': $this->cargs = $val; break;
            default: return null;
        }
    }
    
    public function invoke() {
        if (!is_callable(array($this,$this->cmethod))) {
            printf("No such method: %s", $this->cmethod); die();
        } else {
            call_user_func(array($this,$this->cmethod));
        }
    }
    
    static function factory($cn, \cherry\Mvc\Request $req) {
        // Check the namespace
        $app = \cherry\Application::getInstance();
        $cfg = $app->getConfiguration('application','application');
        $ns = $cfg['namespace'];
        if (substr($cn,0,strlen($ns)) == $ns) {
            $cpath = explode('\\',strToLower($cn));
            $fpath = CHERRY_APP . DIRECTORY_SEPARATOR . 'application/' . join(DIRECTORY_SEPARATOR, array_slice($cpath,2));
        } else {
            $fpath = strToLower($cn);
        }
        $fpath = str_replace('\\',DIRECTORY_SEPARATOR,$fpath);
        if (substr($fpath,-10,10) == 'controller') {
            $fpath = substr($fpath,0,strlen($fpath)-10);
        }
        $fpath.= '.php';
        if (file_exists($fpath)) {
            require_once($fpath);
            $cobj = new $cn($req);
            return $cobj;
        } else {
            throw new \Exception("Could not include file; ". $fpath);
        }
    }

}

class Basic extends Base {

    function unhandled(\cherry\Mvc\Request $req) {
    
    }

}

class Simple extends Base {

}

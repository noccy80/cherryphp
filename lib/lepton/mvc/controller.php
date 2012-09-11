<?php

namespace Lepton\Mvc\Controller;

abstract class Base {

    protected $app = null;
    protected $req = null;

    function __construct(\Lepton\Mvc\Request $req) {
        $this->app = \Lepton\Application::getInstance();
        $this->req = $req;
        if (is_callable(array($this,'initialize'))) $this->initialize();
    }

    static function factory($cn, \Lepton\Mvc\Request $req) {
        // Check the namespace
        $app = \Lepton\Application::getInstance();
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

    function unhandled(\Lepton\Mvc\Request $req) {
    
    }

}

class Simple extends Base {

}

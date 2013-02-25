<?php

namespace Cherry\Expm\Mvc;

use Cherry\Application;
use Cherry\Expm\Mvc\Router;
use Cherry\Web\WebApplication;

/*
 * class MvcApplication
 */
class MvcApplication extends WebApplication {
    protected $router = null;
    function __construct($path) {
        parent::__construct($path);
        $this->router = new Router();
    }
    function onRequest($uri) {
        $this->router->route($uri);
    }
    function handleException($e) {
        echo "Unhandled exception:<br><br><pre>".wordwrap($e,90)."</pre>";
        die();
    }
}

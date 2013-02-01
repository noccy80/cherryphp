<?php

namespace Cherry\Mvc;

use Cherry\Extension\ExtensionManager;
use Cherry\Extension\ExtensionException;
use Cherry\Mvc\Router;
use App;

abstract class MvcApplication extends \cherry\Application {

    function __construct() {
        parent::__construct();
        App::config()->addConfiguration(APP_ROOT._DS_.'config.json');
        App::extend('router', new \Cherry\Mvc\Router());
        App::extend('server', new \Cherry\Mvc\Server());
    }

    abstract function setup();

    function main() {
        $this->setup();
        App::router()->route();
    }

}

<?php

define('APPLICATION','LeptonApplication');

// Bootstrap
if (!( @include_once "lib/bootstrap.php" )) {
    $libpath = getenv('CHERRY_LIB');
    if (!$libpath) {
        fprintf(STDERR,"Define the CHERRY_LIB envvar first.");
        exit(1);
    }
    require_once($libpath.'/lib/bootstrap.php');
}

$lepton = new \cherry\Lepton(__FILE__);

use cherry\base\Event;
use cherry\base\EventEmitter;
use cherry\BundleManager;
use Cherry\Extension\ExtensionManager;

BundleManager::load('cherry.net');
BundleManager::load('cherry.mvc');
BundleManager::load('cherry.crypto');
BundleManager::load('cherry.user');

//ExtensionManager::load('cherrybar');
$rt = \Cherry\Mvc\Router\StaticRoutes::getInstance();
$rt->addRoute('/start/','\MyApp\Controllers\IndexController:start');

$app = new \cherry\Mvc\Application();

$lepton->runApplication($app);


//$lv = new \cherry\mvc\view\LipsumView();
//echo $lv->getViewcontents();

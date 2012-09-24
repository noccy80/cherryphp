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

require_once('lib/bundles.php');
require_once('lib/cherry/base/autoloader.php');

BundleManager::load('cherry.net');
BundleManager::load('cherry.mvc');
BundleManager::load('cherry.crypto');

//ExtensionManager::load('cherrybar');

$app = new \cherry\Mvc\Application();

$lepton->runApplication($app);


//$lv = new \cherry\mvc\view\LipsumView();
//echo $lv->getViewcontents();

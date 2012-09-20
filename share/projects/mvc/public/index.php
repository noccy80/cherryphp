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

$app = new \cherry\Mvc\Application();

require_once('lib/cherry/mvc/view/lipsum.php');
$lepton->runApplication($app);


//$lv = new \cherry\mvc\view\LipsumView();
//echo $lv->getViewcontents();

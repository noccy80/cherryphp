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

$lepton = new \Lepton\Lepton(__FILE__);

$app = new \Lepton\Mvc\Application();

$lepton->runApplication($app);


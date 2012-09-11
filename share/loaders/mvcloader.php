<?php
/**
 * CherryPHP MVC Loader Boilerplate
 *
 * (c) 2012, The CherryPHP Project
 * Licensed under the GNU GPL Version 3
 */

define('APPLICATION','LeptonApplication');

require_once('../lib/lepton/lepton.php');
require_once('../lib/lepton/mvc/application.php');

$lepton = new \Lepton\Lepton(__FILE__);
$app = new \Lepton\Mvc\Application();

$lepton->setApplication($app);
$app->run();

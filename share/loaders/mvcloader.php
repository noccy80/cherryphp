<?php
/**
 * CherryPHP MVC Loader Boilerplate
 *
 * (c) 2012, The CherryPHP Project
 * Licensed under the GNU GPL Version 3
 */

define('APPLICATION','LeptonApplication');

require_once('../lib/cherry/lepton.php');
require_once('../lib/cherry/mvc/application.php');

$lepton = new \cherry\Lepton(__FILE__);
$app = new \cherry\Mvc\Application();

$lepton->setApplication($app);
$app->run();

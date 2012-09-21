<?php

$bpath = dirname(__FILE__)._DS_.'src';

use Cherry\Autoloader\Autoloaders;
use Cherry\Autoloader\Autoloader;
Autoloaders::register(new Autoloader($bpath));

return array(
    'src' => $bpath,
    'autoload' => array(
        'Cherry\Mvc\Application',
        'Cherry\Mvc\Session',
        'Cherry\Mvc\Response',
        'Cherry\Mvc\Request',
        'Cherry\Mvc\Cookies'
    ),
    'depends' => array(
        'cherry.db'
    )
);

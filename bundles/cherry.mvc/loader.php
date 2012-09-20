<?php

use Cherry\Autoloaders;
use Cherry\Autoloader;

$bpath = dirname(__FILE__)._DS_.'src';

Autoloaders::register(new Autoloader($bpath));

return array(
    'autoload' => array(
        'Cherry\Mvc\Application',
        'Cherry\Mvc\Session',
        'Cherry\Mvc\Response',
        'Cherry\Mvc\Request',
        'Cherry\Mvc\Cookies'
    )
);

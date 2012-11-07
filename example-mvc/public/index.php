<?php

/*
//LOADER:BEGIN
require('cherryphp.php');
//LOADER:END
*/

//LOADER:BEGIN
if (!( @include_once "lib/bootstrap.php" )) {
    $libpath = getenv('CHERRY_LIB');
    if (!$libpath) {
        fprintf(STDERR,"Define the CHERRY_LIB envvar first.");
        exit(1);
    }
    require_once($libpath.'/lib/bootstrap.php');
}
//LOADER:END

App::bootstrap([
    'app.ns' => 'ExampleSite',
    'app.public' => dirname(__FILE__),
    'app.root' => dirname(dirname(__FILE__))
]);
App::bundles()->load('cherry.mvc');
App::router()->addRoutes([
    '/debug' => 'default/debug',
    '/post/(.*)' => 'default/view:$1',
    '/(:str)/(:str)' => 'default/index:$1,$2',
    '/(:str)' => 'default/index:$1',
    '/' => 'default/index'
]);
App::router()->addPassthru([
    '/js/*' => 'public',
    '/css/*' => 'public',
    '/favicon*' => 'public'
]);
App::router()->route();

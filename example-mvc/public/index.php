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
    'app.ns' => 'CherryTree',
    'app.public' => dirname(__FILE__),
    'app.root' => dirname(dirname(__FILE__)),
    'bundles' => [
        'cherry.mvc'
    ]
]);
//App::extend('profiler',new AppProfiler('perf.log'));
//App::profiler()->setReporting(AppProfiler::REPORT_FULL);
//App::profiler()->push('Loading bundles...');
//App::bundles()->load('cherry.mvc');
//App::profiler()->pop();
App::router()->addRoutes([
    '/admin/posts/(:str)' => 'admin/posts/$1',
    '/debug' => 'default/debug',
    '/test' => 'default/test',
    '/post/(.*)' => 'default/view:$1',
    '/(:str)/(:str)' => 'default/index:$1,$2',
    '/(:str)' => 'default/index:$1',
    '/' => 'default/index'
]);
// This is only really needed for the PHP embedded web server, but could be
// handy in some other situations as well.
App::router()->addPassthru([
    '/js/*' => 'public',
    '/css/*' => 'public',
    '/images/*' => 'public',
    '/favicon*' => 'public'
]);
App::router()->route();


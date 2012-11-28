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
    'autoload.bundles' => [    ],
    'autoload.paths' => [
        'CherryTree'    => 'src/',
        '*'             => 'vendor/'
    ]
]);

if (is_readable(APP_ROOT.'/vendor/autoload.php')) require(APP_ROOT.'/vendor/autoload.php');

//new Cherry\Mvc\Loader();

require_once('src/widgets/sidebar.php');
require_once('src/widgets/aboutcherry.php');

use Cherry\Util\AppProfiler;
use Cherry\Mvc\MvcApplication;

if (getenv('PROFILE')) {
    App::extend('profiler',new AppProfiler('perf.log'));
    App::profiler()->setReporting(AppProfiler::REPORT_FULL);
}

class PrismApplication extends MvcApplication {
    public function setup() {
        App::router()->addRoutes([
            '/admin/posts/(:str)' => 'admin/posts/$1',
            '/debug' => 'default/debug',
            '/test' => 'default/test',
            '/view' => 'default/view',
            '/view/(.*)' => 'default/view:$1',
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
    }
}
//App::router()->route();
App::run(new PrismApplication());

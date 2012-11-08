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
    'app.root' => dirname(dirname(__FILE__))
]);
App::extend('benchmark',new BenchmarkClass());
App::bundles()->load('cherry.mvc');
App::router()->addRoutes([
    '/admin/posts/(:str)' => 'admin/posts/$1',
    '/debug' => 'default/debug',
    '/test' => 'default/test',
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

class BenchmarkClass {
    private
            $target = null,
            $start = null,
            $log = [],
            $stack = [];
    public function push($module) {
        $this->log[] = [ microtime(true), count($this->stack), 'Enter: '.$module];
        array_push($this->stack, $module);
    }
    public function pop() {
        $this->log[] = [ microtime(true), count($this->stack), 'Leave: '.end($this->stack)];
        array_pop($this->stack);
    }
    public function log($msg) {
        $this->log[] = [ microtime(true), count($this->stack), $msg];
    }
    public function __construct($target=null) {
        $this->target = $target;
        $this->start = microtime(true);
    }
    public function __destruct() {
        if (!$this->target) return;
        $out = [];
        foreach($this->log as $event) {
            list($time,$level,$message) = $event;
            $out[] = sprintf('+%.4f %s', ($time - $this->start), str_repeat(' ',$level).$message);
        }
        file_put_contents($this->target,join("\n",$out));
    }
}
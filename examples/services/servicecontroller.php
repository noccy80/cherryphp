<?php

require_once "../../share/include/cherryphp";

use Cherry\Core\ServiceManager as SvcMan;
use Cherry\Core\ServiceInstance;
SvcMan::register();

class TestService extends ServiceInstance {
    public $serviceid = "info.noccylabs.testservice";
    protected $flags = ServiceInstance::SVC_RESTART;
    function servicemain() {
        for($s = 0; $s < 5; $s++) {
            usleep(100000);
        }
    }
    function onShutdown() {
    }
}
SvcMan::addServiceInstance(new TestService("/tmp/testservice.pid"));

App::run(new \Cherry\Cli\ServiceController("local:/services/info.noccylabs.testservice#0",__DIR__),__DIR__);

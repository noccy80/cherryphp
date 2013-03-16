<?php

require_once __DIR__."/../../share/include/cherryphp";

use Cherry\Core\ObjectManager as ObjMan;
use Cherry\Core\IObjectManagerInterface;
use Cherry\Core\ServiceManager as SvcMan;
use Cherry\Core\ServiceInstance;

// We need to call on this to make sure the service manager is registered
SvcMan::register();

/**
 * TestService: This service does nothing but print dots until it is stopped.
 */
class TestService extends ServiceInstance {
    public $serviceid = "info.noccylabs.testservice";
    public function __construct() {
        $this->flags = ServiceInstance::SVC_RESTART + ServiceInstance::SVC_NO_DELAY;
    }
    function servicemain() {
        for($s = 0; $s < 5; $s++) {
            usleep(100000);
            echo ".";
        }
    }
    function onShutdown() {
        echo "X\n";
    }
}
SvcMan::addServiceInstance(new TestService());

class TestClass {
    public function __construct($name) {
        $this->name = ucwords($name);
    }
    public function hello() {
        echo "Hello {$this->name}!\n";
    }
}

class HelloFactory implements IObjectManagerInterface {
    public function omiGetNodeList($path) {
        return ["*"];
    }
    public function omiGetObject($path) {
        return new TestClass($path->name);
    }
}

$foo = new TestClass("World");

// objects can be registered directly at a path, in which case the path must
// not end with a slash.
ObjMan::registerObject("local:/foo/bar/baz", $foo);
// They can also be registered via proxy classes implementing IObjectManagerInterface.
// Classes registered like this mount into the uri space.
ObjMan::registerObjectRoot("local:/hello/", new HelloFactory());

$svc = ObjMan::getObject("local:/services/info.noccylabs.testservice#0");
$svc->start();

// So, we can grab our injected object
$foo2 = ObjMan::getObject("local:/foo/bar/baz");
$foo2->hello();

// Or we can query it from the factory
$foo3 = ObjMan::getObject("local:/hello/universe");
$foo3->hello();

sleep(5);
$svc->stop();

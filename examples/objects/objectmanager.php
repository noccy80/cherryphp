<?php

require_once __DIR__."/../../share/include/cherryphp";

use Cherry\Core\ObjectManager as ObjMan;
use Cherry\Core\IObjectManagerInterface;
use Cherry\Core\ServiceManager as SvcMan;
use Cherry\Core\ServiceInstance;

class TestClass {
    public function __construct($name) {
        $this->name = ucwords($name);
    }
    public function hello() {
        echo "Hello {$this->name}!\n";
    }
}

class HelloFactory implements IObjectManagerInterface {
    public function omiGetObjectList($path) {
        return ["*"];
    }
    public function omiGetObjectProperties($path) { 
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

// So, we can grab our injected object
$foo2 = ObjMan::getObject("local:/foo/bar/baz");
$foo2->hello();

// Or we can query it from the factory
$foo3 = ObjMan::getObject("local:/hello/universe");
$foo3->hello();


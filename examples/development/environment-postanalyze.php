#!/usr/bin/php
<?php

require_once "../../share/include/cherryphp";

// Register the Cherry RunTime Extensions Application object
Cherry\Rte\Application::register([ "postinspect"=>true ]);

class Foo {

    use \Cherry\Traits\TSingletonAccess;
    use \Cherry\Traits\TImmutableProperties;

    function __construct() {
        $this->addProperty('test','defaultvalue','string',false);
        $this->addProperty('rotest','defaultvalue','string',true);
        echo "Constructed!\n";
    }
}

$foo = Foo::getInstance();
try {
    $foo->test = 'yo';
    echo "Set foo->test\n";
} catch (Exception $e) { echo $e->getMessage()."\n"; }
try {
    $foo->test2 = 'hey';
    echo "Set foo->test2\n";
} catch (Exception $e) { echo $e->getMessage()."\n"; }
try {
    $foo->rotest = 'meow';
    echo "Set foo->meow\n";
} catch (Exception $e) { echo $e->getMessage()."\n"; }

#!/usr/bin/php
<?php

require getenv("CHERRY_LIB")."/lib/bootstrap.php";

use Cherry\Crypto\KeyStore;

class foo {
    function bar() {
        return $this->baz();
    }
    function baz() {
        $key = KeyStore::getInstance()->queryCredentials("foo");
        return $key;
    }
}

class bar {
    function test() {
        $key = KeyStore::getInstance()->queryCredentials("foo");
        return $key;
    }
}

// Add the credentials 
KeyStore::getInstance()->addCredentials("foo","secretkey",[ 'foo::*' ]);

// Foo can access the value
echo "Access test from allowed code path foo::bar->foo::baz (matches foo::*)\n";
$f = new foo();
try {
    echo "  Secret is: ".$f->bar()."\n";
} catch (Exception $e) {
    echo "  Access denied\n";
}

// Fork can't
echo "Access test from invalid code path bar::test\n";
$f = new bar();
try {
    echo "  Secret is: ".$f->test()."\n";
} catch (Exception $e) {
    echo "  Access denied\n";
}



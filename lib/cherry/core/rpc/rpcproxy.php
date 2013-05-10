<?php

namespace Cherry\Core\Rpc;

use \Cherry\Data\Ddl\DocComment;

class RpcProxy {
    
    public function setup($definition) {
        echo "Setting object up:\n";
        print_r($definition);
    }
    
    public function connect($target) {
        echo "Connecting object proxy to {$target}\n";
    }
    
    public function __call($name, array $args) {
        echo "Calling function {$name} of proxied object\n";
    }
    
}

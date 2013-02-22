<?php

require_once "xenon/xenon.php";
xenon\frameworks\cherryphp::bootstrap(__DIR__);

class TestClass {

    protected $a;
    protected $b;

    public function __construct($a,$b) {
        $this->a = $a;
        $this->b = $b;
    }

    public function calc(callable $closure) {
        return call_user_func(bind($this,$closure),$this->a,$this->b);
    }

}

$obj = new TestClass(4,3);
echo "4 + 3 = ".$obj->calc(function($a,$b){ return $a+$b; })."\n";

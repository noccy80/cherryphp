<?php

require_once __DIR__."/../../share/include/cherryphp";

use Cherry\Core\ServiceInstance;

class TestService extends ServiceInstance {
    use \Cherry\Proc\TCriticalSection;
    public $serviceid = "info.noccylabs.testservice2";
    private $char;
    public function __construct($char) {
        $this->char = $char;
        $this->flags = ServiceInstance::SVC_RESTART + ServiceInstance::SVC_NO_DELAY;
    }
    function servicemain() {
        for($s = 0; $s < 5; $s++) {
            usleep(100000);
            $key = fileinode(__FILE__);
            $this->enterCriticalSection($key);
            echo $this->char;
            $this->leaveCriticalSection();
        }
    }
    function servicehalt() {
        echo "X\n";
    }
}

$s1 = new TestService(".");
$s2 = new TestService(":");
$s1->start();
$s2->start();
sleep(5);
$s1->stop();
$s2->stop();

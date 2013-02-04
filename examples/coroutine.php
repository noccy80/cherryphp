<?php

define("XENON","cherryphp/trunk");
require_once("xenon/xenon.php");

use Cherry\Expm\Process\CoRoutine;

// Define your coroutines as anything callable, be it a lambda function, a static
// method call, or [object,method]:
$cr = new CoRoutine(function(){
    for($n = 0; $n < 32; $n++) {
        $this->write($n.",");
        usleep(100000);
    }
});
// You can define it externally as well...
$func2 = function(){
    for($n = 50; $n < 70; $n++) {
        $this->write($n.",");
        usleep(50000);
    }
};
// ...and just reference to it...
$cr2 = new CoRoutine($func2);

// Using a class as a coroutine is more awesome!
class MyCoroutine extends CoRoutine {
    public function main() {
        for($n = 0; $n < 50; $n++) {
            $this->doTheCount();
        }
    }
    public function doTheCount() {
        static $n = 0;
        $n++;
        $this->write($n.",");
        usleep(50000);
    }
}
$cr3 = new MyCoroutine();

// Start the threads
$cr->start();
$cr2->start();
$cr3->start();

// The destructors will take care of the waiting and reaping of the child
// processes.

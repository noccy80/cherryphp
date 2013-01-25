<?php

define("XENON", "cherryphp/trunk");
define("XENON_REPOSITORY", "http://noccylabs.info/cherryphp/repository.json");
require("xenon/xenon.php");

use Cherry\Cli\ConsoleApplication;
use Cherry\Web\HtmlTag as h;
use Cherry\Web\HtmlDocument;

$w_init = [ 1, 2, 3 ];
$w_curr = [];

function init() {
    global $w_curr, $w_init;
    print "[ {$w_init[0]} | {$w_init[1]} | {$w_init[2]} ] ";
    $w_curr = $w_init;
}
function turn_first() {
    global $w_curr;
    echo "F";
    $w_curr[0] = ($w_curr[0] % 3) + 1;
    $w_curr[1] = ($w_curr[1] % 3) + 1;
}
function turn_last() {
    global $w_curr;
    echo "L";
    $w_curr[1] = ($w_curr[1] % 3) + 1;
    $w_curr[2] = ($w_curr[2] % 3) + 1;
}
function check() {
    global $w_curr;
    print " -> [ {$w_curr[0]} | {$w_curr[1]} | {$w_curr[2]} ]\n";
    if ($w_curr == [ 2, 2, 1 ]) {
        echo "Found it!"; 
        die();
    }
}
for($rr = 1; $rr < 16; $rr++) {
    for ($n = 0; $n < 10000; $n++) {
        init();
        for ($m = 0; $m < $rr; $m++) {
            $bit = (($n & (1<<$m))!=0);
            if ($bit) {
                turn_first();
            } else {
                turn_last();
            }
        }
        check();
    }
}

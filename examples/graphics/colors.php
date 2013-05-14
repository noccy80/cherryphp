<?php

require_once "cherryphp";

use Cherry\Graphics\Color;

$tests = [
    [0,0,0],
    [255,255,255],
    [255,128,0],
    [128,255,0],
    [255,0,255],
    [128,0,255],
    [128,128,255]
];

foreach($tests as $cv) {
    $c = new Color($cv[0],$cv[1],$cv[2]);
    list($r,$g,$b) = $c->toRGB();
    list($h,$s,$v) = $c->toHSV();

    printf("rgb(%d,%d,%d) = hsv(%d,%d,%d)\n", $r,$g,$b,$h,$s,$v);
}

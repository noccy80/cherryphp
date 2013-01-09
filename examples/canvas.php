<?php

//LOADER:BEGIN
if (!( @include_once "lib/bootstrap.php" )) {
    $libpath = getenv('CHERRY_LIB');
    if (!$libpath) {
        fprintf(STDERR,"Define the CHERRY_LIB envvar first.");
        exit(1);
    }
    require_once($libpath.'/lib/bootstrap.php');
}
//LOADER:END

use Cherry\Graphics\Canvas;
use Cherry\Graphics\Font\TrueTypeFont;
use Cherry\Graphics\OrderedDither;

// Create a canvas of 640x480
$c = new Canvas();
$c->create(640,480);

// Use ordered dither with a 4x4 matrix
$c->setDitherClass(
    new OrderedDither(OrderedDither::$mthreshold4x4)
);

// Draw a colorful pattern
for($x = 0; $x < 640; $x++) {
    for($y = 0; $y < 480; $y++) {
        // Calculate the color for the pixel
        $r = ($x / 640) * 255;
        $g = 255 - ($x / 640) * 255;
        $b = ($y / 480) * 255;
        // Set the pixel with the red, green and blue values we calculated
        $c->setPixel($x, $y, [$r,$g,$b]);
    }
}

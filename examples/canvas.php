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
use Cherry\Cli\Glyph as g;

define('WIDTH',160);
define('HEIGHT',120);

// Create a canvas of 640x480
$c = new Canvas();
$c->create(WIDTH,HEIGHT);

// Use ordered dither with a 4x4 matrix
$c->setDitherClass(
    new OrderedDither(OrderedDither::$mthreshold4x4)
);

// Draw a colorful pattern
for($x = 0; $x < WIDTH; $x++) {
    //echo "\x08. ";
    echo "\rDrawing row {$x}  ";
    for($y = 0; $y < HEIGHT; $y++) {
        echo g::work("clock");
        // Calculate the color for the pixel
        $r = ($x / WIDTH) * 255;
        $g = 255 - ($x / WIDTH) * 255;
        $b = ($y / HEIGHT) * 255;
        // Set the pixel with the red, green and blue values we calculated
        $c->setPixel($x, $y, [$r,$g,$b]);
    }
}

$c->save("canvas.jpg");

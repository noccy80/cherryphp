<?php

use Cherry\Graphics\Canvas;

class CanvasTest extends \PHPUnit_Framework_TestCase {

    public function testCreateNewCanvas() {

        $canvas = new Canvas(640,480);
        $this->assertEquals(640,$canvas->width,"Canvas width is not 640");
        $this->assertEquals(480,$canvas->height,"Canvas height is not 480");


    }

}

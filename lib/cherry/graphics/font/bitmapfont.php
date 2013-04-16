<?php

namespace Cherry\Graphics\Font;

use \Cherry\Graphics\Canvas;

class BitmapFont extends Font {

    private $font = 0;

    public function __construct($font) {
        $this->font = $font;
    }

    public function measure($text) {
        
    }
    
    public function drawText(Canvas $image, $x, $y, $text, $color) {
        \imagestring($image->himage,$this->font,$x,$y,$text, $image->map($color));
    }

    public function drawTextUp(Canvas $image, $x, $y, $text, $color) {
        \imagestringup($image->himage,$this->font,$x,$y,$text, $image->map($color));
    }

}

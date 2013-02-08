<?php

namespace Cherry\Graphics\Font;

use \Cherry\Graphics\Canvas;

abstract class Font {

    abstract function drawText(Canvas $image, $x, $y, $text, $color);
    abstract function measure($text);

}

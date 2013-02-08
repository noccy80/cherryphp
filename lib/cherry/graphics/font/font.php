<?php

namespace Cherry\Graphics;

abstract class Font {

    abstract function drawText(Canvas $image, $x, $y, $text);
    abstract function measure($text);

}

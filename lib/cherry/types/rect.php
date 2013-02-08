<?php

namespace Cherry\Types;

class Rect {
    public
        $x, $y, $w, $h; ///< Coordinates of the rect
    /**
     * @brief Create a rect
     */
    public function __construct($x=0,$y=0,$w=0,$h=0) {
        $this->x = $x;
        $this->y = $y;
        $this->w = $w;
        $this->h = $h;
    }
    /**
     * @brief Helper function
     */
    public static function Rect($x=0,$y=0,$w=0,$h=0) {
        return new Rect($x,$y,$w,$h);
    }

    public function move($x,$y) {
        $this->x+= $x;
        $this->y+= $y;
    }

    public function resizeTo($w,$h) {
        $this->w = $w;
        $this->h = $h;
    }

    public function moveTo($x,$y) {
        $this->x = $x;
        $this->y = $y;
    }

    public function isIn($x,$y) {
        return (($x >= $this->x) && ($x <= $this->x + $this->w) &&
            ($y >= $this->y) && ($y <= $this->y + $this->h));
    }

}

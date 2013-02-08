<?php

namespace Cherry\Types;

class Point {
    public
        $x, $y; ///< Coordinates of the point
    /**
     * @brief Create a point
     */
    public function __construct($x=0,$y=0) {
        $this->x = $x;
        $this->y = $y;
    }
    /**
     * @brief Helper function
     */
    public static function point($x=0,$y=0) {
        return new point($x,$y);
    }

    public function move($x,$y) {
        $this->x+= $x;
        $this->y+= $y;
    }
    
    public function getDistance(Point $point) {
        return sqrt(
            (
                (pow(abs($this->x - $point->x),2))
                +
                (pow(abs($this->y - $point->y),2))
            )
        );
    }

}

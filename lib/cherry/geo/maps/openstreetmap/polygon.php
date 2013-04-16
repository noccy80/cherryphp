<?php

namespace Cherry\Geo\Maps\OpenStreetmap;

use \Cherry\Geo\GeoPosition;

class Polygon {
    private $color;
    private $points = [];
    public function __construct($color) {
        $this->color = $color;
    }
    public function addPoint(GeoPosition $position) {
        $this->points[] = $position;
    }
    public function getData($index) {
        $data = [
            "d{$index}_style" => "polygon",
            "d{$index}_color" => $this->color
        ];
        $numpoints = count($this->points);
        for ($point = 0; $point < $numpoints; $point++) {
            $p = $this->points[$point];
            $data["d{$index}p{$point}lat"] = $p->lat;
            $data["d{$index}p{$point}lon"] = $p->lon;
        }
    }
}

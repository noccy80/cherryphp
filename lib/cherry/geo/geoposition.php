<?php

namespace Cherry\Geo;

class GeoPosition {
    private $lat;
    private $lon;
    public function __construct($lat,$lon) {
        $this->lat = $lat;
        $this->lon = $lon;
    }
    public function __get($key) {
        switch($key) {
            case "lat":
                return $this->lat;
            case "lon":
                return $this->lon;
            default:
                throw new \UnexpectedValueException("no property {$key} in class GeoPosition");
        }
    }
    public function __set($key,$value) {
        switch($key) {
            case "lat":
                $this->lat = (float)$value;
                break;
            case "lon":
                $this->lon = (float)$value;
                break;
            default:
                throw new \UnexpectedValueException("no property {$key} in class GeoPosition");
        }
    }
}

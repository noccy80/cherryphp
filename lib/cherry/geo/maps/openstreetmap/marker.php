<?php

namespace Cherry\Geo\Maps\OpenStreetmap;

use \Cherry\Geo\GeoPosition;

class Marker {
    private $geo;
    private $icon;
    public function __construct(GeoPosition $position, $icon) {
        $this->geo = $position;
        $this->icon = $icon;
    }
    public function getData($index) {
        return [
            "mlat{$index}" => $this->geo->lat,
            "mlon{$index}" => $this->geo->lon,
            "mico{$index}" => $this->icon
        ];
    }
}

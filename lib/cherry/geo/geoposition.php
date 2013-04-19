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
    public function getDistance(GeoPosition $other) {
        return self::vincentyGreatCircleDistance($this->lat,$this->lon,$other->lat,$other->lon);
    }
    private function calcDecdegToDms($deg,$dir="lat") {
        $od = floor($deg);
        $deg = ($deg - $od) * 60;
        $om = floor($deg);
        $deg = ($deg - $om) * 60;
        $os = floor($deg);
        $dirs = (
            ($dir=="lat")?
                (($deg>=0)?"N":"S"):
                (($deg>=0)?"E":"W")
        );
        $od = abs($od);
        return [ $od, $om, $os, $dirs ];
    }
    public function toString() {
        return sprintf("%.6f, %.6f", $this->lat, $this->lon);
    }
    public function toStringDms() {
        list($latd,$latm,$lats,$latz) = $this->calcDecdegToDms($this->lat,"lat");
        list($lond,$lonm,$lons,$lonz) = $this->calcDecdegToDms($this->lon,"lon");
        return html_entity_decode(sprintf("%03d&deg;%d'%d\"%s %03d&deg;%d'%d\"%s", $latd,$latm,$lats,$latz,$lond,$lonm,$lons,$lonz),null,"utf-8");
    }

    /**
     * Calculates the great-circle distance between two points, with
     * the Vincenty formula.
     * @author martinstoeckli (stackoverflow)
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param float $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    public static function vincentyGreatCircleDistance(
        $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
        pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        return $angle * $earthRadius;
    }

    public static function fromDMS(array $lat, array $lon) {
        list($latd,$latm,$lats,$latz) = $lat;
        list($lond,$lonm,$lons,$lonz) = $lon;
        $latsec = ($latm * 60 + $lats) / 3600;
        $latms = ($latd + $latsec)*(strtolower($latz)=="n"?1:-1);
        $lonsec = ($lonm * 60 + $lons) / 3600;
        $lonms = ($lond + $lonsec)*(strtolower($lonz)=="w"?1:-1);
        return new GeoPosition($latms,$lonms);
    }
}

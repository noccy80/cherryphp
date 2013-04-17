<?php

namespace Cherry\Geo\Maps\OpenStreetmap;
use \Cherry\Geo\GeoPosition;

class Map {

    const LAYER_MAPNIK = "mapnik";
    const LAYER_OSMARENDER = "osmarender";
    const LAYER_CYCLE = "cycle";
    const LAYER_SKIING = "skiing";
    const LAYER_HIKING = "hiking";
    const LAYER_MAPLINT = "maplint";
    
    const FILTER_NONE = "none";
    const FILTER_GREY = "grey";
    const FILTER_LIGHTGREY = "lightgrey";
    const FILTER_DARKGREY = "darkgrey";
    const FILTER_INVERT = "invert";
    const FILTER_BRIGHT = "bright";
    const FILTER_DARK = "dark";
    const FILTER_VERYDARK = "verydark";

    const ATT_TEXT = "text";
    const ATT_LOGO = "logo";
    const ATT_NONE = "none";

    private $geo;
    private $zoom;
    private $layer;
    private $att;
    private $filter;
    private $markers = [];
    private $polygons = [];

    public function __construct(GeoPosition $pos, $zoom=12, $layer=Map::LAYER_MAPNIK) {
        $this->geo = $pos;
        $this->zoom = $zoom;
        $this->layer = $layer;
    }
    
    public function setAttribution($att) {
        $this->att = $att;
    }
    public function getAttribution() {
        return $this->att;
    }
    
    public function setLayer($layer) {
        $this->layer = $layer;
    }
    public function getLayer() {
        return $this->layer;
    }
    
    public function setFilter($filter) {
        $this->filter = $filter;
    }
    public function getFilter() {
        return $this->filter;
    }
    
    private function getApiQueryUrl(array $opts) {
        $base = "http://ojw.dev.openstreetmap.org/StaticMap/";
        $param = [
            "show"  => 1,
            "lat"   => $this->geo->lat,
            "lon"   => $this->geo->lon,
            "z"     => $this->zoom,
            "layer" => $this->layer,
            "fmt"   => "png",
            "filter" => $this->filter?:self::FILTER_NONE,
            "att"   => $this->att?:self::ATT_TEXT
        ];
        $param = array_merge($param,$opts);
        $query = http_build_query($param);
        return $base."?".$query;
    }

    public function saveMap($width,$height,$filename) {
        $url = $this->getApiQueryUrl([
            "w" => $width,
            "h" => $height
        ]);
        $map = file_get_contents($url);
        if ($map) {
            file_put_contents($filename,$map);
        } else {
            echo "Error: No map data returned!\n";
        }
    }
    
    public function getMapCanvas($width,$height) {
        $url = $this->getApiQueryUrl([
            "w" => $width,
            "h" => $height
        ]);
        $map = file_get_contents($url);
        $c = Canvas::createFromString($map);
        return $c;
    }

    public function getMapUrl($width,$height) {
        $url = $this->getApiQueryUrl([
            "w" => $width,
            "h" => $height
        ]);
        return $url;
    }

}

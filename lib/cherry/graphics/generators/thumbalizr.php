<?php

namespace Cherry\Graphics\Generators;

use \Cherry\Graphics\Canvas;
/*
 * class Thumbalizer
 */

class Thumbalizr extends Canvas {
    function __construct($url, $width) {
        if (!$url)
            throw new \UnexpectedValueException("Thumbalizr: Invalid URL");
        if (strpos($url,"://")===false) {
            $url = "http://".$url;
        }
        $urlenc = urlencode($url);
        $apiurl = "http://api.thumbalizr.com/?url={$url}&width={$width}";
        \debug("Thumbalizr: %s","Querying {$apiurl}");
        $img = fopen($apiurl,"rb");
        parent::__construct();
        $this->loadString(stream_get_contents($img));
    }
}

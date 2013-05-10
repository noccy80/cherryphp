<?php

require_once "cherryphp";

use Cherry\Geo\GeoPosition;

/*
$pos = new GeoPosition(-23.399437,-52.090904);

echo "Degrees:  ".$pos->toString()."\n";
echo "DMS:      ".$pos->toStringDms()."\n";
*/

/*
Map Coordinates of Selected Location
Latitude:N 59° 23' 41.1801"
Longitude:E 13° 30' 7.9102"
Latitude:N 59° 23.686335'
Longitude:E 13° 30.131836'
Latitude:59.394772°
Longitude:13.502197°
*/
$ksd = GeoPosition::fromDMS([59,23,41.1801,"N"], [13,30,7.9102,"E"]);

/*
Map Coordinates of Selected Location
Latitude:N 59° 22' 30.6834"
Longitude:E 18° 1' 3.2813"
Latitude:N 59° 22.51139'
Longitude:E 18° 1.054688'
Latitude:59.37519°
Longitude:
*/
$sth = GeoPosition::fromDMS([59,22,30.6834,"N"], [18,1,3.2813,"E"]);

echo "Karlstad:  ".$ksd->toString()." (".$ksd->toStringDms().")\n";
echo "Stockholm: ".$sth->toString()." (".$sth->toStringDms().")\n";
echo "Distance:  ".round($ksd->getDistance($sth)/1000)."KM\n";

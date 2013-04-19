<?php

require_once "cherryphp";

use Cherry\Geo\GeoPosition;

$pos = new GeoPosition(-23.399437,-52.090904);

echo "Degrees:  ".$pos->toString()."\n";
echo "DMS:      ".$pos->toStringDms()."\n";

$ksd = GeoPosition::fromDMS([59,22,45,"N"], [18,30,12,"E"]);
$sth = GeoPosition::fromDMS([59,19,57,"N"], [18,3,53,"E"]);

echo "Karlstad:  ".$ksd->toString()." (".$ksd->toStringDms().")\n";
echo "Stockholm: ".$sth->toString()." (".$sth->toStringDms().")\n";
echo "Distance:  ".round($ksd->getDistance($sth)/1000)."KM\n";

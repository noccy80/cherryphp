<?php

//LOADER:BEGIN
require_once "xenon/xenon.php";
Xenon\Frameworks\CherryPhp::bootstrap(__DIR__);
//LOADER:END

use Cherry\Crypto\Uuid;
use Cherry\Crypto\UuidGenerator;

// What implementation are we using?
$impl = Uuid::getBackend();
echo "Implementation: {$impl}\n";

// Generating via UuidGenerator facade.
$uuid = UuidGenerator::uuid();
echo "Generated UUID: {$uuid}\n";
// Check if the UUID is valid
$valid = UuidGenerator::valid($uuid);
$validstr = ($valid)?'Yes':'No';
echo "UUID is valid: {$validstr}\n";

// Generate a V3 domain-based url
$v3uuid = UuidGenerator::v3("http:///google.com");
if ($v3uuid) {
    // Not all implementations support V3 uuids.
    echo "V3 UUID for http://google.com: {$v3uuid}\n";
}

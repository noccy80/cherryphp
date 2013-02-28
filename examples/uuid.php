<?php

//LOADER:BEGIN
if (!( @include_once "lib/bootstrap.php" )) {
    $libpath = getenv('CHERRY_LIB');
    if (!$libpath) {
        fprintf(STDERR,"Define the CHERRY_LIB envvar first.");
        exit(1);
    }
    require_once($libpath.'/lib/bootstrap.php');
}
//LOADER:END

use Cherry\Crypto\Uuid;
use Cherry\Crypto\UuidGenerator;

$uuid = Uuid::getInstance();
echo "V1 UUID: ".$uuid->generate(Uuid::UUID_V1)."\n";
echo "V3 UUID: ".$uuid->generate(Uuid::UUID_V3,"http://google.com")."\n";
echo "V4 UUID: ".$uuid->generate(Uuid::UUID_V4)."\n";
echo "V5 UUID: ".$uuid->generate(Uuid::UUID_V5)."\n";
echo "Implementation: ".$uuid->getImplementationName()."\n";

echo "Shorthand generation V4: ".UuidGenerator::v4()."\n";
echo "Shorthand validation: ".((UuidGenerator::valid(UuidGenerator::uuid()))?'True':'False')."\n";

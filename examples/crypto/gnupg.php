#!/usr/bin/php
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

// Example. GnuPG

$kr = new \Cherry\Crypto\GnuPG\KeyRing();
$keys = $kr->getKeys();

foreach($keys as $key) {
    printf("%s (%s)\n", $key->uids[0]->name, $key->uids[0]->email);
}

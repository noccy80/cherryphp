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

use Cherry\Base\OpaqueToken;
use Cherry\Crypto\Keystore;

KeyStore::getInstance()->addCredentials("opaquetoken.key", "f02nfoDer2##fC;.Rfk", [ "Cherry\\Base\\OpaqueToken::*" ]);
$tok = new OpaqueToken();
$tok['session_id'] = 1234;
$tok['username'] = "Bob";
echo "Original token:\n";
echo "  session: {$tok['session_id']}\n";
echo "  username: {$tok['username']}\n";
echo "Frozen token:\n  " , $tok->freeze() , "\n";
$tok2 = new OpaqueToken();
$tok2->unfreeze($tok->freeze());
echo "Unfrozen token:\n";
echo "  session: {$tok2['session_id']}\n";
echo "  username: {$tok2['username']}\n";
#!/usr/bin/php
<?php

define("XENON","cherryphp/trunk");
require("xenon/xenon.php");

use Cherry\Cli\ConsoleApplication;
use Cherry\Base\OpaqueToken;
use Cherry\Crypto\Keystore;

class OpaqueTokenExample extends ConsoleApplication {
    public function main() {
        // Add the key to use for encrypting the token to the keystore, this
        // should be done during setup.
        KeyStore::getInstance()->addCredentials("opaquetoken.key", "f02nfoDer2##fC;.Rfk", [ "Cherry\\Base\\OpaqueToken::*" ]);
        // Create a new opaque token
        $tok = new OpaqueToken();
        // Stuff some data into the token
        $tok['session_id'] = 1234;
        $tok['username'] = "Bob";
        $tok['blob'] = [
            'page.from' => '/article/123',
            'page.referer' => 'http://site.com'
        ];
        // Print out what we got
        echo "Original token:\n";
        echo var_inspect($tok,false,false)."\n\n";

        // Freeze the token and display it
        $ftok = $tok->freeze();
        echo "Frozen token:\n";
        echo var_inspect($ftok,false,true)."\n\n";
        //echo "  length: ".strlen($ftok)."\n";

        // To unfreeze the token
        $tok2 = new OpaqueToken($ftok);
        echo "Unfrozen token:\n";
        echo var_inspect($tok2,false,true)."\n\n";
   }
}

App::run(new OpaqueTokenExample());

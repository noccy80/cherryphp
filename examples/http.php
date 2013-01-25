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

use Cherry\Net\Http\Client\StreamClient;
use Cherry\Net\Http\Client\CurlClient;
use Cherry\Net\Http\HttpRequest;

// This is just for reference, and if you want to hack the code.
// Here we create a new StreamClient, initializing it 

$sc = new StreamClient();
$sc->setUrl('http://127.0.0.1');
$ret = $sc->execute();
if ($ret !== false) {
    var_dump($sc->getAllHeaders());
    //echo $sc->getResponse();
    var_dump($sc->getTimings());
} else {
    echo "Request failed.\n";
}
exit;
            
// This is the ideal way, inspired by XmlHttpRequest

$hr = new HttpRequest();
$hr->open('GET','http://127.0.0.1:8090');
$hr->setHeader('User-Agent','Mozilla/5.0 (Windows NT 6.2; Win64; x64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1');
$hr->setHeader('x-foo-bar','Hello world');
$hr->on('httprequest:before', function() { echo "Before request!\n"; } );
$hr->on('httprequest:success', function() { echo "Successful request!\n"; } );
$hr->on('httprequest:error', function() { echo "Error Error!\n"; } );
$hr->send() or die("Request failed\n");

// Or the shorthand

$req = new HttpRequest('http://127.0.0.1:8080');
$req->send();
echo $req->getResponseText();

// Or wrapped

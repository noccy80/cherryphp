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

// Example: Crypto
$str = 'Hello World';
$key = 'FooBar';

$enc = \Cherry\Crypto\Algorithm::tripledes($key)->encrypt($str);
$dec = \Cherry\Crypto\Algorithm::tripledes($key)->decrypt($enc);

$encs = \Cherry\Cli\CliUtils::printable($enc);
echo "{$str} -> {$encs} -> {$dec}\n";

// Example: Crypto (#2)
$str = 'Hello World';
$key = 'FooBar';

$ca = new \Cherry\Crypto\Algorithm('tripledes',$key);
$enc = $ca->encrypt($str);


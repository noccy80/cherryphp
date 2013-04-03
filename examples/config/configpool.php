<?php

require_once "xenon/xenon.php";
xenon\frameworks\cherryphp::bootstrap(__DIR__);

use Cherry\Core\ConfigPool as Config;

// Bind the pool. If you bind another identifier pointing to the same file it
// will be shared between the identifiers.
Config::bindPool("configpool.sdl","test");

// To demonstrate, let us rebind it as "cyanogenmod":
Config::bindPool("configpool.sdl","cyanogenmod");

// Next, retrieve the pool. This will get the root SdlTag for the configuration
// file, so you can go on and query it. Remember that spath always returns an 
// array, so grab the first item.
$pool = Config::getPool("test");
echo "Version 9 is:\n".$pool->query("//cyanogenmod/version[9]")[0]->encode()."\n";

// Let's try that on the cyanogenmod pool:
$pool = Config::getPool("cyanogenmod");
echo "Version 9 is:\n".$pool->query("//cyanogenmod/version[9]")[0]->encode()."\n";


<?php

//LOADER:BEGIN
require_once "xenon/xenon.php";
Xenon\Frameworks\CherryPhp::bootstrap(__DIR__);
//LOADER:END

use Cherry\Cli\ConIo;
use Cherry\Cli\ReadlineCompleter;
use Cherry\Cli\SqlReadlineCompleter;

ConIo::write("Hello stranger! What is your name?\n");
$name = ConIo::readLine("Name: ", new ReadlineCompleter(), "ucwords");
ConIo::write("Awesome, {$name}. Are you ready to play?\n");

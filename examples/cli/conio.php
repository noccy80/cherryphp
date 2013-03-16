<?php

require_once __DIR__."/../../share/include/cherryphp";

use Cherry\Cli\ConIo;
use Cherry\Cli\ReadlineCompleter;
use Cherry\Cli\SqlReadlineCompleter;

ConIo::write("Hello stranger! What is your name?\n");
$name = ConIo::readLine("Name: ", new ReadlineCompleter(), "ucwords");
ConIo::write("Awesome, {$name}. Are you ready to play?\n");

<?php

require_once "xenon/xenon.php";
xenon\frameworks\cherryphp::bootstrap(__DIR__);

use Cherry\Expm\Cli\ArgumentParser;
use Cherry\Expm\Cli\BooleanOption;
use Cherry\Expm\Cli\ValueOption;
use Cherry\Expm\Cli\ListOption;

$ap = new ArgumentParser();
$ap->addOption('help', new BooleanOption([ 'h', 'help' ]), "Show the help");
$ap->addOption('name', new ValueOption([ 'n', 'name' ]), 'Set your name');
list($args,$parms) = $ap->parse();
if ($args->help) {
    echo "Try with --name 'yourname'\n";
} else {
    if (!$args->name) {
        echo "You still didn't give me a name!\n";
    } else {
        echo "Hello {$args->name}.\n";
    }
}


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

use Cherry\Cli\Ansi;

$ca = \Cherry\Cli\Console::getAdapter();

$ca->write(
    Ansi::pushColor(\Ansi\Color::RED,\Ansi\Color::YELLOW).
    "This should be red on yellow and span the entire line".
    Ansi::clearToEnd().
    Ansi::popColor().
    "\n".
    "And this should be normal text again.\n".
    "There is also ".Ansi::setBold()."bold".Ansi::clearBold().", ".
    Ansi::setUnderline()."underline".Ansi::clearUnderline()." and ".
    Ansi::setReverse()."reverse".Ansi::clearReverse()." text available.\n".
    "And take a look at these color bars, pure 256-color ANSI.\n"
);

for($m = 0; $m < 32; $m++) {
$ca->write(
    \Ansi\Color::color256(null,($m*8).','.($m*8).','.($m*8)).
    "  "
);
}
$ca->write(Ansi::reset()."\n");
for($m = 0; $m < 32; $m++) {
$ca->write(
    \Ansi\Color::color256(null,'0,'.($m*8).',0').
    "  "
);
}
$ca->write(Ansi::reset()."\n");
for($m = 0; $m < 32; $m++) {
$ca->write(
    \Ansi\Color::color256(null,('0,0,'.($m*8))).
    "  "
);
}
$ca->write(Ansi::reset()."\n");
for($m = 0; $m < 32; $m++) {
$ca->write(
    \Ansi\Color::color256(null,($m*8).',0,0').
    "  "
);
}
$ca->write(Ansi::reset()."\n");
$ca->write(Ansi::color("This is a line in green.\n", "green"));
$ca->write(Ansi::color("And a line in red.\n", "red"));

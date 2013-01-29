<?php

/**
 * Xenon Bootstrap Script
 *
 *
 */

// Check prerequisites
if (!defined("XENON")) {
    die("Error: XENON not defined.");
} else {
    $_xenoninit = explode(";",XENON);
    if (strpos($_xenoninit[0],"/") === false) {
        die("Error: Framework version not specified.");
    }
    list($_xenonfw,$_xenonfwver) = explode("/",$_xenoninit[0]);
    define("XENON_DEBUG", (getenv("DEBUG")==1));
    define("XENON_PATH", __DIR__);
    if (!defined("XENON_ROOT")) {
        if (($e = getenv("XENON_ROOT")))
            define("XENON_ROOT",$e);
        else
            define("XENON_ROOT",getcwd());
    }
    define("XENON_FW", $_xenonfw);
    define("XENON_FW_VERSION", $_xenonfwver);
    if (XENON_DEBUG) echo "xenon: loading {$_xenonfw}...\n";
    require XENON_PATH . "/frameworks/" . XENON_FW . ".php";
}

<?php

/**
 * Xenon Bootstrap Script
 *
 *
 */


require_once(__DIR__.'/modules/xenon.php');


define("XENON_DEBUG", (getenv("DEBUG")==1));

define("XENON_PATH", __DIR__);

if (!defined("XENON_ROOT")) {
    if (($e = getenv("XENON_ROOT")))
        define("XENON_ROOT",$e);
    else
        define("XENON_ROOT",realpath(__DIR__));
}

if (defined("XENON")) {
    $_xenoninit = explode(";",XENON);
    if (strpos($_xenoninit[0],"/") !== false) {
        list($_xenonfw,$_xenonfwver) = explode("/",$_xenoninit[0]);
    } else {
        $_xenonfw = $_xenoninit[0];
        $_xenonfwver = "*";
    }
    define("XENON_FW", $_xenonfw);
    define("XENON_FW_VERSION", $_xenonfwver);
    xenon\xenon::framework($_xenonfw,$_xenonfwver);
}

spl_autoload_register(function($class) {
    $match = 'xenon\frameworks';
    $class = strtolower($class);
    if (substr($class,0,strlen($match)) == $match) {
        require_once XENON_ROOT.str_replace("\\","/",substr($class,5)).'.php';
        return true;
    }
});
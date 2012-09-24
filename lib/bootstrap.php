<?php

namespace cherry;

define('_DS_',DIRECTORY_SEPARATOR);

define('CHERRY_VERSION','1.0.0-alpha');
if ($_app = getenv("CHERRY_APP")) define('CHERRY_APP',$_app);
if ($_lib = getenv("CHERRY_LIB")) define('CHERRY_LIB',$_lib);

// Fallbacks
if (defined('CHERRY_APP'))
    define('CHERRY_APPROOT',CHERRY_APP);
// Fix paths
if (!defined('CHERRY_LIB'))
    define('CHERRY_LIB',dirname(dirname(__FILE__)));
if (!defined('CHERRY_APPROOT'))
    define('CHERRY_APPROOT',getcwd());

if (file_exists(CHERRY_APPROOT._DS_.'application')) {
    define('CHERRY_APPDIR',CHERRY_APPROOT._DS_.'application');
} else {
    define('CHERRY_APPDIR',CHERRY_APPROOT);
}
if (file_exists(CHERRY_APPDIR._DS_.'extensions')) {
    define('CHERRY_EXTDIR',CHERRY_APPDIR._DS_.'extensions');
} else {
    define('CHERRY_EXTDIR',CHERRY_APPROOT._DS_.'extensions');
}
if (file_exists(CHERRY_APPROOT.'/application.ini')) {
    define('CHERRY_CFGDIR',CHERRY_APPROOT);
} else {
    define('CHERRY_CFGDIR',CHERRY_APPDIR.'/config');
}

$_os = strtolower(PHP_OS);
define('_IS_LINUX', ($_os=='linux'));
define('_IS_WINDOWS',(substr($_os,0,3)=='win'));
define('_IS_MACOS',($_os=='darwin'));

require_once CHERRY_LIB.'/lib/cherry/base/autoloader.php';
require_once CHERRY_LIB.'/lib/cherry/base/debug.php';
require_once CHERRY_LIB.'/lib/bundles.php';
require_once CHERRY_LIB.'/lib/cherry/base/config.php';
require_once CHERRY_LIB.'/lib/cherry/base/event.php';
require_once CHERRY_LIB.'/lib/cherry/extension.php';
require_once CHERRY_LIB.'/lib/cherry/base/cherry.php';
require_once CHERRY_LIB.'/lib/cherry/base/application.php';

use Cherry\Autoloader\Autoloader;
use Cherry\Autoloader\Autoloaders;

function unipath($path) {
    if (DIRECTORY_SEPARATOR != '/') {
        $path = str_replace('\\',DIRECTORY_SEPARATOR,$path);
    }
    while(strpos($path,DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR)!==false)
        $path = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
    return $path;
}

// Register the autoloader for the base library
Autoloaders::register(new Autoloader(CHERRY_LIB.'/lib'));

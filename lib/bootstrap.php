<?php

namespace cherry;

$_os = strtolower(PHP_OS);
define('_DS_',DIRECTORY_SEPARATOR);
define('_IS_LINUX', ($_os=='linux'));
define('_IS_WINDOWS',(substr($_os,0,3)=='win'));
define('_IS_MACOS',($_os=='darwin'));
define('_IS_CLI',(php_sapi_name() == 'cli'));
define('_IS_CLI_SERVER',(php_sapi_name() == 'cli-server'));
define('CHERRY_VERSION','1.0.0-alpha');
define('DEBUG_VERBOSE',false);
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
if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}
require_once CHERRY_LIB.'/lib/data/fifoqueue.php';
if (PHP_VERSION_ID >= 50400) {
    require_once CHERRY_LIB.'/lib/traits.php';
}
require_once CHERRY_LIB.'/lib/cherry/base/autoloader.php';
require_once CHERRY_LIB.'/lib/cherry/base/debug.php';
require_once CHERRY_LIB.'/lib/bundles.php';
require_once CHERRY_LIB.'/lib/cherry/base/config.php';
require_once CHERRY_LIB.'/lib/cherry/base/event.php';
require_once CHERRY_LIB.'/lib/cherry/extension.php';
require_once CHERRY_LIB.'/lib/cherry/base/cherry.php';
//require_once CHERRY_LIB.'/lib/cherry/base/application.php';
require_once CHERRY_LIB.'/lib/app.php';

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

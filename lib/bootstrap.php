<?php

namespace Cherry;

$_os = strtolower(PHP_OS);

require_once CHERRY_LIB.'/lib/utils.php';

// Defines
define('_DS_',DIRECTORY_SEPARATOR);
define('_IS_LINUX', ($_os=='linux'));
define('_IS_WINDOWS',(substr($_os,0,3)=='win'));
define('_IS_MACOS',($_os=='darwin'));
define('_IS_CLI',(php_sapi_name() == 'cli'));
define('_IS_CLI_SERVER',(php_sapi_name() == 'cli-server'));
define('CHERRY_VERSION','1.0.0-alpha');

if ($_app = getenv("CHERRY_APP")) if (!defined("CHERRY_APP")) define('CHERRY_APP',$_app);
if ($_lib = getenv("CHERRY_LIB")) if (!defined("CHERRY_LIB")) define('CHERRY_LIB',$_lib);

define('DEBUG_VERBOSE',(getenv('DEBUG_VERBOSE')==1));

if (getenv('DEBUG_ERRORS')==1)
    error_reporting(E_ERROR | E_WARNING | E_PARSE | E_COMPILE_ERROR);

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

require_once CHERRY_LIB.'/lib/cherry/base/debug.php';
require_once CHERRY_LIB.'/lib/cherry/base/autoloader.php';

$al = new \Cherry\Base\Autoloader(CHERRY_LIB.'/lib');
$al->register();

require_once CHERRY_LIB.'/lib/_globals.php';


if (getenv("PROFILE")) {
    $pc = explode(":",getenv("PROFILE"));
    $binlog = false;
    $fn = null;
    if ($pc[0] == "1") {
        if (count($pc)>1)
            $fn = $pc[1];
        if (!$fn)
            $fn = 'profile.cpd';
        if (count($pc)>2) {
            $opts = explode(",",strtoupper($pc[2]));
            if (in_array("BIN",$opts)) {
                $binlog = true;
                $fn = str_replace(".cpd",".cpb",$fn);
            }
        }
        \Cherry\Util\AppProfiler::profile($fn,$binlog);
    }
}
require_once CHERRY_LIB.'/lib/cherry/base/config.php';
require_once CHERRY_LIB.'/lib/cherry/base/event.php';
require_once CHERRY_LIB.'/lib/cherry/extension.php';
require_once CHERRY_LIB.'/lib/cherry/base/cherry.php';
require_once CHERRY_LIB.'/lib/app.php';
//require_once CHERRY_LIB.'/lib/cherry/base/application.php';

function unipath($path) {
    if (DIRECTORY_SEPARATOR != '/') {
        $path = str_replace('\\',DIRECTORY_SEPARATOR,$path);
    }
    while(strpos($path,DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR)!==false)
        $path = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
    return $path;
}


class ScopeTimer {
    private $info, $tstart;
    function __construct($info) {
        $this->info = $info;
        $this->tstart = microtime(true);
    }
    function __destruct() {
        $dur = microtime(true) - $this->tstart;
        \Cherry\debug('%s: %fus', $this->info, $dur);
    }
}

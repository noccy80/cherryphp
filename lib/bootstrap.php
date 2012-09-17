<?php

namespace cherry;

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

define('CHERRY_APPDIR',CHERRY_APPROOT.'/application');
define('CHERRY_EXTDIR',CHERRY_APPROOT.'/extensions');

if (file_exists(CHERRY_APPROOT.'/application.ini')) {
    define('CHERRY_CFGDIR',CHERRY_APPROOT);
} else {
    define('CHERRY_CFGDIR',CHERRY_APPDIR.'/config');
}

$_os = strtolower(PHP_OS);
define('_IS_LINUX', ($_os=='linux'));
define('_IS_WINDOWS',(substr($_os,0,3)=='win'));
define('_IS_MACOS',($_os=='darwin'));

/*
printf("CHERRY_APPROOT: %s\n", CHERRY_APPROOT);
printf("CHERRY_APPDIR: %s\n", CHERRY_APPDIR);
printf("CHERRY_CFGDIR: %s\n", CHERRY_CFGDIR);
printf("CHERRY_EXTDIR: %s\n", CHERRY_EXTDIR);
*/

require_once CHERRY_LIB.'/lib/cherry/base/config.php';
require_once CHERRY_LIB.'/lib/cherry/base/event.php';
require_once CHERRY_LIB.'/lib/cherry/base/autoloader.php';

// Register the autoloader for the base library
use Cherry\Autoloader\Autoloader;
use Cherry\Autoloader\Autoloaders;
Autoloaders::register(new Autoloader(CHERRY_LIB.'/lib'));

const LOG_DEBUG = 0x01;

function unipath($path) {
    if (DIRECTORY_SEPARATOR != '/') {
        $path = str_replace('\\',DIRECTORY_SEPARATOR,$path);
    }
    while(strpos($path,DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR)!==false)
        $path = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
    return $path;
}

function log($type,$fmt,$args=null) {
    $arg = func_get_args();
    $fmts = array_splice($arg,1);
    $so = call_user_func_array('sprintf',$fmts);
    if (($type == LOG_DEBUG) && (getenv('DEBUG') == 1)) {
        fputs(STDERR,$so."\n");
    } elseif ($type != LOG_DEBUG) {
        fputs(STDOUT,$so."\n");
    }
}

class Lepton {

    protected $application = null;
    private $conf = null;
    private static $instance = null;

    public static function getInstance() {
        if (!self::$instance) self::$instance = new \cherry\Lepton();
        return self::$instance;
    }

    public function runApplication(Application $app) {
        $this->application = $app;
        $app->setPath($this->conf->apppath);
        $app->loadConfiguration('application',CHERRY_CFGDIR.'/application.ini');
        try {
            $extn = $app->getConfiguration('application','extensions');
            foreach((array)$extn as $ext=>$on) {
                if ($on) {
                    $extdir = CHERRY_EXTDIR.DIRECTORY_SEPARATOR.$ext.DIRECTORY_SEPARATOR;
                    require_once $extdir.'extension.php';
                }
            }
        } catch (\cherry\ApplicationException $e) {
        }
        \cherry\Base\Event::invoke('onbeforeapplication');
        $app->run();
        \cherry\Base\Event::invoke('onafterapplication');
    }

    public function getApplication() {
        return $this->application;
    }
    
    public function __construct($scriptpath = null) {

        if (!self::$instance) self::$instance = $this;

        $this->conf = new \StdClass();
        $this->conf->apppath = getenv('CHERRY_APP');
        $this->conf->libpath = getenv('CHERRY_LIB');
        if (!$scriptpath) $scriptpath = __FILE__;
        if (!defined('CHERRY_LIB')) define('CHERRY_LIB',$this->conf->libpath);

        $path = get_include_path();
        set_include_path($this->conf->libpath . PATH_SEPARATOR . $path);

    }

}

abstract class Application {

    protected $path = null;
    private static $instance = null;
    public static function getInstance() {
        return self::$instance;
    }

    public function __construct() {
        self::$instance = $this;
    }

    public function setPath($path) {
        $this->path = $path.'/app';
    }

    public function getPath() {
        return $this->path;
    }

    private $cfgsets = array();

    public function loadConfiguration($set,$path) {
        if (file_exists($path)) {
            $this->cfgsets[$set] = parse_ini_file($path,true);
        } else {
            $this->cfgsets[$set] = array();
            // throw new ApplicationException(_('Configuration file could not be found'), ApplicationException::ERR_CONFIG_FILE_MISSING);
        }
    }
    
    public function getConfiguration($set,$group=null) {
        \cherry\log(\cherry\LOG_DEBUG, __CLASS__."->GetConfiguration: (set=%s, group=%s)",$set,$group);
        if (empty($this->cfgsets[$set])) {
            throw new ApplicationException(_('Configuration set could not be found'), ApplicationException::ERR_CONFIG_NOT_FOUND);
        }
        $ret = $this->cfgsets[$set];
        if ($group) {
            $groups = array_keys($ret);
            $out = null;
            foreach($groups as $g) {
                if (strpos($g,':')!==false) {
                    list($gn,$gp) = explode(':',$g);
                } else {
                    $gn = $g;
                    $gp = null;
                }
                if (trim($gn) == $group) {
                    if ($gp) {
                        $out = $this->getConfiguration($set,trim($gp));
                        $grp = $ret[$g];
                        foreach($grp as $k=>$v) $out[$k] = $v;
                    } else {
                        $out = $ret[$group];
                    }
                }

            }
            $ret = $out;
        }
        return $ret;
    }

    abstract function run();


}

class ApplicationException extends \Exception {
    const ERR_CONFIG_NOT_FOUND = 1;
    const ERR_CONFIG_FILE_MISSING = 2;
}

class ConfigurationException extends \Exception { }

<?php

namespace Cherry;

function loadbundle($bundle) {
    \Cherry\BundleManager::load($bundle);
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

class ConfigurationException extends \Exception { }

function withAll() {
    $args = func_get_args();
    $cbargs = array();
    $vals = array();
    $cb = null;
    for ($n = 0; $n < count($args); $n++) {
        if (!$cb) {
            if (is_callable($args[$n])) {
                $cb = $args[$n];
            } else {
                $vals[] = $args[$n];
            }
        } else {
            $cbargs[] = $args[$n];
        }
    }
    $out = array();
    foreach($vals as $val) {
        $out[] = call_user_func_array($cb,array_merge(array($val),$cbargs));
    }
    return $out;
}

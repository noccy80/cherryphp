<?php

namespace Cherry;


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

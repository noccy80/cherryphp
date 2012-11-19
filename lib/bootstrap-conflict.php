<?php
/**
 * @file bootstrap.php
 * @brief CherryPHP Framework bootstrap code.
 *
 * This is the file you are to include to get things going.
 */

/**
 * @namespace \Lepton
 * @brief CherryPHP core functions.
 *
 * Note: Will be renamed to Cherry soon
 */
namespace Lepton {

    const LOG_DEBUG = 0x01;

    /**
     * @brief Log messages
     *
     * @param Mixed $type The type of debug message, one of \cherry\LOG_*
     * @param Mixed $fmt The format for sprintf
     * @param ... $args The data for sprintf
     */
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

    /**
     * @class Lepton
     * @brief CherryPHP base class.
     */
    class Lepton {

        private $application = null;
        private $conf = null;
        private static $instance = null;
        public static function getInstance() {
            if (!self::$instance) self::$instance = new \cherry\Lepton();
        }

        public function runApplication(Application $app) {
            $this->application = $app;
            $app->setPath($this->conf->apppath);
            $app->loadConfiguration('application',$this->conf->apppath.DIRECTORY_SEPARATOR.'/app/config/application.ini');
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

            $path = get_include_path();
            set_include_path($this->conf->libpath . PATH_SEPARATOR . $path);
            spl_autoload_register(array($this,'_spl_autoload'),true,true);

        }

        public function _spl_autoload($class) {
            \cherry\log(\cherry\LOG_DEBUG,'Autoload request: %s', $class);
            $file = 'lib'.DIRECTORY_SEPARATOR.strtolower(str_replace('\\',DIRECTORY_SEPARATOR,$class)).'.php';
            //if (file_exists($file)) {
                \cherry\log(\cherry\LOG_DEBUG,'Including %s',$file);
                include_once $file;
            //} else {
            //    \cherry\log(\cherry\LOG_DEBUG,'No matching file found (tried %s)', $file);
            //}
        }

    }

    abstract class Application {

        protected $path = null;
        private static $instance = null;
        private $cfgsets = array();

        /**
         * @brief Singleton getInstance()
         *
         * @return Application The main application instance
         */
        public static function getInstance() {
            if (!self::$instance)
                self::$instance = new self();
            return self::$instance;
        }

        public function __construct() {
            if (!self::$instance)
                self::$instance = $this;
        }

        public function setPath($path) {
            $this->path = $path.'/app';
        }

        public function getPath() {
            return $this->path;
        }

        public function loadConfiguration($set,$path) {
            if (file_exists($path)) {
                $this->cfgsets[$set] = parse_ini_file($path,true);
            } else {
                $this->cfgsets[$set] = array();
            }
        }

        public function getConfiguration($set,$group=null) {
            if (empty($this->cfgsets[$set])) {
                throw new ApplicationException(_('Configuration set could not be found'), ApplicationException::ERR_CONFIG_NOT_FOUND);
            }
            $ret = $this->cfgsets[$set];
            if ($group) {
                $groups = array_keys($ret);
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
    }

    class ConfigurationException extends \Exception { }

}

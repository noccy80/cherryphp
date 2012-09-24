<?php

namespace Cherry;

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

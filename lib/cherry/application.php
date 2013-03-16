<?php

namespace Cherry;

abstract class Application implements \Cherry\Core\IApplication {

    protected $path = null;
    private static $instance = null;
    private $logtarget = null;

    private $_writebuffer;
    public static function getInstance() {
        return self::$instance;
    }

    public function __construct($apppath=null) {
        if ($apppath) {
            define("APPPATH",realpath($apppath));
        } else {
            define("APPPATH",getcwd());
        }
        $this->path = APPPATH;
        if (is_callable([ $this, 'handleException' ])) {
            \Cherry\debug("Registering application exception handler...");
            set_error_handler(array($this,'__php_handleError'), E_ALL);
            set_exception_handler(array($this,'handleException'));
            // Active assert and make it quiet
            assert_options(ASSERT_ACTIVE, 1);
            assert_options(ASSERT_WARNING, 0);
            assert_options(ASSERT_QUIET_EVAL, 1);
            assert_options(ASSERT_CALLBACK, array($this,'__php_handleAssert'));
            register_shutdown_function(array($this,'__php_handleShutdown'));
        }

        if (!self::$instance) self::$instance = $this;
    }

    public static function __php_handleShutdown() {
        if (($err = error_get_last())) {
            $errmask = (E_ERROR | E_PARSE | E_CORE_ERROR | E_USER_ERROR);
            if (($err['type'] & $errmask) == $errmask) {
                error_log("Error: {$err['message']} (in {$err['file']} line {$err['line']})");
            }
        }
    }

    public static function __php_handleError($errno,$errstr,$file,$line,$errctx) {

        if ($errno & E_WARNING) {
            //fprintf(STDERR,"Warning: %s [from %s:%d]\n", $errstr,$errfile,$errline);
            \Cherry\debug("Warning: %s [from %s:%d]\n", $errstr,$file,$line);
            return true;
        }
        if ($errno & E_DEPRECATED) {
            //fprintf(STDERR,"Deprecated: %s [from %s:%d]\n", $errstr,$errfile,$errline);
            return true;
        }

        if (!error_reporting()) return;
        throw new \ErrorException($errstr,$errno,0,$file,$line);

    }
    // Create a handler function
    public static function __php_handleAssert($file, $line, $code, $desc = null) {

        \Cherry\debug("Assertion failed in %s on line %d", $file, $line);
        /*$log = DebugLog::getDebugLog();
        $ca = \Cherry\Cli\Console::getAdapter();

        $str = sprintf("in %s on line %d",$file, $line );
        $bt = Debug::getBacktrace(1);
        self::showError($ca,'Assertion failed',$str,$file,$line,$log,$bt);*/
        throw new \ErrorException("Assertion failed: {$code}", 0, 0, $file, $line);

        exit(1);
    }


    public function setPath($path) {
        $this->path = $path.'/app';
    }

    public function getPath() {
        return $this->path;
    }

    private $cfgsets = array();

    public function loadConfig() {
        if (file_exists($this->path._DS_.'application.json')) {
            $cfg = json_decode(file_get_contents($this->path._DS_.'application.json'));
            if (!$cfg) {
                user_error(json_last_error());
            }
        }
    }

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

    /**
     * Log text to the current logging facility on the
     * application level.
     *
     * @param string $str The printf pattern
     * @param string ... The arguments
     */
    public function log($str) {
        static $fhtarget = null;
        $args = func_get_args();
        $lstr = call_user_func_array('sprintf',$args);
        if ($this->logtarget === null) {
            echo $lstr."\n";
        } elseif ($this->logtarget === false) {
            return;
        } elseif (is_array($this->logtarget)) {
            array_map([$this,'log'],$lstr);
        } elseif (is_callable($this->logtarget)) {
           call_user_func($this->logtarget,$lstr);
        } elseif (is_object($this->logtarget)) {
            $this->logtarget($lstr);
        } elseif (is_resource($this->logtarget)) {
            fputs($this->logtarget,$lstr."\n");
        } elseif (is_writable($this->logtarget)) {
            if (!$fhtarget)
                $fhtarget = fopen($this->logtarget,"a+");
            fwrite($fhtarget,$lstr."\n");
        } else {
            echo $lstr."\n";
        }
    }

    /**
     * Set the log target as one of:
     *  - Callable (closure)
     *  - Class (implementing __invoke)
     *  - Resource (from fopen or STDOUT, STDERR etc)
     *  - NULL (stdout)
     *
     * @param string $target The log target
     */
    public function setLogTarget($target) {
        $this->logtarget = $target;
    }

    public function debug($str) {
        call_user_func_array('\Cherry\Debug',func_get_args());
    }

    public function warn($str) {
        $msg = call_user_func_array('sprintf',func_get_args());
        error_log($msg);
    }

    public function write($str) {
        $this->writebuffer .= $str;
    }

    public function getWriteBuffer() {
        return $this->writebuffer;
    }

}

class ApplicationException extends \Exception {
    const ERR_CONFIG_NOT_FOUND = 1;
    const ERR_CONFIG_FILE_MISSING = 2;
}

<?php

namespace Cherry\Base;

if (!defined('_NS_')) define('_NS_',"\\");

/**
 * @brief Autoloader implementation.
 *
 *
 *
 *
 * @code
 * $al = new \Cherry\Base\AutoLoader("/opt/someframework","SomeFramework");
 * $al->register();
 * @endcode
 *
 */
class AutoLoader {
    const CP_AUTO = 'auto';
    const CP_PRESEVE = 'yes';
    const CP_LOWERCASE = 'no';
    private
        $path = null,
        $ns = null,
        $options = [
            'extensions' => '.php|.class.php',
            'casepreserve' => self::CP_AUTO
        ];

    /**
     *
     *
     * @param string $path
     * @param string $ns
     * @param array $options
     */
    public function __construct($path,$ns=null,array $options=null) {
        $this->path = $path;
        if ($ns) {
            $ns = trim($ns,_NS_)._NS_;
            $this->ns = $ns;
        }
        $this->options = array_merge($this->options,(array)$options);
    }

    /**
     * @brief Register the autoloader.
     *
     */
    public function register() {
        spl_autoload_register([&$this,'autoload'],true);
        $this->debug('Registered loader for %s', $this->path);
    }

    /**
     * @brief Unregister the autoloader.
     *
     */
    public function unregister() {
        spl_autoload_unregister([&$this,'autoload'],true);
        $this->debug('Registered loader for %s', $this->path);
    }

    /**
     *
     *
     */
    public function autoload($class) {
        $this->debug("Autoload requested: {$class}");
        if ($this->ns) {
            $cm = strtolower($class);
            $nm = strtolower($this->ns);
            if (substr($cm,0,strlen($nm)) == $nm) {
                $cf = substr($class,strlen($nm));
            } else return false;
        } else {
            $cf = $class;
        }
        if (strpos($cf,'_')!==false) {
            $cfn = str_replace('_',_DS_,$cf);
        } else {
            $cfn = str_replace("\\",'/',$cf);
        }
        $loc = $this->path._DS_;
        $extn = (array)explode("|",$this->options['extensions']);
        $tested = [];
        if ($this->options['casepreserve']==self::CP_LOWERCASE) {
            foreach($extn as $ext) {
                $fl = $loc.strtolower($cfn).$ext;
                $tested[] = $fl;
                if (file_exists($fl) && is_readable($fl)) {
                    $this->debug("Autoloading: %s", $fl);
                    require_once $fl;
                    return true;
                }
            }
        } elseif ($this->options['casepreserve']==self::CP_PRESEVE) {
            foreach($extn as $ext) {
                $fl = $loc.$cfn.$ext;
                $tested[] = $fl;
                if (file_exists($fl) && is_readable($fl)) {
                    $this->debug("Autoloading %s", $fl);
                    require_once $fl;
                    return true;
                }
            }
        } else {
            for($case = 0; $case < 2; $case++) {
                foreach($extn as $ext) {
                    $fl = $loc.(($case==1)?strtolower($cfn):$cfn).$ext;
                    $tested[] = $fl;
                    if (file_exists($fl) && is_readable($fl)) {
                        $this->debug("Autoloading %s", $fl);
                        require_once $fl;
                        return true;
                    }
                }
            }
        }
        $this->debug("Autoloader: Failed to match class for autoload, tried: %s", join(", ",$tested));
        return false;

    }

    protected function debug($str) {
        $args = func_get_args();
        $fmt = array_shift($args);
        $fmt = "\033[1m".get_called_class()."\033[21m: ".$fmt;
        array_unshift($args,$fmt);
        call_user_func_array("\Cherry\debug",$args);
    }

}

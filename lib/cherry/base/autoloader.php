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
        \cherry\log(\cherry\LOG_DEBUG,'Autoloader: Registered loader for %s', $this->path);
    }

    /**
     * @brief Unregister the autoloader.
     *
     */
    public function unregister() {
        spl_autoload_unregister([&$this,'autoload'],true);
        \cherry\log(\cherry\LOG_DEBUG,'Autoloader: Registered loader for %s', $this->path);
    }

    /**
     *
     *
     */
    public function autoload($class) {
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
        if ($this->options['casepreserve']==self::CP_LOWERCASE) {
            foreach($extn as $ext) {
                $fl = $loc.strtolower($cfn).$ext;
                // \Cherry\Debug("Autoload: {$class} (path {$fl}");
                if (file_exists($fl) && is_readable($fl)) {
                    require_once $fl;
                    return true;
                }
            }
        } elseif ($this->options['casepreserve']==self::CP_PRESEVE) {
            foreach($extn as $ext) {
                $fl = $loc.$cfn.$ext;
                // \Cherry\Debug("Autoload: {$class} (path {$fl}");
                if (file_exists($fl) && is_readable($fl)) {
                    require_once $fl;
                    return true;
                }
            }
        } else {
            for($case = 0; $case < 2; $case++) {
                foreach($extn as $ext) {
                    $fl = $loc.(($case==1)?strtolower($cfn):$cfn).$ext;
                    // \Cherry\Debug("Autoload: {$class} (path {$fl}");
                    if (file_exists($fl) && is_readable($fl)) {
                        require_once $fl;
                        return true;
                    }
                }
            }
        }

    }

}

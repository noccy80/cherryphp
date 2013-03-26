<?php

namespace Cherry\Base;

if (!defined('_NS_')) define('_NS_',"\\");

require_once __DIR__ . "/../traits/tdebug.php";

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
    use \Cherry\Traits\TDebug;
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
    public function register($prepend=false) {
        spl_autoload_register([&$this,'autoload'],true,$prepend);
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

    private function debugInfo($fl,$class) {
        if (trait_exists($class)) $type = "trait";
        elseif (class_exists($class,false)) $type = "class";
        elseif (interface_exists($class,false)) $type = "interface";
        else $type = "<???>";
        $this->debug("Autoloaded %s for %s %s", $fl, $type, $class);
    }

    /**
     *
     *
     */
    public function autoload($class) {
        //$this->debug("Autoload requested: {$class}");
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
                    require_once $fl;
                    $this->debugInfo($fl,$class);
                    return true;
                }
            }
        } elseif ($this->options['casepreserve']==self::CP_PRESEVE) {
            foreach($extn as $ext) {
                $fl = $loc.$cfn.$ext;
                $tested[] = $fl;
                if (file_exists($fl) && is_readable($fl)) {
                    require_once $fl;
                    $this->debugInfo($fl,$class);
                    return true;
                }
            }
        } else {
            for($case = 0; $case < 2; $case++) {
                foreach($extn as $ext) {
                    $fl = $loc.(($case==1)?strtolower($cfn):$cfn).$ext;
                    $tested[] = $fl;
                    if (file_exists($fl) && is_readable($fl)) {
                        require_once $fl;
                        $this->debugInfo($fl,$class);
                        return true;
                    }
                }
            }
        }
        $bt = \Cherry\Core\Debug::getCaller(3);
        $srcfile = (empty($bt["file"])?"na":$bt["file"]);
        $srcline = (empty($bt["line"])?"na":$bt["line"]);
        $this->debug("No candidate for autoloading {$class}; tried %s [from %s]", join(", ",$tested), "{$srcfile}:{$srcline}");
        return false;

    }

}

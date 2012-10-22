<?php

namespace Cherry;

use Cherry\Autoloader\Autoloaders;
use Cherry\Autoloader\Autoloader;

class BundleManager {

    private static $instance = null;
    private static $bundles = array();

    public static function getInstance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function register(Bundle $bundle) {
        $this->bundles[$bundle->key] = $bundle;
    }

    public function load($bundlekey) {
        if (!empty(self::$bundles[$bundlekey]))
            return;
        $bpath = CHERRY_LIB._DS_.'bundles'._DS_.$bundlekey;
        if (file_exists($bpath._DS_.'manifest.json')) {
            $info = require($bpath._DS_.'loader.php');
            // var_dump($info);
            $bundlesrc = $bpath._DS_.((empty($info['src'])?'src':$info['src']));
            Autoloaders::register(new Autoloader($bundlesrc));
            foreach((array)$info['autoload'] as $classname) {
                Autoloaders::_spl_autoload($classname);
            }
        } else {
            throw new BundleException("Bundle ".$bundlekey." not found.");
        }
        self::$bundles[$bundlekey] = $info;
    }

}

class BundleException extends \Exception { }

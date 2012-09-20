<?php

namespace Cherry;

class BundleManager {

    private static $instance = null;
    private $bundles = array();

    public static function getInstance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public static function __callstatic($cmd,$args) {
        return call_user_func_array(array(self::getInstance(),$cmd),$args);
    }
    
    public function register(Bundle $bundle) {
        $this->bundles[$bundle->key] = $bundle;
    }
    
    public function load($bundlekey) {
        $bpath = CHERRY_LIB._DS_.'bundles'._DS_.$bundlekey;
        if (file_exists($bpath._DS_.'manifest.json')) {
            // Load bundle here
        }
    }

}

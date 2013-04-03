<?php

namespace Cherry\Cache\Adapters;

use \Cherry\Cache\CacheAdapter;

class MemcachedAdapter extends CacheAdapter {

    private $mc = null;

    public function __construct($persistent_id=null) {

        $this->mc = new \Memcached();
        $this->mc->setOption(\Memcached::OPT_PREFIX_KEY, $persistent_id);
        $this->mc->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true); // advisable option
        if (!count($this->mc->getServerList())) {
            $cfg = ObjectManager::getObject("local:/config/system");
            $this->mc->addServer('localhost','11211');
        }

    }

    public static function getInstance($persistent_id=null) {
        static  $default = null,
                $instances = [];
        if (!$persistent_id) {
            if (!$default)
                $default = new Memcached();
            return $default;
        } else {
            if (!array_key_exists($persistent_id,$instances))
                $instances[$persistent_id] = new Memcached($persistent_id);
            return $instances[$persistent_id];
        }
    }

    public function getValue($key) {

        $val = $this->mc->get($key);
        return $val;

    }

    public function getValueCas($key) { }

    public function updateValue($key, $value, $expires = 0) {

        $this->mc->set($key,$value,$expires);

    }

    public function updateValueCas($key, $value, $cas) { }

    public function checkValue($key) { }

}

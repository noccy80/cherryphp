<?php

namespace Cherry\Cache;

class Cache {

    public static function getInstance($group=null) {
        if (!$group) {
            $group = \App::config()->get('cache.defaultgroup');
            if (!$group)
                user_error("No cache group defined and default group is not set.");
        }
        static $instances = [];
        if (!array_key_exists($group,$instances))
            $instances[$group] = new self($group);
        return $instances[$group];
    }

    public function __construct($group) {
        $cfg = \App::config()->get("cache.groups.{$group}");
        if (!$cfg)
            user_error("Cache group {$group} is undefined.");
        $adapter = $cfg->adapter;
        if (strpos($adapter,"\\")===false) {
            $adapterclass = "\\Cherry\\Cache\\".$adapter."adapter";
        } else {
            $adapterclass = $adapter;
        }
        $this->adapter = new $adapterclass();
    }

    public function get($key) {
        return $this->adapter->getValue($key);
    }

    public function set($key,$data,$expiry=null) {
        $this->adapter->updateValue($key,$data,$expiry);
    }

}

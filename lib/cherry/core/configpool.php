<?php

namespace Cherry\Core;

use Cherry\Data\Ddl\SdlTag;

abstract class ConfigPool {
    use \Cherry\Traits\TstaticDebug;
    private static $configfiles = [];
    private static $pools = [];
    private function __construct() {
    }
    public static function bindPool($config,$identifier,$writeable=false) {
        \utils::deprecated(__CLASS__."::bindPool", "\\Cherry\\Core\\ConfigManager::bind");
        $cfgpath = PathResolver::path($config);
        $cfghash = sha1($cfgpath);
        if (array_key_exists($identifier,self::$pools)) {
            // Key exists, make sure it's the same config file
            if (self::$pools[$identifier]->cfgpath == $cfgpath) {
                // Already set up, so update flags and return.
                self::$pools[$identifier]->writeable = $writeable;
                self::debug("Pool already bound. Updating flags for '{$identifier}");
                return true;
            }
        }
        self::debug("Binding pool '{$identifier}' to '{$cfgpath}'");
        $poolinfo = (object)[
            'id' => $identifier,
            'cfgpath' => $cfgpath,
            'cfghash' => $cfghash,
            'writeable' => $writeable
        ];
        self::$pools[$identifier] = $poolinfo;

    }
    public static function getPool($identifier) {
        \utils::deprecated(__CLASS__."::getPool", "\\Cherry\\Core\\ConfigManager::get");
        if (array_key_exists($identifier,self::$pools)) {
            $pool = self::$pools[$identifier];
            if (!array_key_exists($pool->cfghash,self::$configfiles)) {
                $path = $pool->cfgpath;
                $cpath = dirname($path).'/.'.basename($path).'.cache';
                if (file_exists($path)) {
                    // Check for a serialized cache of the config
                    if (file_exists($cpath) && (filemtime($cpath)>filemtime($path))) {
                        // Load from cache
                        self::debug("Reading config '{$identifier}' from cache...");
                        $tag = unserialize(file_get_contents($cpath));
                    } else {
                        // Update cache
                        self::debug("Updating cache for config '{$identifier}'...");
                        $tag = SdlTag::createFromFile($path);
                        file_put_contents($cpath,serialize($tag));
                    }
                    self::$configfiles[$pool->cfghash] = $tag;
                } else {
                    self::debug("File not found: {$path}");
                    self::$configfiles[$pool->cfghash] = null;
                }
            }
            return self::$configfiles[$pool->cfghash];
        } else {
            self::debug("Error: Pool has not been bound: {$identifier}");
            return null;
        }
    }
    public static function setPool($identifier, SdlTag $root) {
        \utils::deprecated(__CLASS__."::set", "\\Cherry\\Core\\ConfigManager::set");
        if (array_key_exists($identifier,self::$pools)) {
            $pool = self::$pools[$identifier];
            self::$configfiles[$pool->cfghash] = $root;
        } else {
            return null;
        }
    }
}

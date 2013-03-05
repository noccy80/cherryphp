<?php

namespace Cherry\Core;

use Cherry\Base\PathResolver;
use Cherry\Data\Ddl\SdlTag;

abstract class ConfigPool {
    private static $configfiles = [];
    private static $pools = [];
    private function __construct() {
    }
    public static function bindPool($config,$identifier,$writeable=false) {
        $cfgpath = PathResolver::path($config);
        $cfghash = sha1($cfgpath);
        if (array_key_exists($identifier,self::$pools)) {
            // Key exists, make sure it's the same config file
            if (self::$pools[$identifier]->cfgpath == $cfgpath) {
                // Already set up, so update flags and return.
                self::$pools[$identifier]->writeable = $writeable;
                return true;
            }
        }
        $poolinfo = (object)[
            'id' => $identifier,
            'cfgpath' => $cfgpath,
            'cfghash' => $cfghash,
            'writeable' => $writeable
        ];
        self::$pools[$identifier] = $poolinfo;

    }
    public static function getPool($identifier) {
        if (array_key_exists($identifier,self::$pools)) {
            $pool = self::$pools[$identifier];
            if (!array_key_exists($pool->cfghash,self::$configfiles)) {
                $path = $pool->cfgpath;
                $cpath = dirname($path).'/.'.basename($path).'.cache';
                if (file_exists($path)) {
                    // Check for a serialized cache of the config
                    if (file_exists($cpath) && (filemtime($cpath)>filemtime($path))) {
                        // Load from cache
                        \debug("ConfigPool: Reading config '{$identifier}' from cache...");
                        $tag = unserialize(file_get_contents($cpath));
                    } else {
                        // Update cache
                        \debug("ConfigPool: Updating cache for config '{$identifier}'...");
                        $tag = SdlTag::createFromFile($path);
                        file_put_contents($cpath,serialize($tag));
                    }
                    self::$configfiles[$pool->cfghash] = $tag;
                } else {
                    \debug("ConfigPool: File not found: {$path}");
                    self::$configfiles[$pool->cfghash] = null;
                }
            }
            return self::$configfiles[$pool->cfghash];
        } else {
            \debug("ConfigPool: Pool has not been bound: {$identifier}");
            return null;
        }
    }
    public static function setPool($identifier, SdlTag $root) {
        if (array_key_exists($identifier,self::$pools)) {
            $pool = self::$pools[$identifier];
            self::$configfiles[$pool->cfghash] = $root;
        } else {
            return null;
        }
    }
}

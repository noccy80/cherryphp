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
            'writeable' => $false
        ];
        self::$pools[$identifier] = $poolinfo;

    }
    public static function getPool($identifier) {
        if (array_key_exists($identifier,self::$pools)) {
            $pool = self::$pools[$identifier];
            if (!array_key_exists($cfghash,self::$configfiles)) {
                if (file_exists($path)) {
                    self::$configfiles[$cfghash] = SdlTag::createFromFile($cfgpath);
                } else {
                    self::$configfiles[$cfghash] = null;
                }
                return self::$configfiles[$pool->cfghash];
            } else {
                return null;
            }
        } else {
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

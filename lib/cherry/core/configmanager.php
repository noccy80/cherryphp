<?php

namespace Cherry\Core;

use Cherry\Base\PathResolver;
use Cherry\Data\Ddl\SdlTag;

class ConfigManager implements \Cherry\Core\IObjectManagerInterface {
    use \Cherry\Traits\TStaticDebug;
    private static $configfiles = [];
    private static $pools = [];
    private function __construct() {
    }
    public static function bind($identifier,$config,$writeable=false) {
        // If the file name contains a path, resolve it via pathresolver,
        // otherwise try to detect the location of the configuration file.
        if (strpos($config,"/")!==false) {
            $cfgpath = PathResolver::path($config);
        } else {
            $test = PathResolver::path("{APP}/{$config}");
            if (file_exists($test)) {
                $cfgpath = $test;
            } else {
                $test = PathResolver::path("{APP}/config/{$config}");
                if (file_exists($test)) {
                    $cfgpath = $test;
                }
            }
        }
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
    public static function get($identifier) {
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
                        if (!$tag) {
                            self::debug("Updating cache for config '{$identifier}' (Forced)");
                            $tag = SdlTag::createFromFile($path);
                            file_put_contents($cpath,serialize($tag));
                        }
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
    public static function set($identifier, SdlTag $root) {
        if (array_key_exists($identifier,self::$pools)) {
            $pool = self::$pools[$identifier];
            self::$configfiles[$pool->cfghash] = $root;
        } else {
            return null;
        }
    }
    public function omiGetObjectList($path) {
        return ["*"];
    }
    public function omiGetObject($path) {
        $obj = self::get($path->name);
        return $obj;
    }
    public function omiGetObjectProperties($path) {
        if (self::isRegistered($path->name)) {
            return [];
        }
        return null;
    }
    public static function register() {
        ObjectManager::registerObjectRoot("/config/", new ConfigManager());
    }
}

<?php

namespace Cherry\Crypto;

class UuidGenerator {
    private static $uuid;
    // Can't construct
    private function __construct() { }
    
    public static function __callStatic($mtd,$arg) {
        if (!self::$uuid) self::$uuid = Uuid::getInstance();
        switch(strtoupper($mtd)) {
            case 'V1': $gen = Uuid::UUID_V1; break;
            case 'V3': $gen = Uuid::UUID_V3; break;
            case 'UUID':
            case 'V4': $gen = Uuid::UUID_V4; break;
            case 'V5': $gen = Uuid::UUID_V5; break;
            default:
                return null;
        }
        return call_user_func_array([self::$uuid,'generate'],array_merge([$gen],(array)$arg));
    }
    
    public static function valid($uuid) {
        if (!self::$uuid) self::$uuid = Uuid::getInstance();
        return self::$uuid->test($uuid);
    }

}

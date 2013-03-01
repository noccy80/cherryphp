<?php

namespace Cherry\Crypto;

abstract class UuidImpl {

    abstract public function test($uuid);
    abstract public function generate($version = self::UUID_V4, $url = null);
    abstract public function getImplementationName();

    const UUID_V1 = 1;
    const UUID_V3 = 3;
    const UUID_V4 = 4;
    const UUID_V5 = 5;

}

/* Load the appropriate implementation by dynamically extending from the
 * implementation based on what is available on the system. The implementation
 * must extend UuidImpl and implement test() and generate().
 */
if (function_exists('\uuid_make')) {
    // Load the ossp-uuid implementation
    class UuidAbstraction extends \Cherry\Crypto\Uuid\OsspUuidImpl { }
} elseif (function_exists('\uuid_create')) {
    // Load the pecl-uuid implementation
    class UuidAbstraction extends \Cherry\Crypto\Uuid\PeclUuidImpl { }
} else {
    // Load the php implementation
    class UuidAbstraction extends \Cherry\Crypto\Uuid\PhpUuidImpl { }
}

/**
 * @brief UUID Generation
 *
 * Supports the pecl uuid extension, ossp-uuid, and a few additional fallbacks
 * in case the support is missing from the system.
 *
 */
class Uuid extends UuidAbstraction {

    static $instance;

    /**
     * @brief Singleton getter
     *
     * @return Uuid The UUID instance
     */
    static function getInstance() {
        if (empty(self::$instance)) self::$instance = new self();
        return self::$instance;
    }

    static function getBackend() {
        return self::getInstance()->getImplementationName();
    }

}

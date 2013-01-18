<?php

namespace Cherry\Crypto\Uuid;

use Cherry\Crypto\UuidImpl;

class PeclUuidImpl extends UuidImpl {

    /**
     * @brief Test an UUID to see if it is valid
     *
     * @param string $uuid The UUID
     * @return bool True if the UUID is valid.
     */
    public function test($uuid) {
        return \uuid_is_valid($uuid);
    }

    /**
     * @brief Generate a UUID
     *
     * @param mixed $version The version to generate (default UUID_V4)
     * @param mixed $url The URL to use for V3 and V5 UUIDs.
     * @return string The UUID
     */
    public function generate($version = self::UUID_V4, $url = null) {
        // Implementation for pecl uuid
        $hu = null;
        switch($version) {
        case self::UUID_V1:
            $uuid = \uuid_create(\UUID_TYPE_DCE);
            break;
        case self::UUID_V3:
            return null;
            break;
        case self::UUID_V4:
            $uuid = \uuid_create(\UUID_TYPE_RANDOM);
            break;
        case self::UUID_V5:
            return null;
            break;
        default:
            return null;
        }
        return trim($uuid);
    }

    public function getImplementationName() {
        return "Pecl UUID";
    }

}

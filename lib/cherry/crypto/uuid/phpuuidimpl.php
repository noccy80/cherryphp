<?php

namespace Cherry\Crypto\Uuid;

use Cherry\Crypto\UuidImpl;

class PhpUuidImpl extends UuidImpl {

    /**
     * @brief Test an UUID to see if it is valid
     *
     * @param string $uuid The UUID
     * @return bool True if the UUID is valid.
     */
    public function test($uuid) {
        return reg_match(preg_match("/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}(?(1)\})$/i", $uuid));
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
        case self::UUID_V4:
            $uuid = $this->gen_uuid();
            break;
        default:
            return null;
        }
        return trim($uuid);
    }

    function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,
    
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,
    
            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    public function getImplementationName() {
        return "Pecl UUID";
    }

}

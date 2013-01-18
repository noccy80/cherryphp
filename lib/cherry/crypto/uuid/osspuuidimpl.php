<?php

namespace Cherry\Crypto\Uuid;

use Cherry\Crypto\UuidImpl;

class OsspUuidImpl extends UuidImpl {

    /**
     * @brief Test an UUID to see if it is valid
     *
     * @param string $uuid The UUID
     * @return bool True if the UUID is valid.
     */
    public function test($uuid) {
        $hu = null;
        \uuid_create($hu);
        $status = \uuid_parse($hu, $uuid);
        $ret = ($status === 0);
        // \uuid_destroy($hu);
        return $ret;
    }

    /**
     * @brief Generate a UUID
     *
     * @param mixed $version The version to generate (default UUID_V4)
     * @param mixed $url The URL to use for V3 and V5 UUIDs.
     * @return string The UUID
     */
    public function generate($version = self::UUID_V4, $url = null) {
        // Implementation for ossp-uuid
        $hu = null; $ustr = null;
        switch($version) {
        case self::UUID_V1:
            \uuid_create($hu);
            \uuid_make($hu, \UUID_MAKE_V1 | \UUID_MAKE_MC);
            break;
        case self::UUID_V3:
            \uuid_create($hu);
            if (!$url)
                return null;
            $ns = null;
            \uuid_create($ns);
            \uuid_make($hu, \UUID_MAKE_V3, $ns, $url);
            \uuid_destroy($ns);
            break;
        case self::UUID_V4:
            \uuid_create($hu);
            \uuid_make($hu, \UUID_MAKE_V4);
            break;
        case self::UUID_V5:
            \uuid_create($hu);
            \uuid_create($ns);
            \uuid_make($hu, \UUID_MAKE_V5, $ns, $url);
            \uuid_destroy($ns);
            break;
        default:
            return null;
        }

        \uuid_export($hu, UUID_FMT_STR, $ustr);
        $uuid = $ustr;
        return trim($uuid);
    }

    public function getImplementationName() {
        return "Ossp UUID";
    }
}

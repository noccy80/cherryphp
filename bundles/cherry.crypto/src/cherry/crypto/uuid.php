<?php

namespace cherry\crypto;

/**
 * @brief UUID Generation
 *
 * Supports the pecl uuid extension, ossp-uuid, and a few additional fallbacks
 * in case the support is missing from the system.
 *
 */
class Uuid {

    const UUID_V1 = 1;
    const UUID_V3 = 3;
    const UUID_V4 = 4;
    const UUID_V5 = 5;

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
    
    /**
     * @brief Test an UUID to see if it is valid
     *
     * @param string $uuid The UUID
     * @return bool True if the UUID is valid.
     */
    function test($uuid) {
        $hu = null;
        uuid_create($hu);
        $status = @uuid_import($hu, \UUID_FMT_STR, $uuid);
        $ret = ($status === \UUID_RC_OK);
        uuid_destroy($hu);
        return $ret;
    }

    /**
     * @brief Generate a UUID
     *
     * @param mixed $version The version to generate (default UUID_V4)
     * @param mixed $url The URL to use for V3 and V5 UUIDs.
     * @return string The UUID
     */
    function generate($version = self::UUID_V4, $url = null) {
        if (function_exists('uuid_make')) {
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
                    throw new \Exception(_("UUID v3 requires the url parameter"));
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
                throw new \Exception(_("Error: Invalid UUID version!"));
            }

            \uuid_export($hu, UUID_FMT_STR, $ustr);
            $uuid = $ustr;
        } else {
            // Implementation for pecl uuid
            $hu = null;
            switch($version) {
            case self::UUID_V1:
                $uuid = \uuid_create(\UUID_TYPE_DCE);
                break;
            case self::UUID_V3:
                throw new \Exception(_("No support for this UUID version with pecl-uuid backend."));
                break;
            case self::UUID_V4:
                $uuid = \uuid_create(\UUID_TYPE_RANDOM);
                break;
            case self::UUID_V5:
                throw new \Exception(_("No support for this UUID version with pecl-uuid backend."));
                break;
            default:
                throw new \Exception(_("Error: Invalid UUID version!"));
            }
        }
        return trim($uuid);
    }

}

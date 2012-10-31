<?php

namespace Cherry\DateTime;

abstract class Duration {

    /**
     * @brief Parses strings to extract durations.
     *
     * The strings can be in the following formats:
     *
     * - <b>5M</b> - 5 minutes (case insensitive, 5m is ok)
     * - <b>60</b> - 60 minutes
     * - <b>1:30</b> - 90 minutes (1 hour 30 minutes)
     *
     * If you need to find the number of seconds at a time in the future, use
     * the $offset parameter to define the offset for the result:
     *
     * @code
     *  $expires = Duration::toSeconds('30m',time());
     * @endcode
     *
     * @param string $duration The duration string
     * @param mixed $offset The offset in seconds to apply to the result
     * @return long The number of seconds in the duration plus the offset.
     */
    public static function toSeconds($duration, $offset = null) {
        $mags = [ 's'=> 1, 'm' => 60, 'h' => 3600, 'd' => 86400, 'w' => 604800 ];
        $stot = 0;
        foreach(explode(' ',$duration) as $ent) {
            if ($ent) {
                $val = intval($ent);
                $mag = strtolower(substr($ent,-1,1));
                if (array_key_exists($mag,$mags)) {
                    $secs = $val * $mags[$mag];
                } else {
                    user_error("Valid magnitudes are s, m, h, d and w; got {$mag}");
                }
            } else {
                $secs = 0;
            }
            $stot += $secs;
        }
        if ($offset) $stot += (int)$offset;
        return $stot;
    }

    public static function fromSeconds($seconds) {

    }

}

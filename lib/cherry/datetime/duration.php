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
        
    }

    public static function fromSeconds($seconds) {

    }

}

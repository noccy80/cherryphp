<?php

namespace Cherry\Expm\Net;

use debug;

/**
 *
 *
 *
 * @license GNU General Public License (GPL) v3
 * @copyright Copyright (c) 2012-2013, NoccyLabs
 */
class BufferedSocket extends Socket {

    private $buffer = null;

    public function onDataWaiting() {
        // Read the data

        // Push it on the buffer
        $this->buffer .= $buf;
        // We got data waiting!
        $this->datawaiting = true;
    }
    public function read($length, $nonblock = false) {
        // Do the read from the buffer now
    }
}

<?php

namespace Cherry\Expm\Net;

/**
 *
 *
 *
 * @license GNU General Public License (GPL) v3
 * @copyright Copyright (c) 2012-2013, NoccyLabs
 */
abstract class SocketTransport extends Socket {

    use \Cherry\Traits\TUuid;

    public function onAccept($socket,$peer,$endpoint) {
        $this->stream = $socket;
        $this->peer = $peer;
    }

    abstract public function onDataWaiting();

    abstract public function onProcess();

}

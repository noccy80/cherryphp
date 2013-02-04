<?php

namespace Cherry\Expm\Net;

use debug;
use Cherry\Crypto\Uuid;

/**
 *
 *
 *
 * @license GNU General Public License (GPL) v3
 * @copyright Copyright (c) 2012-2013, NoccyLabs
 */
class Socket {

    public $userdata = null;
    public $stream = null;
    public $datawaiting = false;
    public $uuid = null;
    public $peer = null;
    public $socketserver = null;

    /**
     * @var When a socket is flagged with $discard=true, the socket is all
     * done and can be discarded from whatever pool it belongs to, effectively
     * destructing it.
     */
    public $discard =  false;

    const ERR_NOT_OPEN = 0x01;

    public function __construct($endpoint = null) {
        $this->uuid = Uuid::getInstance()->generate(Uuid::UUID_V4);
        if ($endpoint) {
            if (is_string($endpoint)) {
                // Connect to endpoint url
                $this->connect($endpoint);
            } else {
                if (\get_resource_type($endpoint) == 'stream') {
                    // We can read and write, so try to get the metadata
                    $meta = stream_get_meta_data($endpoint);
                    if (strpos($meta['stream_type'],'socket')!==false)
                        $this->stream = $endpoint;
                    else
                        user_error("Bad endpoint. Stream not of valid type");
                } else {
                    user_error("Bad endpoint.");
                }
            }
        }
    }

    protected function error($msg) {

    }

    public function connect($endpoint) {
        debug("Socket: Connecting to %s", $endpoint);
        $errno = null; $errstr = null;
        // Create the stream
        $this->stream = \stream_socket_client($endpoint, $errno, $errstr);
        // Check for errors
        if (!$this->stream) {
            // Error
            throw new SocketException("{$errstr}");
        }
        $peer = $endpoint;
        $this->onConnect($peer);
    }

    public function disconnect() {
        if ($this->stream) {
            @fclose($this->stream);
            $this->stream = null;
        }
    }

    public function write($data, $length = null) {
        if ($this->stream) {
            if ($length == null) $length = strlen($data);
            $ret = fwrite($this->stream, $data, $length);
            return $ret;
        } else
            throw new SocketException("Write operation on close socket.", self::ERR_NOT_OPEN);
    }

    public function read($length, $nonblock = false) {
        if ($this->stream) {
            if ($nonblock) $oldstate = $this->setBlocking(false);
            $data = fread($this->stream, $length);
            $this->datawaiting = (!feof($this->stream));
            if ($nonblock) $this->setBlocking($oldstate);
            return $data;
        } else
            throw new SocketException("Write operation on close socket.", self::ERR_NOT_OPEN);
    }

    public function setBlocking($blocking) {
        $cs = stream_get_meta_data($this->stream);
        $old = $cs['blocked'];
        \stream_set_blocking($this->stream,$blocking);
        return $old;
    }

    public function onDataWaiting() {
        $this->datawaiting = true;
    }

    public function onConnect($peer) {
        $this->peer = $peer;
    }

    public function onDisconnect() {

    }

    public function onError() {

    }

}

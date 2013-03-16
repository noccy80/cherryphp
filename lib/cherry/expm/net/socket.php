<?php

namespace Cherry\Expm\Net;

use debug;
use Cherry\Crypto\Uuid;
use Cherry\Expm\Stream\Context\StreamContext;

/**
 *
 *
 *
 * @license GNU General Public License (GPL) v3
 * @copyright Copyright (c) 2012-2013, NoccyLabs
 */
class Socket {

    use \Cherry\Traits\TDebug;

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
        $this->debug("Socket: Connecting to %s", $endpoint);
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
            $this->discard = true;
        }
    }

    public function write($data, $length = null) {
        if (!$this->stream) {
            $this->discard = true;
            $this->debug("Warning: {$this->uuid} - write operation on closed socket.");
            return null;
        }
            // throw new SocketException("Write operation on closed socket.", self::ERR_NOT_OPEN);
        if ($length == null) $length = strlen($data);
        $ret = fwrite($this->stream, $data, $length);
        return $ret;
    }

    public function read($length, $nonblock = false) {
        if (!$this->stream) {
            $this->discard = true;
            $this->debug("Warning: {$this->uuid} - read operation on closed socket.");
            return null;
        }
        if ($nonblock) $oldstate = $this->setBlocking(false);
        $data = fread($this->stream, $length);
        $this->datawaiting = (strlen($data)>0);
        if ($nonblock) $this->setBlocking($oldstate);
        return $data;
    }

    public function setBlocking($blocking) {
        if (!$this->stream)
            throw new SocketException("setBlocking() on closed socket.", self::ERR_NOT_OPEN);

        $cs = stream_get_meta_data($this->stream);
        $old = $cs['blocked'];
        \stream_set_blocking($this->stream,$blocking);
        return $old;
    }

    public function setCrypto($enable, $crypto=null, StreamContext $ctx=null) {
        if (!$this->stream)
            throw new SocketException("setCrypto() on closed socket.", self::ERR_NOT_OPEN);
        $bs = $this->setBlocking(true);
        if ($crypto) {
            stream_socket_enable_crypto($this->stream, $enable, $crypto);
        } else {
            stream_socket_enable_crypto($this->stream, $enable);
        }
        $this->setBlocking($bs);
    }

    /**
     * Check if the last operation timed out
     */
    public function getTimedOut() {
        if (!$this->stream)
            throw new SocketException("getTimedOut() on closed socket.", self::ERR_NOT_OPEN);
        $md = stream_get_meta_data($this->stream);
        return $md['timed_out'];
    }

    /**
     * Set stream timeout in seconds and µs.
     */
    public function setTimeout($sec,$us=0) {
        if (!$this->stream)
            throw new SocketException("setTimeout() on closed socket.", self::ERR_NOT_OPEN);
        stream_set_timeout($this->stream,$sec,$us);
    }

    public function onConnect($peer) {
        $this->peer = $peer;
    }

    public function onDisconnect() {

    }

    public function onError() {

    }

}

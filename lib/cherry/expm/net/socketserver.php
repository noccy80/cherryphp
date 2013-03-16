<?php

namespace Cherry\Expm\Net;

use debug;
use Cherry\Crypto\OpenSSL\Certificate;

/**
 *
 *
 *
 * @license GNU General Public License (GPL) v3
 * @copyright Copyright (c) 2012-2013, NoccyLabs
 */
class SocketServer {

    use \Cherry\Traits\TDebug;

    private $streams = [];
    private $stream;
    private $clients = [];
    private $sockclass = null;
    private $©ertificate = null;
    /**
     * Create a socket server
     *
     * Endpoint can be one of:
     *  - tcp://ip:port
     *  - udp://ip:port
     *  - ssl://ip:port
     *  - unix:///path/to/socket
     *  - udg:///path/to/socket
     *
     * @param string $endpoint The endpoint URI
     */
    public function __construct($endpoint=null, $socketclass='\Cherry\Expm\Net\Socket', Certificate $c = null) {
        $this->sockclass = $socketclass;
        $this->certificate = $c;
        if ($endpoint)
            $this->addListenPort($endpoint);
    }

    public function addListenPort($endpoint) {
        $errno = null; $errstr = null;
        // Create the stream
        if ($this->certificate) {
            $ctx = $this->certificate->getStreamContext();
        } else {
            $ctx = null;
        }
        $this->streams[] = \stream_socket_server($endpoint, $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $ctx);
        // Check for errors
    }

    /**
     *
     *
     * @return Array The readable sockets
     */
    public function select() {
        // No clients no work
        // Select sockets to read
        foreach($this->clients as $k=>$client) {
            if ($client->discard) {
                $this->debug("Discarding client {$client->uuid}");
                unset($this->clients[$k]);
            }
        }
        $read = array_map(function($sock){ return $sock->stream; }, $this->clients); $write = []; $except = [];
        $read = array_merge($this->streams, $read);
        if (count($read) == 0) return [];
        // Add the readable sockets to a list as Socket instances
        $sock = [];
        if (false !== \stream_select($read,$write,$except,0,10000)) {
            pcntl_signal_dispatch();
            foreach($read as $stream) {
                if (in_array($stream, $this->streams)) {
                    // Create a new Socket
                    $peer = null;
                    $asock = \stream_socket_accept($stream,0,$peer);
                    if ($asock) {
                        if (is_callable($this->sockclass))
                            $class = $this->sockclass();
                        else
                            $class = $this->sockclass;
                        $socket = new $class($asock);
                        $socket->socketserver = $this;
                        $this->clients[$socket->uuid] = $socket;
                        // Add the socket to the clients array
                        $socket->onConnect($peer);
                    }
                } else {
                    $streamid = $this->getStreamId($stream);
                    $this->debug("{$streamid}: socket read");
                    assert(array_key_exists($streamid,$this->clients));
                    $sock[$streamid] = $this->clients[$streamid];
                    $sock[$streamid]->onDataWaiting();
                }
            }
        }
        // Return the readable sockets
        return $sock;
    }

    public function each(callable $func) {
        $args = func_get_args();
        $func = array_shift($args);
        foreach($this->clients as $client) {
            $cargs = array_merge([$client],$args);
            call_user_func_array($func,$cargs);
        }
    }

    private function getStreamId($stream) {
        foreach($this->clients as $client) {
            if ($stream === $client->stream) return $client->uuid;
        }
        return null;
    }

    /**
     * Close and/or remove the socket from the client pool.
     *
     * @param Socket $socket The socket to close
     * @return bool True if successful
     */
    public function close(Socket $socket) {
        $socket->disconnect();
        // Remove socket
        $uuid = $socket->uuid;
        unset($this->clients[$uuid]);
    }

}

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
class SocketServer {

    private $stream;
    private $clients = [];
    private $sockclass = null;
    /**
     * Create a socket server
     *
     * Endpoint can be one of:
     *  - tcp://ip:port
     *  - udp://ip:port
     *  - unix:///path/to/socket
     *  - udg:///path/to/socket
     *
     * @param string $endpoint The endpoint URI
     */
    public function __construct($endpoint, $socketclass='\Cherry\Expm\Net\Socket') {
        $errno = null; $errstr = null;
        // Create the stream
        $this->stream = \stream_socket_server($endpoint, $errno, $errstr);
        $this->sockclass = $socketclass;
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
                \debug("Discarding client {$client->uuid}");
                unset($this->clients[$k]);
            }
        }
        $read = array_map(function($sock){ return $sock->stream; }, $this->clients); $write = []; $except = [];
        array_unshift($read,$this->stream);
        if (count($read) == 0) return [];
        // Add the readable sockets to a list as Socket instances
        $sock = [];
        if (false !== \stream_select($read,$write,$except,0,50000)) {
            foreach($read as $stream) {
                if ($stream === $this->stream) {
                    // Create a new Socket
                    $peer = null;
                    $asock = \stream_socket_accept($this->stream,0,$peer);
                    if ($asock) {
                        $socket = new $this->sockclass($asock);
                        $socket->socketserver = $this;
                        $this->clients[$socket->uuid] = $socket;
                        // Add the socket to the clients array
                        $socket->onConnect($peer);
                    }
                } else {
                    $streamid = $this->getStreamId($stream);
                    \debug("{$streamid}: socket read");
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

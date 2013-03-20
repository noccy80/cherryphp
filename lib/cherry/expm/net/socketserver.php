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
    private $sockclass = null;
    private $certificate = null;

    private $listeners = [];
    private $endpoints = [];
    private $clients = [];

    /**
     * Add a listener to the socket server.
     *
     * @code
     * addListener("tcp://0.0.0.0:6060", '\MyHttpTransport'); // HTTP
     * addListener("ssl://0.0.0.0:6061", '\MyHttpTransport', $cert); // HTTPS
     * addListener("unix:///var/run/myhttpd-control", '\MyControlTransport'); // Unix Domain Socket
     * @endcode
     *
     * @param mixed $endpoint The endpoint (eg. tcp://0.0.0.0:6667)
     * @param ISocketTransport $transport The transport to use
     *
     * @x-new
     * @x-replaces addListener
     * @x-replaces addListenPort
     */
    public function addListener($endpoint, SocketTransport $transport,
                                Certificate $cert = null, array $options = null) {
        // Initialize a null context
        $ctx = null;
        // If we got a certificate, let's set up an SSL context and then listen.
        if ($cert) {
            $ctx = $cert->getStreamContext();
            $server = \stream_socket_server(
                $endpoint, $errno, $errstr,
                STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $ctx);
        } else {
            // Add the listener, and start listening.
            $server = \stream_socket_server(
                $endpoint, $errno, $errstr,
                STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
        }
        if (!$server)
            throw new \Exception("Server setup failed: {$errstr} ({$errno})");
        $this->debug("Created new listener for {$endpoint} (on %s)", get_class($transport));
        // Save our instances so we can use them for select as well as to look
        // up the status.
        $this->listeners[$endpoint] = $server;
        $this->endpoints[$endpoint] = (object)[
            "socket" => $server,
            "endpoint" => $endpoint,
            "transport" => $transport,
            "clients" => 0,
            "options" => (array)$options
        ];
    }

    /**
     * Remove an endpoint from the set of listeners and stop listening on the
     * specified endpoint.
     *
     * @param mixed $endpoint The endpoint to remove from the set of active listening endpoints.
     */
    public function removeListener($endpoint) {
        // Check if the endpoint exist in the pool
        if (array_key_exists($endpoint,$this->listeners)) {
            // If so, grab it, close it and then unset it
            $lobj = $this->listeners[$endpoint];
            fclose($lobj);
            unset($this->listeners[$endpoint]);
            // Return true on success
            return true;
        }
        // If not, return false
        return false;
    }

    public function process() {
        // Get the client socket list, which also discards closed sockets.
        $clients = $this->getClientSockets();
        $sread = array_merge(array_values($this->listeners), $clients);
        if (count($sread) == 0)
            return;
        // Add the readable sockets to a list as Socket instances
        $swrite = []; $sexcept = [];
        $ssel = stream_select($sread,$swrite,$sexcept,0,10000);
        if ($ssel == 0)
            return true;
        $sread = array_unique($sread);
        // Go over our readable sockets
        foreach ($sread as $socket) {
            // Is this socket a listening socket?
            if ($this->isListeningSocket($socket)) {
                // Create a new Socket
                $peer = null;
                $asock = \stream_socket_accept($socket,0,$peer);
                // Success?
                if ($asock) {
                    $transport = $this->getNewTransportForSocket($socket);
                    $endpoint = $this->getListenerEndpoint($socket);
                    $transport->onAccept($asock,$peer,$endpoint);
                    $this->clients[$transport->getUuid()] = (object)[
                        "transport" => $transport,
                        "socket" => $asock,
                        "uuid" => $transport->getUuid()
                    ];
                }
            } else {
                $transport = $this->getClientTransport($socket);
                $transport->onDataWaiting();
            }
        }

        // Do this for each loop to update the clients if needed
        $this->each(function($client){
            $client->onProcess();
        });
        return true;
    }

    private function isListeningSocket($socket) {
        return in_array($socket,$this->listeners);
    }

    /**
     * Return a new transport for the listening sockets endpoint.
     *
     * @param mixed $socket The listening socket for the endpoint
     * @return SocketTransport The transport
     */
    private function getNewTransportForSocket($socket) {
        foreach($this->endpoints as $endpoint) {
            if ($endpoint->socket == $socket) {
                $tp = clone $endpoint->transport;
                $this->debug("<%s> Spawning transport %s", $tp->getUuid(), get_class($endpoint->transport));
                return $tp;
            }
        }
        return false;
    }

    private function getListenerEndpoint($socket) {
        foreach($this->endpoints as $name=>$endpoint) {
            if ($endpoint->socket == $socket) {
                return $name;
            }
        }
        return false;
    }

    /**
     * Retrieve the transport for the (currently connected) socket
     *
     * @param mixed $socket The socket
     */
    private function getClientTransport($socket) {
        foreach($this->clients as $client) {
            if ($client->socket == $socket) {
                return $client->transport;
            }
        }
        return false;
    }

    /**
     *
     *
     *
     */
    private function getClientSockets() {
        $read = [];
        foreach($this->clients as $k=>$client) {
            if ($client->transport->canDiscard()) {
                $this->debug("<{$client->uuid}> Discarding client");
                unset($this->clients[$k]);
            } else {
                $read[] = $client->socket;
            }
        }
        return $read;
    }

    /**
     *
     *
     */
    public function each(callable $func) {
        $args = func_get_args();
        $func = array_shift($args);
        foreach($this->clients as $client) {
            $cargs = array_merge([$client->transport],$args);
            call_user_func_array($func,$cargs);
        }
    }







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
                    // C
                    $class = $this->getNewTransportForSocket($stream);
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

<?php

namespace Cherry\Net\Socket;
use cherry\net\proxy\Proxy;

class TcpSocket extends Socket {

    protected $proxy = null;
    protected $hsocket = null;
    protected $is_secure = false;

    public function __construct($host=null,$port=null,$flags=0x00) {
        $this->flags = $flags;
        $this->host = $host;
        $this->port = $port;
    }
    public function connect() {
        if ($this->flags & SOCK_PROXY) {
            \Cherry\debug('Proxied connection, creating proxy');
            $proxy = new \cherry\net\proxy\SocksProxy('127.0.0.1',8088);
            $this->proxyConnect($proxy);
        } else {
            $this->hsocket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
        }
        $this->setState('connecting');
        if (!socket_connect($this->hsocket, $this->host, $this->port)) {
            $this->setState('error');
            throw new SocketException("Call to socket_connect failed!");
        }
        $this->setState('connected');
    }
    public function listen($bind='*',$port=null) {
        if (!$port) $port = $this->port;
        if ($this->flags & SOCK_PROXY) {
            \Cherry\debug('Proxied connection, creating proxy');
            $proxy = new \cherry\net\proxy\SocksProxy('127.0.0.1',8088);
            $this->proxyListen($proxy);
        } else {
            $this->hsocket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
            if (!socket_bind($this->hsocket, (($bind=='*')?0:$bind), $this->port)) {
                throw new SocketException("Call to socket_bind failed.");
            }
            if (!socket_listen($this->hsocket)) {
                throw new SocketException("Call to socket_listen failed.");
            }
            $this->setState('listening');
        }
    }
    private function setState($state) {
        $this->emit('statechanged',$state);
    }
    protected function proxyConnect(Proxy $proxy) {
        $this->proxy = $proxy;
        $this->hsocket = $proxy->getSocket();
    }    protected $is_proxied = false;
    public function enableEncryption($crypto_type = \STREAM_CRYPTO_METHOD_TLS_CLIENT) {

    }
}

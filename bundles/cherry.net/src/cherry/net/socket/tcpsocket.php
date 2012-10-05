<?php

namespace cherry\net\socket;
use cherry\net\proxy\Proxy;

class TcpSocket extends Socket {
    protected $proxy = null;
    protected $hsocket = null;
    protected $is_secure = false;
    public function __construct($host,$port,$flags=0x00) {
        $this->flags = $flags;
        $this->host = $host;
        $this->port = $port;
    }
    public function connect() {
        if ($this->flags & SOCK_PROXY) {
            \Cherry\debug('Proxied connection, creating proxy');
            $proxy = new \cherry\net\proxy\SocksProxy('127.0.0.1',8088);
            $this->proxyConnect($proxy);
        }
    }
    protected function proxyConnect(Proxy $proxy) {
        $this->proxy = $proxy;
        $this->hsocket = $proxy->getSocket();
    }    protected $is_proxied = false;
    public function enableEncryption($crypto_type = \STREAM_CRYPTO_METHOD_TLS_CLIENT) {

    }
}

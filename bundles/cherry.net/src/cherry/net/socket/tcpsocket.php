<?php

namespace cherry\net\socket;
use cherry\net\proxy\Proxy;

class TcpSocket extends Socket {
    protected $is_proxied = false;
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
    public function enableEncryption($crypto_type = \STREAM_CRYPTO_METHOD_TLS_CLIENT) {

    }
}

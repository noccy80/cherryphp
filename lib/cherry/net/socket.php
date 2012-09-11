<?php

namespace cherry\net\socket;
require_once 'lib/lepton/base/event.php';
require_once 'lib/cherry/net/proxy.php';
use \lepton\base\EventEmitter;
use \cherry\net\proxy\Proxy;

const SOCK_PROXY = 0x20;
const SOCK_NONBLOCKING = 0x08;
const SOCK_IPV6 = 0x10;

abstract class Socket extends EventEmitter {
    protected function proxyConnect(Proxy $proxy) {
        $this->proxy = $proxy;
        $this->hsocket = $proxy->getSocket();
    }
}

class TcpSocket extends Socket {
    protected $host;
    protected $port;
    protected $flags;
    public function __construct($host,$port,$flags=0x00) {
        $this->flags = $flags;
        $this->host = $host;
        $this->port = $port;
    }
    public function connect() {
        if ($this->flags & SOCK_PROXY) {
            \lepton\log(\lepton\LOG_DEBUG,'Proxied connection, creating proxy');
            $proxy = new \cherry\net\proxy\SocksProxy('127.0.0.1',8088);
            $this->proxyConnect($proxy);
        }
    }
}

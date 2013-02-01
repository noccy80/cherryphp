<?php

namespace Cherry\Net;

use cherry\base\Event;
use cherry\base\EventEmitter;

class SocketServer extends EventEmitter {

    /**
     * @brief Constructor.
     *
     * The constructor saves the port and bind IP, but does not set up any
     * listening sockets. To do this, call on the start() or fork() methods.
     *
     * @param Mixed $port The port to bind to (f.ex. 6667)
     * @param Mixed $bind The IP to bind to, or '*' for all
     */
    public function __construct($port,$bind='127.0.0.1') {

    }

    public function start() {
        Event::observe('posix.signal.*',array($this,'onSignal'));
        $sock = new \cherry\net\socket\TcpSocket('127.0.0.1',8000,\cherry\net\socket\SOCK_PROXY);
        $sock->connect();
        $sess = new ServerSession();
        $sess->setTransport(new \cherry\net\socket\transport\HttpTransport());
        $this->emit('ready');
    }

    public function onSignal() {

    }

}

class ServerSession {

    public function setTransport(\cherry\net\socket\transport\Transport $transport) {
        $transport->on('upgrade',array($this,'upgradeTransport'));
        $transport->initialize();
    }

    public function upgradeTransport(\cherry\net\socket\transport\Transport $transport) {
        \cherry\log(\cherry\LOG_DEBUG,'Upgrading transport for socket...');
    }

}

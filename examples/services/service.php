#!/usr/bin/php
<?php

require_once "../../share/include/cherryphp";

use Cherry\Net\SocketServer;


class ServiceExample extends ConsoleService {

    public function main() {

    }

    public function servicemain() {
        $server = new SocketServer(SocketServer::PROTO_TCP, 8088);
        $server->on('connection', [$this, 'onConnection']);
        $server->listen();
    }

}

App::run(new ServiceControl());

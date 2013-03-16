<?php

require_once "../../share/include/cherryphp";

class SocketContextApp extends Cherry\Cli\ConsoleApplication {
    public function main() {
        $str = new \Cherry\Expm\Stream\Context\SslContext();
        $str->setVerifyPeer(true);
        var_dump($str->getContextData());
    }
}

App::run(new SocketContextApp());

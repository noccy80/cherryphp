<?php

require("xenon/xenon.php");
xenon\xenon::framework("cherryphp");

class SocketContextApp extends Cherry\Cli\ConsoleApplication {
    public function main() {
        $str = new \Cherry\Expm\Stream\Context\SslContext();
        $str->setVerifyPeer(true);
        var_dump($str->getContextData());
    }
}

App::run(new SocketContextApp());

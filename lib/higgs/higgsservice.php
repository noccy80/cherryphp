<?php

namespace Higgs;

declare(ticks = 1);

use Cherry\Core\ServiceInstance;
use Cherry\Expm\Net\Socket;
use Cherry\Expm\Net\SocketServer;
use Cherry\Expm\Components;
use Cherry\Web\Response;
use Cherry\Web\Request;
use Cherry\Web\HtmlTag as h;
use Cherry\Expm\Process\CoRoutine;
use Cherry\Core\ConfigPool;
use Cherry\Crypto\OpenSSL\Certificate;

class HiggsService extends ServiceInstance {

    protected $serviceid = "com.noccy.higgs.daemon";

    private $clog = [];
    private $httplog = null;
    private $cfg = null;
    public $dataroot = null;

    public function getFlags() {
        return ServiceInstance::SVC_RESTART;
    }

    public function getHttpLogger() {
        if (!$this->httplog) $this->httplog = new \Cherry\Core\Utils\HttpdLogger();
        return $this->httplog;
    }

    function servicemain() {
        $this->cfg = ConfigPool::getPool("higgs");
        if ($this->cfg) {
            $this->dataroot = $this->cfg->spath("//httpd/dataroot")[0][0];
        }

        // Note how we pass our ServerSocketClass here
        $cert = new Certificate("server.pem");
        $this->debug("Using certificate %s", "server.pem");
        $info = $cert->getCertificateInfo();
        $this->debug("    %s", $info["name"]);

        $server = new SocketServer(null, "\\Higgs\\HttpServer", $cert);
        $server->addListenPort("tcp://127.0.0.1:9700");
        $server->addListenPort("ssl://127.0.0.1:9701");

        while($this->getState() == ServiceInstance::STA_STARTED) {
            pcntl_signal_dispatch();
            // Go over the sockets that are ready to read
            foreach($server->select() as $sock) {
                $sock->onDataWaiting();
                if ($sock->discard)
                    $server->close($sock);
            }
            // Do this for each loop
            $server->each(function($client){
                $client->onTick();
            });

        }
    }
    function servicehalt() {
    }
}

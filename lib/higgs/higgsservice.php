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
    private $stop = false;

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
        list($vfrom,$vto) = $cert->getValidity();
        $this->debug("    Valid from: %s", $vfrom);
        $this->debug("    Valid until: %s", $vto);
        if ($cert->isSelfSigned()) {
            $this->warn("Warning! The certificate in use is self-signed. Consider getting a proper certificate for production use.");
        }

        /*
        $server = new SocketServer(null, "\\Higgs\\HttpServer", $cert);
        $server->addListenPort("tcp://127.0.0.1:9700");
        $server->addListenPort("ssl://127.0.0.1:9701");
        */

        // Set up the httpd. Will be cloned for each new instance.
        $http = new \Higgs\HttpServer();

        $server = new SocketServer();
        $server->addListener("tcp://127.0.0.1:9700", $http);
        $server->addListener("ssl://127.0.0.1:9701", $http, $cert);
        while($server->process()) {
            usleep(5000);
            if ($this->stop) break;
        }
    }
    function servicehalt() {
        $this->stop = true;
    }
}

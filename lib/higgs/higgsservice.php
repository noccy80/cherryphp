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

use Cherry\Core\ObjectManager as ObjMan;
use Cherry\Core\ConfigManager as CfgMan;

CfgMan::register();
CfgMan::bind("higgs","httpd.sdl");

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

        // Set up the httpd. Will be cloned for each new instance.
        $http = new \Higgs\HttpServer();
/*
        $http->addExtension(new \Higgs\Extensions\Misc\AddHeader([
            "header" => "x-foo",
            "value" => "Hello World"
        ]));
*/
        $cfg = ObjMan::getObject("local:/config/higgs");

        $exts = $cfg->query("/httpd/server[default]/extension");
        foreach($exts as $ext) {
            $cn = \Utils::getClassFromDotted($ext[0]);
            class_exists($cn);
        }
        foreach($exts as $ext) {
            $cn = \Utils::getClassFromDotted($ext[0]);
            if (class_exists($cn)) {
                $ext = new $cn($ext->getAttributes());
                $http->addExtension($ext);
            } else {
                $this->warn("Could not load extension '{$ext[0]}'");
            }
        }

        //$ctrl = new \Higgs\HttpControl();

        $server = new SocketServer();

        $ports = $cfg->query("/httpd/server[default]/listen");
        foreach($ports as $ep) {
            $endpoint = $ep[0];
            if ($ep->hasAttribute("certificate")) {
                $cert = new Certificate("server.pem");
                $this->debug("Using certificate %s", "server.pem");
                $info = $cert->getCertificateInfo();
                list($vfrom,$vto) = $cert->getValidity();
                $this->debug("    Issued to:    %s", $info["name"]);
                $this->debug("    Issuer:       %s (%s)", $info["issuer"]["O"], $info["issuer"]["OU"]);
                $this->debug("    Hash:         0x%s", $info["hash"]);
                $this->debug("    Valid from:   %s", $vfrom);
                $this->debug("    Valid until:  %s", $vto);
                if ($cert->isSelfSigned()) {
                    $this->warn("Warning! The certificate in use is self-signed. Consider getting a proper certificate for production use.");
                    $this->warn("HSTS by design does not allow self-signed certificates. Enabling HSTS will not work.");
                }
                $server->addListener($endpoint, $http, $cert);
            } else {
                $server->addListener($endpoint, $http);
            }
        }
        /*
        $server->addListener("tcp://127.0.0.1:9700", $http);
        $server->addListener("ssl://127.0.0.1:9701", $http, $cert);
        $server->addListener("tcp://127.0.0.1:9799", $http);
        */
        while($server->process()) {
            usleep(5000);
            if ($this->stop) break;
        }
    }
    function servicehalt() {
        $this->stop = true;
    }
}

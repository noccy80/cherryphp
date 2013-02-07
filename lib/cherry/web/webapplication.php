<?php

namespace Cherry\Web;

use App;
use Cherry\Application;
use Cherry\Mvc\Request;
use Cherry\Mvc\Response;
use Cherry\Base\PathResolver;
use Cherry\Data\Ddl\SdlTag;

abstract class WebApplication extends Application {

    protected $config;
    protected $request;
    protected $response;

    public function loadConfig() {
        $cfg = PathResolver::getInstance()->getPath("{APP}/config.sdl");
        $this->debug("WebApplication: Looking for %s", $cfg);
        if (file_exists($cfg)) {
            $this->debug("WebApplication: Reading configuration...");
            $config = new SdlTag("root");
            $config->decode(file_get_contents($cfg));
            $this->config = $config;
        }
    }

    public function __construct() {
        $this->setLogTarget([]);
        parent::__construct();
        $this->loadConfig();
        App::extend('router', new \Cherry\Mvc\Router());
        App::extend('server', new \Cherry\Mvc\Server());
    }

    public function run() {
        $this->request = new Request();
        $this->response = new Response($this->request->getProtocol());
        return $this->onRequest();
    }

    abstract protected function onRequest();

    protected function send404() {
        header("HTTP/1.1 404 Not Found.", true, 404);
        echo "The requested resource could not be found.";
    }

}

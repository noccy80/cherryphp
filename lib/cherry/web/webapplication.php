<?php

namespace Cherry\Web;

use App;
use Cherry\Application;
use Cherry\Web\Request;
use Cherry\Web\Response;
use Cherry\Base\PathResolver;
use Cherry\Data\Ddl\SdlTag;

abstract class WebApplication extends Application {

    protected $config;
    protected $request;
    protected $response;

    public function loadConfig() {
        $cfg = PathResolver::getInstance()->getPath("{APP}/config/application.sdl");
        if (file_exists($cfg)) {
            $this->debug("WebApplication: Reading %s", $cfg);
            $config = new SdlTag("root");
            $config->decode(file_get_contents($cfg));
            $this->config = $config;
        } else {
            $this->debug("WebApplication: Could not find %s", $cfg);
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
        $this->response = new Response();
        if (!empty($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        } elseif (!empty($_ENV['URI'])) {
            $uri = $_ENV['URI'];
        } else {
            $uri = "/";
        }

        return $this->onRequest($uri);
    }

    abstract protected function onRequest($uri);

    protected function send404() {
        header("HTTP/1.1 404 Not Found.", true, 404);
        echo "The requested resource could not be found.";
    }

}

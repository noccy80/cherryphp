<?php

namespace cherry\Mvc;

abstract class Controller {
    protected
            $request = null,
            $response = null,
            $document = null,
            $so = null;

    public function __construct(Request $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
        if (defined('IS_PROFILING'))
            $this->so = \App::profiler()->enter('Controller');
    }

    public function __destruct() {
        if (defined('IS_PROFILING')) {
            \App::profiler()->log("Destroying Controller observer");
            unset($this->so);
        }
    }

    public function invoke($action,$args) {
        if (defined('IS_PROFILING'))
            \App::profiler()->log('Invoking controller');
        // Begin the document, and assign it as the response document
        $this->document = Document::begin(Document::DT_HTML5,'en-us','UTF-8');
        $this->response->setDocument($this->document);
        $this->setup();
        $handler = [$this,$action.'Action'];
        if (is_callable($handler)) {
            call_user_func_array($handler, $args);
            $this->response->output();
        } else {
            $this->show_404();
        }
        //$this->indexAction($doc);
        // Output the document
    }
    abstract function setup();
    protected function show_404() {
        $this->response->send404($this->request->getUri());
    }
}

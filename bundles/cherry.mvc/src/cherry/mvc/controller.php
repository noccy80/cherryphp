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
    public function invoke($action,$args) {
        if (defined('IS_PROFILING'))
            $_so = \App::profiler()->enter('Invoking controller');
        // Begin the document, and assign it as the response document
        $this->document = Document::begin(Document::DT_HTML5,'en-us','UTF-8');
        $this->response->setDocument($this->document);
        $this->setup();
        call_user_func_array([$this,$action.'Action'], $args);
        //$this->indexAction($doc);
        // Output the document
        $this->response->output();
    }
    abstract function setup();
    protected function show_404() {
        $this->response->send404();
    }
}

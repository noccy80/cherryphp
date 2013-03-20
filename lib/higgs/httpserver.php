<?php

namespace Higgs;

class HttpServer extends \Cherry\Expm\Net\Transport\HttpTransport {

    protected function onHttpRequest() {
        // Set up some generics
        $this->response->server = "Cherry Higgs/1.0.0";
        $this->response->connection = "Close";
        // Prepare the response
        $html = $this->request->asHtml().
                $this->response->asHtml();
        // Set the content of the response
        $this->response->setContent($html);
        // onHttpRequest must return true for the response to be automatically
        // sent. If you want to handle the response all by yourself, make sure
        // to return false.
        return true;
    }

}

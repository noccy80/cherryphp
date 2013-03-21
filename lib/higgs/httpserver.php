<?php

namespace Higgs;

class HttpServer extends \Cherry\Expm\Net\Transport\HttpTransport {

    /**
     * Set the document root. Pass null to disable serving of static content.
     */
    public function setDocumentRoot($root) {
    
    }

    protected function onHttpRequest() {
        // Set up some generics
        $this->response->server = "Higgs";
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

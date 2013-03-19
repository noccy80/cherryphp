<?php

namespace Higgs;

class HttpServer extends \Cherry\Expm\Net\Transport\HttpTransport {

    protected function onHttpRequest() {
        $this->response->setContent($this->request->asHtml());
        // onHttpRequest must return true for the response to be automatically
        // sent.
        return true;
    }

}

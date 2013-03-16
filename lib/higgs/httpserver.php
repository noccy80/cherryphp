<?php

namespace Higgs;

class HttpServer extends \Cherry\Expm\Net\Transport\HttpTransport {

    use \Cherry\Traits\TDebug;

    protected function onRequest() {
        $this->response->setContent($this->request->asHtml());
    }

}

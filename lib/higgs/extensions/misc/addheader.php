<?php

namespace Higgs\Extensions\Misc;

use Higgs\Extension;

/**
 * This extension adds a custom header to the response. Set it up with the keys
 * "header" and "value" to define the header to be added.
 *
 */
class AddHeader extends Extension {
    public function __construct($opts) {
        $this->name = $opts["header"];
        $this->value = $opts["value"];
    }
    public function attach($obj) {
        $obj->on("higgs.httpd.onrequest", [$this,"onRequest"]);
    }
    public function onRequest($e) {
        $this->debug("Setting header {$this->name} to '{$this->value}'");
        $e->data->response->setHeader($this->name, $this->value);
    }
}


<?php

namespace Higgs\Extensions\Rpc;

use Higgs\Extension;

/**
 * This extension adds a custom header to the response. Set it up with the keys
 * "header" and "value" to define the header to be added.
 *
 */
class XmlRpc extends Extension {
    public function __construct($opts) {

    }
    public function attach($obj) {
        $obj->on("higgs.httpd.onrequest", [$this,"onRequest"]);
    }
    public function onRequest($e) {
        $this->debug("Request is not an XmlRpc request.");
    }
}


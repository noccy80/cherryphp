<?php

namespace Higgs\Extensions\Auth;

use Higgs\Extension;

class Basic extends Extension {
    public function attach($obj) {
        $obj->on("higgs.httpd.onrequest",[$this,"onRequest"]);
    }
    public function detach($obj) { }
    public function onRequest($e) {
        $req = $e->data->request;
        $resp = $e->data->response;
        if ($req->meta["higgs.protected"]) {
            if ($req->auth) {
                // check auth and return if ok, passing the event on
                
            }
            $resp->clearContent();
            $resp->setContent("Authorization required", 403);
            $e->yield("higgs.httpd.response.return");
    }
}

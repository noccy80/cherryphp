<?php

namespace Higgs;

abstract class Extension {

    use \Cherry\Traits\TDebug;
    use \Cherry\Traits\TUuid;   

    abstract function attach($obj);
    
}

/*
class HttpTransport {
    use TExtensionHost;
    use TEventEmitter;
    ...
    const EVT_ONREQUEST = "higgs.httpd.onrequest";
    function onRequestReceived() {
    
        $this->emit(HttpTransport::EVT_ONREQUEST)
    }
}

// Transport
$t = new HttpTransport();
// This extension adds a header to every response
$ext = new AddHeader([
    "header" => "x-hello",
    "value" => "Hello World"
]);
// Add the extension (it will be attached when the transport is setup
$t->addExtension($ext);
// Add basic http auth
$t->addExtension(new Basic([
    "realm" => "Higgs",
    // get the system auth db
    "authsource" => ObjMan::get("local://auth/default")
]));


abstract class AuthSource implements Iterator {
    public abstract function testPassword($user,$pass);
    public abstract function setPassword($user,$pass)
}

*/

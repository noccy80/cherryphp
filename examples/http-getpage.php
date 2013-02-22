<?php

define("XENON","cherryphp/trunk");
require_once("xenon/xenon.php");



class Hello extends Cherry\Cli\ConsoleApplication {

    public function main() {

        $req = new Cherry\Net\Http\HttpRequest();
        $req->on(Cherry\Net\Http\HttpRequest::ON_CACHEHIT,function($e) {
            echo "Request hit cache.\n";
        });
        $req->open("GET","http://www.google.com");
        $req->send();
        
        echo strlen($req->getResponseText())."\n";

    }

}

App::run(new Hello());

<?php

require_once "PHPUnit/Autoload.php";
// use Cherry\CherryUnit\TestCase;

//require_once "lib/cherry/data/ddl/SdlTag.php";

use Cherry\Web\Request;

class WebRequestTestCase extends \PHPUnit_Framework_TestCase {

    public function setup() { }
    public function teardown() { }

    public function testCreateRequestFromFullString() {
        $r = new Request();
        $r->createFromString("GET / HTTP/1.1\r\nserver: localhost\r\nuser-agent: phpunit\r\n\r\n");
        $this->assertTrue($r->isRequestComplete());
        $this->assertEquals($r->getRequestUrl(),"/");
        $this->assertEquals($r->getRequestMethod(),"GET");
        $this->assertEquals($r->getRequestProtocol(),"HTTP/1.1");
        $this->assertEquals($r->getHeader("server"),"localhost");
        $this->assertEquals($r->getHeader("user-agent"),"phpunit");
    }

    public function testRequestAsText() {

    }

    public function testRequestAsHtml() {

    }

    public function testRequestHeaders() {
        
    }

}

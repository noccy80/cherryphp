<?php

define("XENON","cherryphp/trunk");
require_once("xenon/xenon.php");

class DbTest extends \Cherry\Cli\ConsoleApplication {

    function main() {
        $db = \Cherry\Database\DatabaseConnection::getInstance("mysql://root@localhost");
    }

}

App::run(new DbTest());

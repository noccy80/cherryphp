<?php

define("XENON","cherryphp/trunk");
require("xenon/xenon.php");

$password = "secretpassword";

use Cherry\Database\DatabaseConnection;

class testapp extends Cherry\Cli\ConsoleApplication {
    function main() {
        global $password;
        $db = DatabaseConnection::getInstance("mysql://testuser:{$password}@localhost/testdb");
        $q = $db->query("SELECT * FROM blog;");
        foreach($q as $row) {
            printf("%s: %s\n", $row['postdate'], $row['title']);
        }
    }
}

App::run(new testapp());

echo "The password is: {$password}\n";

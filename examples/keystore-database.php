<?php

define("XENON","cherryphp/trunk");
require_once("xenon/xenon.php");

/**
 * WARNING! Never ever save the root password in the keystore unless you got
 * a really really really really good reason to do so. With enough effort
 * everything is crackable, and there is no need for unneccesary auth tokens
 * to be laying around.
 *
 * If you really have to store your root password somewhere, either mount the
 * keystore on demand, or put it in the system keystore (and omit the system
 * keystore password for all but the most necessary tasks. You do however
 * need to keep your keystore passwords safe as well.
 *
 * You should also consider envvar readability. On linux systems, only the
 * owner of the process can read the environment, so isolating daemons into
 * separate users is considered a good thing.
 */
class DbTest extends \Cherry\Cli\ConsoleApplication {

    function main() {
        // No password here makes the keystore be queried. For more info
        // try running this script with the envvar DEBUG set to 1.
        $db = \Cherry\Database\DatabaseConnection::getInstance("mysql://root@localhost");
    }

}

App::run(new DbTest());

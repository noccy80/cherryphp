<?php

class App {

    private static $_apps = [];

    public function __construct() {
        user_error("The App class is intended to be called static.");
    }

    public static function run($app) {
        global $argv;
        self::$_apps[] = $app;
        \Cherry\debug("Running application class %s, total of %d apps on stack after.", get_class($app),count(self::$_apps));
        $ev = $app->run($argv);
        array_shift(self::$_apps);
        \Cherry\debug("Application %s returned code %d after exit.", get_class($app),$ev);
        return $ev;
    }

    public static function instance() {
        return end(self::$_apps);
    }

}

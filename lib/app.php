<?php

class App {

    private static $_apps = [];
    private static $_context = null;
    private static $_config = null;

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

    public static function app() {
        return end(self::$_apps);
    }

    public static function config() {
        if (!self::$_config) {
            self::$_config = new AppConfig();
            self::$_config->addConfiguration([
                '/etc/cherryphp/global.json',
                getenv('HOME').'/.cherryphp/user.json'
            ]);
        }
        return self::$_config;
    }

    public static function context() {
        if (!self::$_context) self::$_context = new AppContext();
        return self::$_context;
    }

    public static function bundles() {
        return \Cherry\BundleManager::getInstance();
    }

}

class AppConfig {

    private $cfg = [];

    /**
     * @brief Query a configuration key.
     *
     * The keys are expanded from the structure of the json configuration file.
     * If you have got the following:
     *
     *     { "foo": { "bar": [ "baz" ] }}
     *
     * You can get the array by querying foo.bar:
     *
     *     App::config()->query('foo.bar');
     *
     * A default value can be provided to be returned if the key can not be
     * found.
     *
     * @param string $key The key
     * @param string $default Default value
     * @return Mixed The unmodified value of the key
     */
    function get($key,$default=null) {
        new \Cherry\ScopeTimer("Key fetch {$key}");
        \Cherry\debug("Config::get {$key}");
        $base = $this->cfg;
        $keyseg = explode('.',$key);
        while (($key = array_shift($keyseg))) {
            $hit = false;
            if (is_array($base)) {
                if (!array_key_exists($key,$base))
                    return $default;
                $base = $base[$key];
            } elseif (is_object($base)) {
                if (!isset($base->{$key}))
                    return $default;
                    $base = $base->{$key};
            }
        }
        return $base;
    }

    public function addConfiguration($file) {
        if (is_array($file)) {
            foreach($file as $f) {
                $this->addConfiguration($f);
            }
        } else {
            if (file_exists($file)) {
                \Cherry\debug("Attempting to load configuration file %s.", $file);
                $cfg = json_decode(file_get_contents($file));
                if (($err = json_last_error())) {
                    switch($err) {
                        case JSON_ERROR_DEPTH: $msg='Maximum stack depth exceeded'; break;
                        case JSON_ERROR_SYNTAX: $msg = 'Syntax error'; break;
                        case JSON_ERROR_UTF8: $msg = 'Malformed UTF8 character'; break;
                        case JSON_ERROR_SYNTAX: $msg = 'Syntax error'; break;
                        case JSON_ERROR_CTRL_CHAR: $msg = 'Malformatted control character'; break;
                        case JSON_ERROR_STATE_MISMATCH: $msg = 'State mismatch'; break;
                        default: $msg = 'Unknown error'; break;
                    }
                    user_error("{$msg} ({$err}) while parsing configurationn {$file}");
                }
                $this->cfg = array_merge_recursive($this->cfg, (array)$cfg);
            } else {
                \Cherry\debug("Configuration file %s: File not found", $file);
            }
        }
    }

    private function apply($config) {


        foreach((array)$config as $k=>$v) {
            $kk = ($base?$base.'.'.$k:$k);
            if (is_object($v)) {
                $this->apply((array)$v,$kk);
            } else {
                $this->cfg[$kk] = $v;
            }
        }

    }

}

class AppContext {

    private $context = [];

    public function __construct() {
        if (file_exists('.context'))
            $this->context = (array)json_decode(file_get_contents('.context'));
    }

    public function __destruct() {
        file_put_contents('.context',json_encode($this->context));
    }

    public function __get($key) {
        if (array_key_exists($key,$this->context))
            return $this->context[$key];
        return null;
    }

    public function __set($key,$value) {
        $this->context[$key] = $value;
    }

    public function __isset($key) {
        return (array_key_exists($key,$this->context));
    }

    public function __unset($key) {
        unset($this->context[$key]);
    }

}

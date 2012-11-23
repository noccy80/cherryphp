<?php

namespace Cherry\Cli;

abstract class ConsoleAdapter {

    const CLASS_DEFAULT = null;
    const CLASS_ERROR = 1;
    const CLASS_WARNING = 2;

    protected $errorfifo = null;

    public function __construct() {
        $this->errorfifo = new \Data\FifoQueue(250);
    }
    
    public function __invoke() {
        $args = func_get_args();
        $out = call_user_func_array('\sprintf',$args);
        $this->putMessage($out,self::CLASS_DEFAULT);
    }

    function write() {
        $args = func_get_args();
        $out = call_user_func_array('\sprintf',$args);
        $this->putMessage($out,self::CLASS_DEFAULT);
    }

    function error() {
        $args = func_get_args();
        $out = call_user_func_array('\sprintf',$args);
        $this->putMessage($out,self::CLASS_ERROR);
        $this->errorfifo->push($out);
    }
    //abstract function read();
    //abstract function readLine();

    public function getErrorFifo() {
        return $this->errorfifo;
    }

    abstract protected function putMessage($string, $msgclass=null);



}

class Console {

    const ADAPTER_BEST = NULL;
    private static $adapter = null;

    static function getAdapter($adapter=self::ADAPTER_BEST) {

        if (!self::$adapter) {
            if ($adapter != null) {
                $objadapter = new $adapter;
            } else {
                if (php_sapi_name() == 'cli-server')
                    $objadapter = new \Cherry\Cli\Adapters\NullConsole();
                elseif (_IS_LINUX) {
                    if (getenv("ANSI")=='1') {
                        \Cherry\Log(\Cherry\LOG_DEBUG,"Console: ANSI envvar is 1, enabling ANSI.");
                        $objadapter = new \Cherry\Cli\Adapters\AnsiConsole();
                    } elseif (getenv("NOANSI")=='1') {
                        \Cherry\Log(\Cherry\LOG_DEBUG,"Console: NOANSI envvar is 1. Falling back on simple adapter.");
                        $objadapter = new \Cherry\Cli\Adapters\SimpleConsole();
                    } else {
                        $out = null; $ret = 0;
                        exec('tty',$out,$ret);
                        if ($ret == 0) {
                            \Cherry\Log(\Cherry\LOG_DEBUG,"Console: Terminal seems to be a TTY, enabling ANSI.");
                            $objadapter = new \Cherry\Cli\Adapters\AnsiConsole();
                        } else {
                            \Cherry\Log(\Cherry\LOG_DEBUG,"Console: No TTY. Falling back on simple adapter.");
                            $objadapter = new \Cherry\Cli\Adapters\SimpleConsole();
                        }
                    }
                } elseif (_IS_WINDOWS) {
                    $objadapter = new \Cherry\Cli\Adapters\SimpleConsole();
                }
            }
            self::$adapter = $objadapter;
        }
        return self::$adapter;

    }

}

<?php

namespace Cherry;

const LOG_DEBUG = 0x01;

class DebugLog {

    protected static $fifo = null;
    protected static $hlog = null;

    static private function initqueue() {
        if (empty(self::$fifo) && class_exists('\Data\FifoQueue')) {
            $loglen = intval((getenv('LOG_LENGTH')!='')?getenv('LOG_LENGTH'):10);
            self::$fifo = new \Data\FifoQueue($loglen);
        }
    }

    static function log($type,$fmt,$args=null) {
        self::initqueue();
        $arg = func_get_args();
        $fmts = array_slice($arg,1);
        $so = call_user_func_array('sprintf',$fmts);
        if (self::$fifo) self::$fifo->push($so);
        if (self::$hlog) fputs(self::$hlog,$so."\n");
        if (($type == LOG_DEBUG) && (getenv('DEBUG') == 1)) {
            fputs(STDERR,$so."\n");
        } elseif ($type != LOG_DEBUG) {
            fputs(STDOUT,$so."\n");
        }
    }

    static function openLog($logfile,$append=true) {
        self::$hlog = fopen($logfile,($append)?'a+':'w+');
    }

    static function getDebugLog() {
        self::initqueue();
        return self::$fifo->popAll();
    }

}

if (getenv('DEBUG_LOGFILE')) {
    DebugLog::openLog(getenv('DEBUG_LOGFILE'),false);
}

function getLineInfo(array $btr) {
    $fn = (!empty($btr['file']))?$btr['file']:null;
    $fl = (!empty($btr['line']))?$btr['line']:'??';
    $fnp = explode(_DS_,$fn);
    if (count($fnp)>3) {
        $fnp = array_splice($fnp,-3);
        array_unshift($fnp,'...');
        $fn = join(_DS_,$fnp);
    }
    if (!empty($fn)) {
        $ol = $fn.':'.$fl;
        return $ol;
    } else {
        return null;
    }
}

function log($type,$fmt,$args=null) {
    $args = func_get_args();
    $bt = debug_backtrace();
    if (count($bt)>0) {
        $ol = getLineInfo($bt[0]);
        if ($ol) $args[1] = $args[1].' (at '.$ol.')';
    }
    call_user_func_array(array('\Cherry\DebugLog','log'),$args);
}
function debug($fmt,$args=null) {
    $args = func_get_args();
    array_unshift($args,LOG_DEBUG);
    $bt = debug_backtrace();
    if (count($bt)>0) {
        $ol = getLineInfo($bt[0]);
        if ($ol) $args[1] = $args[1].' (at '.$ol.')';
    }
    call_user_func_array(array('\Cherry\DebugLog','log'),$args);
}


class Debug {

    static function getBacktrace($trim = 0) {

        $bt = debug_backtrace();
        $bt = array_slice($bt,$trim+1);
        return self::makeBacktrace($bt);

    }

    static function makeBacktrace($bt) {

        $bt = array_reverse($bt);

        $fid = 0;
        $out = array();
        foreach($bt as $frame) {
            $fid++;
            if (!empty($frame['args'])) {
                $argout = array();
                foreach($frame['args'] as $arg) {
                    if (is_bool($arg)) {
                        $argout[] = ($arg)?'true':'false';
                    } elseif (is_object($arg)) {
                        $argout[] = get_class($arg);
                    } elseif (is_array($arg)) {
                        $argout[] = 'array';
                    } elseif (is_string($arg)) {
                        $argout[] = "'".$arg."'";
                    } else {
                        $argout[] = $arg;
                    }
                }
                $argstr = join(',',$argout);
            } else {
                $argstr = '';
            }
            if (!empty($frame['file'])) {
                if (!empty($frame['line'])) {
                    $fileline = sprintf('[%s:%d]',$frame['file'],$frame['line']);
                } else {
                    $fileline = sprintf('[%s]',$frame['file']);
                }
            } else {
                $fileline = '';
            }
            if(empty($frame['type'])) $frame['type'] = '';
            switch($frame['type']) {
                case '::':
                case '->':
                    $out[] = sprintf('#%d. %s%s%s(%s) %s',$fid,$frame['class'],$frame['type'],$frame['function'],$argstr,$fileline);
                    break;
                default:
                    $out[] = sprintf('#%d. %s(%s) %s',$fid,$frame['function'],$argstr,$fileline);
                    break;
            }
        }
        return $out;

    }

    static function getCodePreview($file,$line) {

        $lines = explode("\n",file_get_contents($file));
        $linepart = array_slice($lines,$line-5,9);
        $lineout = array();
        for($n = 0; $n < count($linepart); $n++) {
            $lineout[] = sprintf('%-3s%5d | %s',($n == 4)?'=>':'',$line - 4 + $n,$linepart[$n]);
        }
        return $lineout;

    }

    static function getTimeStamp() {

        return date('D d M h:i:s');

    }

    static function getDebugLog() {

        // return Logger::getBuffer(Logger::BUFFER_DEBUG);

    }


}

class ErrorHandler {
    private static $oldhandler = null;
    public static function register() {
        self::$oldhandler = set_error_handler(array(__CLASS__,'__php_handleError'), E_ALL);
        \set_exception_handler(array(__CLASS__,'__php_handleException'));
        // Active assert and make it quiet
        assert_options(ASSERT_ACTIVE, 1);
        assert_options(ASSERT_WARNING, 0);
        assert_options(ASSERT_QUIET_EVAL, 1);
        assert_options(ASSERT_CALLBACK, array(__CLASS__,'__php_handleAssert'));
    }
    private static function showError($ca,$type,$message,$file,$line,$log,$bt) {
        if (function_exists('ncurses_end')) @ncurses_end();
        $ca->error("\033[1m%s:\033[22m\n    %s\n",$type,$message);
        $ca->error("\033[1mSource:\033[22m\n    %s (line %d)\n",$file,$line);
        $ca->error("%s\n",join("\n",self::indent(Debug::getCodePreview($file,$line),4)));
        $ca->error("\033[1mBacktrace:\033[22m\n%s\n", join("\n",self::indent($bt,4)));
        $ca->error("\033[1mDebug log:\033[22m\n%s\n",join("\n",self::indent($log,4)));
        $ca->error("(Hint: Change the LOG_LENGTH envvar to set the size of the debug log buffer)\n");
    }
    public static function __php_handleError($errno,$errstr,$file,$line,$errctx) {

        if ($errno & E_WARNING) {
            //fprintf(STDERR,"Warning: %s [from %s:%d]\n", $errstr,$errfile,$errline);
            \Cherry\debug("Warning: %s [from %s:%d]\n", $errstr,$file,$line);
            return true;
        }
        if ($errno & E_DEPRECATED) {
            //fprintf(STDERR,"Deprecated: %s [from %s:%d]\n", $errstr,$errfile,$errline);
            return true;
        }

        \Cherry\debug("Fatal error %s in %s on line %d", $errstr, $file, $line);
        $log = DebugLog::getDebugLog();
        $ca = \Cherry\Cli\Console::getAdapter();
        $bt = Debug::getBacktrace(1);
        self::showError($ca,'Error',$errstr.' ('.$errno.')',$file,$line,$log,$bt);

        exit(1);

    }
    public static function __php_handleException(\Exception $exception) {

        \Cherry\debug("Unhandled exception %s in %s on line %d", get_class($exception), $exception->getFile(), $exception->getLine());
        $log = DebugLog::getDebugLog();
        $ca = \Cherry\Cli\Console::getAdapter();

        $bt = Debug::makeBacktrace($exception->getTrace());
        $errfile = $exception->getFile();
        $errline = $exception->getLine();
        self::showError($ca,'Exception',$exception->getMessage().' ('.$exception->getCode().')',$errfile,$errline,$log,$bt);

        exit(1);
    }
    // Create a handler function
    public static function __php_handleAssert($file, $line, $code, $desc = null) {
        \Cherry\debug("Assertion failed in %s on line %d", $file, $line);
        $log = DebugLog::getDebugLog();
        $ca = \Cherry\Cli\Console::getAdapter();

        $str = sprintf("in %s on line %d",$file, $line );
        $bt = Debug::getBacktrace(1);
        self::showError($ca,'Assertion failed',$str,$file,$line,$log,$bt);

        exit(1);
    }
    private static function indent(array $arr, $indent) {
        $arro = array();
        foreach($arr as $row) {
            $arro[] = str_repeat(" ",$indent).$row;
        }
        return $arro;
    }
}

ErrorHandler::register();

<?php

namespace Cherry;

const LOG_DEBUG = 0x01;

class DebugLog {

    protected static $fifo = null;
    protected static $hlog = null;

    static private function initqueue() {
        if (empty(self::$fifo) && class_exists('\Cherry\Types\Queue\FifoQueue')) {
            $loglen = intval((getenv('LOG_LENGTH')!='')?getenv('LOG_LENGTH'):10);
            self::$fifo = new \Cherry\Types\Queue\FifoQueue($loglen);
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
            if (defined('STDERR'))
                fputs(STDERR,$so."\n");
        } elseif ($type != LOG_DEBUG) {
            if (defined('STDOUT'))
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
    $cn = (!empty($btr['class']))?$btr['class']:null;
    $ct = (!empty($btr['type']))?$btr['type']:'··';
    $cm = (!empty($btr['function']))?$btr['function']:null;
/*
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
*/
    $ol = '['.$cn.$ct.$cm.'()]';
    return $ol;
}

function log($type,$fmt,$args=null) {
    $args = func_get_args();
    if (DEBUG_VERBOSE) {
        $bt = debug_backtrace();
        if (count($bt)>1) {
            $ol = getLineInfo($bt[1]);
            if ($ol) $args[1] = $ol.' '.$args[1];
        }
    }
    call_user_func_array(array('\Cherry\DebugLog','log'),$args);
}
function debug($fmt,$args=null) {
    $args = func_get_args();
    array_unshift($args,LOG_DEBUG);
    if (DEBUG_VERBOSE) {
        $bt = debug_backtrace();
        if (count($bt)>1) {
            $ol = getLineInfo($bt[1]);
            if ($ol) $args[1] = $ol.' '.$args[1];
        }
    }

    call_user_func_array(array('\Cherry\DebugLog','log'),$args);
}


class Debug {

    static function getBacktrace($trim = 0) {

        $bt = debug_backtrace();
        $bt = array_slice($bt,$trim+1);
        return self::makeBacktrace($bt);

    }

    static function makeBacktrace($bt,$ansi=false) {

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
                    $fmt = ($ansi)?"\033[2m#%d.\033[0m %s%s%s(%s) %s":"#%d. %s%s%s(%s) %s";
                    $out[] = sprintf($fmt,$fid,$frame['class'],$frame['type'],$frame['function'],$argstr,$fileline);
                    break;
                default:
                    $fmt = ($ansi)?"\033[2m#%d.\033[0m %s(%s) %s":"#%d. %s(%s) %s";
                    $out[] = sprintf($fmt,$fid,$frame['function'],$argstr,$fileline);
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
            $lineout[] = sprintf("%-3s\033[2m%05d\033[0m ¦ %s%s\033[0m",($n == 4)?"=>":'',$line - 4 + $n,($n==4)?"\033[1;7m":"",$linepart[$n]."  ");
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

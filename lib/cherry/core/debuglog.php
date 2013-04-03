<?php

namespace Cherry\Core;

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

    static function log($fmt,$args=null) {
        self::initqueue();
        $arg = func_get_args();
        if (count($arg)>1) {
            $fmts = array_slice($arg,1);
            $so = call_user_func_array('sprintf',$fmts);
        } else {
            $o = $arg[0];
        }
        if (self::$fifo) self::$fifo->push($so);
        if (self::$hlog) fputs(self::$hlog,$so."\n");
        if (getenv('DEBUG') == 1) {
            if (defined('STDERR'))
                fputs(STDERR,$so."\n");
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
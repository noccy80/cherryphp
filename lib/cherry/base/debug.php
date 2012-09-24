<?php

namespace Cherry;

const LOG_DEBUG = 0x01;

function log($type,$fmt,$args=null) {
    $arg = func_get_args();
    $fmts = array_splice($arg,1);
    $so = call_user_func_array('sprintf',$fmts);
    if (($type == LOG_DEBUG) && (getenv('DEBUG') == 1)) {
        fputs(STDERR,$so."\n");
    } elseif ($type != LOG_DEBUG) {
        fputs(STDOUT,$so."\n");
    }
}


class Debug {
    
    static function getBacktrace($trim = 0) {

        $bt = debug_backtrace();
        $bt = array_slice($bt,$trim+1);
        $bt = array_reverse($bt);
        
        $fid = 0;
        $out = array();
        foreach($bt as $frame) {
            $fid++;
            $argstr = '..';
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
                    $out[] = sprintf('%d> %s%s%s(%s) %s',$fid,$frame['class'],$frame['type'],$frame['function'],$argstr,$fileline);
                    break;
                default:
                    $out[] = sprintf('%d> %s(%s) %s',$fid,$frame['function'],$argstr,$fileline);
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
    
    static function getDebugLog() {
        
        // return Logger::getBuffer(Logger::BUFFER_DEBUG);
        
    }
    
    
}

class ErrorHandler {
    private static $oldhandler = null;
    public static function register() {
        self::$oldhandler = set_error_handler(array(__CLASS__,'__php_handleError'), E_ALL);
    }
    public static function __php_handleError($errno,$errstr,$errfile,$errline,$errctx) {

        if ($errno & E_WARNING) {
            fprintf(STDERR,"Warning: %s [from %s:%d]\n", $errstr,$errfile,$errline);
            return true;
        }
        if ($errno & E_DEPRECATED) {
            //fprintf(STDERR,"Deprecated: %s [from %s:%d]\n", $errstr,$errfile,$errline);
            return true;
        }
        
        fprintf(STDERR,"Error:\n    %s (%d)\n",$errstr,$errno);
        fprintf(STDERR,"File:\n    %s (line %d)\n",$errfile,$errline);
        fprintf(STDERR,"%s\n",join("\n",self::indent(Debug::getCodePreview($errfile,$errline),4)));
        fprintf(STDERR,"Backtrace:\n%s\n", join("\n",self::indent(Debug::getBacktrace(1),4)));

        exit(1);
        if (self::$oldhandler) {
            $args = func_get_args();
            return call_user_func_array(self::$oldhandler,$args);
        }
        return true;
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
//ExceptionHandler::register();

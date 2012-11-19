<?php

namespace Cherry\Mvc;

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
        \ob_end_clean();
        printf("<pre>");
        printf("%s:\n    %s\n",$type,$message);
        printf("Source:\n    %s (line %d)\n",$file,$line);
        printf("%s\n",join("\n",self::indent(Debug::getCodePreview($file,$line),4)));
        printf("Backtrace:\n%s\n", join("\n",self::indent($bt,4)));
        printf("Debug log:\n%s\n",join("\n",self::indent($log,4)));
        printf("(Hint: Change the LOG_LENGTH envvar to set the size of the debug log buffer)\n");
        printf("</pre>");
        exit(1);
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
        $bt = Debug::getBacktrace(1);
        self::showError($ca,'Error',$errstr.' ('.$errno.')',$file,$line,$log,$bt);

        exit(1);

    }
    public static function __php_handleException(\Exception $exception) {

        \Cherry\debug("Unhandled exception %s in %s on line %d", get_class($exception), $exception->getFile(), $exception->getLine());
        $log = DebugLog::getDebugLog();

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

<?php

namespace Logger;

class Logger {
    
    const DEFAULT_LOG = "default";

    private static $targets = [];
    private static $logs = [];

    public static function getLog($log = self::DEFAULT_LOG) {
        if (!array_key_exists($log,self::$logs)) {
            self::$logs[$log] = new LogWriter($log);
        }
        return self::$logs[$log];
    }
    
    /**
     *
     *
     *
     *
     * @param LogTarget $target The target
     * @param array $options An associative array of options:
     *   "minlevel" - The minimum severity level for an event to be included (default 0, emerg)
     *   "maxlevel" - The maximum severity level for an event to be included (default 7, info)
     *   "include" - Classes to include, as an array. Wildcards allowed (default *)
     *   "log" - Logs to include (default *)
     *
     *
     */
    public static function addTarget(LogTarget $target, array $options = null) {
        $default = [
            "minlevel" => LOG_EMERG,
            "maxlevel" => LOG_DEBUG,
            "include" => [ "*" ]
        ];
        $options = array_merge($default,(array)$options);
        self::$targets[] = (object)[
            "target" => $target,
            "options" => $options
        ];
    }

    public static function log($level,$message,$trim=0) {
        $bt = debug_backtrace();
        $bt = array_slice($bt,$trim);
        $source = new LogSource($bt);
        $event = new LogEvent($level, $source, $message);
        
        self::logEvent($event);
    }
    
    public static function logEvent(LogEvent $event) {
        foreach(self::$targets as $target) {
            // Match the level
            if (($event->level >= $target->options["minlevel"])
                && ($event->level <= $target->options["maxlevel"])) {
                // Match the classes to include
                foreach($target->options["include"] as $include) {
                    if ($event->source->classMatch($include)) {
                        $target->target->logEvent($event);
                        break;
                    }
                }
            } else {
                echo "Level mismatch: {$event->level}!\n";
            }
        }
    }
    
}

class LogEvent {
    public $microtime;
    public $time;
    public $source;
    public $event;
    public $level;
    public $message;
    public $object = null;
    public function __construct($level,LogSource $source, $message) {
        $this->microtime = microtime(true);
        $this->time = time();
        $this->level = $level;
        $this->source = $source;
        if (is_array($message)) {
            $this->message = json_encode($message,\JSON_PRETTY_PRINT);
            $this->object = $message;
        } elseif (is_object($message)) {
            $this->message = "<".get_class($message).">".json_encode($message,\JSON_PRETTY_PRINT);
            $this->object = $message;
        } else {
            $this->message = $message;
        }
    }
}

class LogSource {
    private $backtrace;
    public function __construct($backtrace) {
        $this->backtrace = $backtrace;
    }
    public function classMatch($match) {
        if (count($this->backtrace)>1) {
            if (fnmatch($match, $this->backtrace[1]["class"]))
                return true;
        }
        if ($match == "*") return true;
        return false;
    }
    public function getCallerInfo() {
        $cfile = $this->backtrace[0]["file"];
        if (strpos($cfile,\CHERRY_APP)!==false)
            $cfile = str_replace(\CHERRY_APP,"[app]",$cfile);
        if (strpos($cfile,\CHERRY_LIB)!==false)
            $cfile = str_replace(\CHERRY_LIB,"[lib]",$cfile);
        $cline = $this->backtrace[0]["line"];
        if (count($this->backtrace)>1) {
            $cmethod = $this->backtrace[1]["function"];
            if (!empty($this->backtrace[1]["class"])) {
                $cclass = $this->backtrace[1]["class"];
                $ctype = $this->backtrace[1]["type"];
                $ccaller = $cclass.$ctype.$cmethod."()";
            } else {
                $ccaller = $cmethod."()";
            }
        } else {
            $ccaller = "<anonymous>";
        }
        return $ccaller." at ".$cfile.":".$cline;
    }
    public function getCallerLocation() {
        $cfile = $this->backtrace[0]["file"];
        if (strpos($cfile,\CHERRY_APP)!==false)
            $cfile = str_replace(\CHERRY_APP,"[app]",$cfile);
        if (strpos($cfile,\CHERRY_LIB)!==false)
            $cfile = str_replace(\CHERRY_LIB,"[lib]",$cfile);
        $cline = $this->backtrace[0]["line"];
        return $cfile.":".$cline;
    }
}


abstract class LogTarget {
    abstract public function logEvent(LogEvent $event);
    protected function getLevelString($level) {
        static $levels = [
            LOG_EMERG   => "EMERG",
            LOG_ALERT   => "ALERT",
            LOG_CRIT    => "CRITICAL",
            LOG_ERR     => "ERROR",
            LOG_WARNING => "WARNING",
            LOG_NOTICE  => "NOTICE",
            LOG_INFO    => "INFO",
            LOG_DEBUG   => "DEBUG"
        ];
        if (array_key_exists($level,$levels))
            return $levels[$level];
        return $level;
    }
}

class ConsoleLogTarget extends LogTarget {
    public function logEvent(LogEvent $event) {
        $msg = $event->message;
        //if (strpos($msg,"\n")===false) $msg.="\n";
        $msg = str_replace("\n","\n    ",$msg);
        fprintf(STDERR,"\r\033[32m%s\033[0m [\033[1m%s\033[0m] %s \033[37m(from %s)\033[0m\n", date("D d M Y H:m:i",$event->time), $this->getLevelString($event->level), $msg, $event->source->getCallerInfo());
    }
}
class FileLogTarget extends LogTarget {
    public function __construct($stream) {
        $this->fh = $stream;
    }
    public function logEvent(LogEvent $event) {
        $msg = $event->message;
        $msg = str_replace("\n","\n    ",$msg);
        fprintf($this->fh,"%s [%s] %s: %s\n", date("D d M Y H:m:i",$event->time), $event->source->getCallerLocation(), $this->getLevelString($event->level), $msg);
    }
}

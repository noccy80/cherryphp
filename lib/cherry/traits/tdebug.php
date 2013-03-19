<?php

namespace Cherry\Traits;

if (!defined("DEBUG_MAX_CLASSLEN"))
    define("DEBUG_MAX_CLASSLEN",30);

trait TDebug {

    protected function debug($str) {
        static $lp,$rp;
        if (!$lp) list($lp,$rp) = [
            html_entity_decode("&#x2590;", ENT_NOQUOTES, 'UTF-8'),
            html_entity_decode("&#x258B;", ENT_NOQUOTES, 'UTF-8')
        ];
        $class = get_called_class();
        if (defined("DEBUG_IGNORE_CLASSES")) {
            static $ignore;
            if (!$ignore) $ignore = explode(",",DEBUG_IGNORE_CLASSES);
            if (in_array($class,$ignore)) return;
        }
        $args = func_get_args();
        $class = \Utils::collapsePath($class,DEBUG_MAX_CLASSLEN,"\\");
        $cl = strlen($class);
        $class = explode("\\",$class);
        foreach($class as $i=>$cs) {
            if ($cs == end($class))
                $class[$i] = "\033[1m".$class[$i]."\033[21m";
            else
                $class[$i] = "\033[2m".$class[$i]."\033[22m";
        }
        $class = join("\\",$class);
        if ($cl<DEBUG_MAX_CLASSLEN) $class = str_repeat(" ",DEBUG_MAX_CLASSLEN-$cl).$class;
        $fmt = array_shift($args);
        $class = sprintf("\033[32m%s\033[0m", $class);
        $type = sprintf("\033[0;32m{$lp}\033[32;7mDEBUG\033[0;32m{$rp}\033[0m");
        $fmt = "{$class} {$type} ".$fmt;
        array_unshift($args,$fmt);
        call_user_func_array("\cherry\debug",$args);
    }

    protected function warn($str) {
        static $lp,$rp;
        if (!$lp) list($lp,$rp) = [
            html_entity_decode("&#x2590;", ENT_NOQUOTES, 'UTF-8'),
            html_entity_decode("&#x258B;", ENT_NOQUOTES, 'UTF-8')
        ];
        $class = get_called_class();
        if (defined("DEBUG_IGNORE_CLASSES")) {
            static $ignore;
            if (!$ignore) $ignore = explode(",",DEBUG_IGNORE_CLASSES);
            if (in_array($class,$ignore)) return;
        }
        $args = func_get_args();
        $class = \Utils::collapsePath($class,DEBUG_MAX_CLASSLEN,"\\");
        $cl = strlen($class);
        $class = explode("\\",$class);
        foreach($class as $i=>$cs) {
            if ($cs == end($class))
                $class[$i] = "\033[1m".$class[$i]."\033[21m";
            else
                $class[$i] = "\033[2m".$class[$i]."\033[22m";
        }
        $class = join("\\",$class);
        if ($cl<DEBUG_MAX_CLASSLEN) $class = str_repeat(" ",DEBUG_MAX_CLASSLEN-$cl).$class;
        $fmt = array_shift($args);
        $class = sprintf("\033[32m%s\033[0m", $class);
        $type = sprintf("\033[0;33m{$lp}\033[33;7mWARN \033[0;33m{$rp}\033[0m");
        $markup = "\033[33m";
        $fmt = "{$class} {$type} {$markup}".$fmt."\033[0m";
        array_unshift($args,$fmt);
        call_user_func_array("\cherry\debug",$args);
    }

}

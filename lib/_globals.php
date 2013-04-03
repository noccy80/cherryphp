<?php
/**
 * Global functions go into this file. There should be a really good reason for
 * them to be here, such as patching behavior or extensive access.
 *
 *
 *
 *
 */

define("INSPECT_OMIT_TYPE", 1<<0);
define("INSPECT_EXPANDED", 1<<1);
define("INSPECT_OBJECT_PARENT", 1<<1);

/**
 * @brief Inspect a variable, returning a printable string.
 */
function var_inspect($v,$omit_type=false,$expanded=false,$indent = 0) {
    $ind = str_repeat("    ",$indent);
    $suf = null;
    if (is_string($v)) {
        if (strlen($v)>30) $rep = "\"".substr($v,0,30)."...\"";
        else $rep = "\"".$v."\"";
        if (!$omit_type) {
            $len = strlen($v);
            $suf = "[{$len}]";
        }
    } elseif (is_bool($v)) {
        if ($v) $rep = "True";
        else $rep = "False";
    } elseif (is_object($v)) {
        $rep = get_class($v);
        $vars = [];
        foreach($v as $k=>$vv) {
            $vars[] = ($expanded?"\n{$ind}    ":' ')."{$k}:".var_inspect($vv,false,$expanded,$indent+1);
        }
        if (!$expanded) {
            $vars = array_slice($vars,0,3);
            if (count($vars)>0) $vars[] = " ...";
        }
        $rep.= " {".join(($expanded?'':','),$vars).($expanded?"\n":"")."}";
    } elseif (is_array($v)) {
        $vars = [];
        foreach($v as $k=>$vv)
            $vars[] = ($expanded?"\n{$ind}    ":' ')."\"{$k}\"=> ".var_inspect($vv,false,$expanded,$indent+1);
        if (count($v)>3) $vars[] = '..';
        $len = count($v);
        if (!$expanded) {
            $vars = array_slice($vars,0,3);
            if (count($vars)>0) $vars[] = " ...";
        }
        $rep = "[".join(($expanded?"":","),$vars).($expanded?"\n":"")."{$ind}]";
        $suf = "[{$len}]";
    } elseif (is_null($v)) {
        if ($omit_type) $rep = "NULL";
        else $rep = '';
    } else {
        $rep = $v;
    }
    if ($omit_type)
        return $rep;
    return sprintf("<%s%s>%s",gettype($v),$suf,$rep);
}

function preload_class($class) {
    return class_exists($class);
}


function logstr($type,$fmt,$args=null) {
    $args = func_get_args();
    if (DEBUG_VERBOSE) {
        $bt = debug_backtrace();
        if (count($bt)>1) {
            $ol = \Cherry\Core\Debug::getLineInfo($bt[1]);
            if ($ol) $args[1] = $ol.' '.$args[1];
        }
    }
    call_user_func_array(array('\Cherry\Core\DebugLog','log'),$args);
}

function debug($fmt,$args=null) {
    $args = func_get_args();
    array_unshift($args,LOG_DEBUG);
    if (DEBUG_VERBOSE) {
        $bt = debug_backtrace();
        if (count($bt)>1) {
            $ol = \Cherry\Core\Debug::getLineInfo($bt[1]);
            if ($ol) $args[1] = $ol.' '.$args[1];
        }
    }

    call_user_func_array(array('\Cherry\Core\DebugLog','log'),$args);
}    

function indent($string,$indent=4) {
    $le = ((strpos($string,"\n\r")!==false)?"\n\r":
        (strpos($string,"\r")!==false)?"\r":"\n");
    $is = str_repeat(" ",$indent);
    return $is.str_replace($le,$le.$is,$string);
}

function readpass($prompt) {
    echo $prompt;
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
    echo "\n";
    return $password;
}

function bind($context,callable $closure) {
    return Closure::bind($closure,$context,$context);
}

function p($path) {
    return \Cherry\Core\PathResolver::path($path);
}

if (_IS_WINDOWS) {
    // Define functions that are not available in windoze
}

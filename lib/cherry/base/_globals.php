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

function debug($str) {
    $args = func_get_args();
    call_user_func_array('\Cherry\debug',$args);
    //if (count($args)>1)
    //    $str = call_user_func_array("sprintf",$args);
    //\Cherry\debug($str);

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

if (_IS_WINDOWS) {
    // Define functions that are not available in windoze
}

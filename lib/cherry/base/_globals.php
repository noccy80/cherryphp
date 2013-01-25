<?php
/**
 * Global functions go into this file. There should be a really good reason for
 * them to be here, such as patching behavior or extensive access.
 *
 *
 *
 *
 */

/**
 * @brief Inspect a variable, returning a printable string.
 */
function var_inspect($v,$omit_type=false) {
    if (is_string($v)) {
        if (strlen($v)>30) $rep = "\"".substr($v,0,30)."...\"";
        else $rep = "\"".$v."\"";
    } elseif (is_bool($v)) {
        if ($v) $rep = "True";
        else $rep = "False";
    } elseif (is_object($v)) {
        $rep = get_class($v);
    } elseif (is_array($v)) {
        for ($n = 0; $n < min(count($v),3); $n++)
            $vars[] = var_inspect($v[$n],true);
        if (count($v)>3) $vars[] = '.. ('.count($v).')';
        $rep = "[".join(",",$vars)."]";
    } elseif (is_null($v)) {
        if ($omit_type) $rep = "NULL";
        else $rep = '';
    } else {
        $rep = $v;
    }
    if ($omit_type)
        return $rep;
    return sprintf("<%s>%s",gettype($v),$rep);
}

if (_IS_WINDOWS) {
    // Define functions that are not available in windoze
}
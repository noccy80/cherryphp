<?php

class Utils {

    public static function getClassFromDotted($classname) {
        $cn = str_replace("-"," ",$classname);
        $cn = str_replace("."," . ",$cn);
        $cn = ucwords($cn);
        $cn = str_replace(" ","",$cn);
        $cn = "\\".trim(str_replace(".","\\",$cn),"\\");
        return $cn;
    }

    static function collapsePath($path,$length,$separator="/") {
        $pl = strlen($path);
        while($pl>DEBUG_MAX_CLASSLEN) {
            $path = explode($separator,$path);
            if (count($path)>2) {
                if ($path[1] == "...")
                    unset($path[2]);
                else
                    $path[1] = "...";
                $path = join($separator,$path);
                $pl = strlen($path);
            } else {
                if ($path[1] == "...")
                    unset($path[0]);
                $path = join($separator,$path);
                break;
            }
        }
        return $path;
    }

    static function indentText($text,$indent,$skipfirstline=false) {
        return (($skipfirstline==false)?str_repeat(" ",$indent):"").str_replace("\n","\n".str_repeat(" ",$indent),$text);
    }

    static function detectLineEnding($string,$default="\n") {
        if (strpos($string, "\r\n")!==false) {
            return "\r\n";
        } elseif (strpos($string, "\r")!==false) {
            return "\r";
        } elseif (strpos($string, "\n")!==false) {
            return "\n";
        } else {
            return $default;
        }
    }

}

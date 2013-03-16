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

}

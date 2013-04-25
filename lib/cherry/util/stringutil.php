<?php

namespace Cherry\Util;

class StringUtil {

    static public function camelToUnderscore($string) {
        $out = "";
        for($c = 0; $c < strlen($string); $c++) {
            $out.= ((ctype_lower($string[$c]))?"":"_").strtolower($string[$c]);
        }
        return $out;
    }

    static public function underscoreToCamel($string) {
        $out = str_replace("_"," ",$string);
        $out = ucwords($out);
        $out[0] = strtolower($out[0]);
        $out = str_replace(" ","",$out);
        return $out;
    }

    static public function getFqcn($class) {

    }


}

<?php

namespace Cherry\Cli;

use \Ansi\Color as AnsiColor;

class HtmlToAnsi {

    public function __construct() {
        $a = new \Cherry\Cli\Ansi();
    }

    public function convert($html) {

        $ansi = str_replace("<?","&lt;?",$html);
        $ansi = preg_replace_callback("|\<code\>(.*?)\<\/code\>|msi",
            function($m){
                return $m[1];
            },$ansi);
        $ansi = preg_replace_callback("|\<b\>(.*?)\<\/b\>|msi",
            function($m){
                return "\033[1m{$m[1]}\033[22m";
            },$ansi);
        $ansi = preg_replace_callback("|\<u\>(.*?)\<\/u\>|msi",
            function($m){
                return "\033[4m{$m[1]}\033[24m";
            },$ansi);
        $ansi = preg_replace_callback("|\<blockquote\>(.*?)\<\/blockquote\>|msi",
            function($m){
                $out = null;
                $lines = explode("\n",wordwrap($m[1],70));
                foreach($lines as $line) {
                    $out.= str_repeat(" ",4).$line."\n";
                }
                return $out;
            },$ansi);
        $ansi = preg_replace_callback("|\<span(.*?)\>(.*?)\<\/span\>|msi",
            function($m){
                $_ansi = null;
                $_reset = null;
                if (count($m)>1) {
                    $stylepos = strpos($m[1],"style=");
                    if ($stylepos!==false) {
                        $stylepos = strpos($m[1],"\"",$stylepos);
                        $styleend = strpos($m[1],"\"",$stylepos+1);
                        $style = trim(substr($m[1],$stylepos,$styleend-$stylepos)," \"");
                        $styles = explode(";",$style);
                        foreach($styles as $style) {
                            if (strpos($style,":")!==false) {
                                list($attr,$value) = explode(":",$style,2);
                                $value = trim($value);
                                switch(strtolower(trim($attr))) {
                                    case "color":
                                        if ($value[0]=="#") {
                                            $r = min(hexdec(substr($value,1,2))*1.4,255);
                                            $g = min(hexdec(substr($value,3,2))*1.4,255);
                                            $b = min(hexdec(substr($value,5,2))*1.4,255);
                                        } else {
                                            list($r,$g,$b) = AnsiColor::colorNameToRgb($value);
                                        }
                                        $_ansi.= ";".AnsiColor::rgb256fg($r,$g,$b);
                                        $_reset.= ";39";
                                        break;
                                    case "font-weight":
                                        if (strpos($value,"bold")!==false)
                                            $_ansi.= ";1";
                                        if (strpos($value,"normal")!==false)
                                            $_ansi.= ";22";
                                        if (strpos($value,"thin")!==false)
                                            $_ansi.= ";2";
                                        $_reset.= ";22";
                                        break;
                                    case "font-style":
                                        if (strpos($value,"underline")!==false)
                                            $_ansi.= ";4";
                                        $_reset.= ";24";
                                        break;
                                }
                            }
                        }
                    }
                    if ($_ansi) {
                        $_ansi = trim($_ansi,";");
                        $_reset = trim($_reset,";");
                        $out = "\033[{$_ansi}m{$m[2]}\033[{$_reset}m";
                    } else {
                        $out = $m[2];
                    }
                } else {
                    $out = null;
                }
                return $out;
            },$ansi);
        $ansi = preg_replace_callback("|\<font color=\"(.*?)\"\>(.*?)\<\/font\>|msi",
            function($m){
                $_ansi = null;
                $_reset = null;
                if (count($m)>1) {
                    $value = $m[1];
                    if ($value[0]=="#") {
                        $r = min(hexdec(substr($value,1,2))*1.4,255);
                        $g = min(hexdec(substr($value,3,2))*1.4,255);
                        $b = min(hexdec(substr($value,5,2))*1.4,255);
                    } else {
                        list($r,$g,$b) = AnsiColor::colorNameToRgb($value);
                    }
                    $_ansi.= ";".AnsiColor::rgb256fg($r,$g,$b);
                    $_reset.= ";39";
                    $_ansi = trim($_ansi,";");
                    $_reset = trim($_reset,";");
                    $out = "\033[{$_ansi}m{$m[2]}\033[{$_reset}m";
                } else {
                    $out = $m[2];
                }
                return $out;
            },$ansi);
        $ansi = preg_replace_callback("|\<ul(.*?)\>(.*?)\<\/ul\>|msi",
            function($m){
                $out = null;
                if (preg_match_all("|\<li(.*?)\>(.*?)\<\/li\>|msi",$m[2],$sub)) {
                    foreach($sub[2] as $item) {
                        $out.="  &square; {$item}\n";
                    }
                } else {
                    $out = $m[2];
                }
                return $out;
            },$ansi);
        $ansi = preg_replace("|\<br \/\>|msi","\n",$ansi);
        $ansi = html_entity_decode($ansi,ENT_HTML5,"utf-8");
        return $ansi;

    }

}

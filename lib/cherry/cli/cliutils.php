<?php

namespace Cherry\Cli;

class CliUtils {

    static function numberLines($text,$format="%3d",$start=1) {
        $out = [];
        foreach((array)explode("\n",$text) as $line) {
            $out[] = sprintf($format." %s",$start++,$line);
        }
        return join("\n",$out);
    }

}

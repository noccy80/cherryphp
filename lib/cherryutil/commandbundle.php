<?php

namespace CherryUtil;
use Cherry\Cli\Ansi;

abstract class CommandBundle {
    protected function parseOpts(array $args,array $rules) {

        $out = array();
        for($optidx = 0; $optidx < count($args); $optidx++) {
            $opt = $args[$optidx];
            $matched = false;
            foreach($rules as $name=>$rule) {
                if ($rule[strlen($rule)-1] == ':') {
                    $rulestr = substr($rule,0,strlen($rule)-1);
                    if ($opt == $rulestr) {
                        $out[$name] = $args[$optidx+1];
                        $optidx++;
                        $matched = true;
                    }
                } elseif ($rule[0] == '+') {
                    if ($opt == $rule) {
                        $out[$name] = true;
                        $matched = true;
                    }
                }
            }
            if (!$matched) {
                fprintf(STDERR,"Unknown option: %s\n", $opt);
            }
        }
        return $out;

    }

}


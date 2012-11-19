<?php

namespace Cherry\Mvc;

class Html {

    public static function __callStatic($name,$args) {
        $tag = $name;
        $attrs = [];
        $children = [];
        $content = '';
        if (count($args)>0) {
            $content = $args[0];
            if (count($args)>1) {
                $attrs = $args[1];
                if (count($args)>2) {
                    $children = $args[2];
                }
            }
        }
        if (count($attrs)>0) {
            $ao = '';
            foreach($attrs as $k => $v) {
                $ao.= sprintf(' %s="%s"', $k, htmlentities($v));
            }
        } else {
            $ao = '';
        }
        if (count($children)>0) {
            foreach($children as $k=>$v) {
                $content = str_replace('{'.$k.'}', $v, $content);
            }
        }
        if (is_array($content)) $content = join($content);
        return sprintf('<%s%s>%s</%s>', $tag, $ao, $content, $tag);
    }

}

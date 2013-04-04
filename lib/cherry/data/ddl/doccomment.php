<?php

namespace Cherry\Data\Ddl;

class DocComment {
    private $tags = [];
    private $comment = null;
    function __construct($comment=null) {
        if ($comment) $this->parse($comment);
    }
    public function parse($string) {
        if (substr(ltrim($string),0,3) == "/**") {
            $cout = [];
            $cmt = trim($string,"/*\n");
            $cmtl = explode("\n",$cmt);
            foreach($cmtl as $cmtr) $cout[] = ltrim($cmtr,"* ");
            $ccmt = $cout;
        } else {
            $ccmt = explode("\n",$string);
        }
        foreach($ccmt as $row) {
            if (substr($row,0,1) == "@") {
                if (strpos($row," ")!==false) {
                    list($tag,$row) = explode(" ",$row,2);
                    $tag = substr($tag,1);
                    $_tag = null; // Don't parse trailing tags
                    if (array_key_exists($tag,$this->tags)) {
                        if (!is_array($this->tags[$tag]))
                            $this->tags[$tag] = [ $this->tags[$tag] ];
                        $this->tags[$tag][] = trim($row);
                    } else
                        $this->tags[$tag] = trim($row);
                } else {
                    $_tag = substr($row,1);
                    $row = null;
                }
            } else {
                if ($_tag) {
                    if (array_key_exists($_tag,$this->tags))
                        $this->tags[$_tag] .= "\n".$row;
                    else
                        $this->tags[$_tag] = $row;
                } else $this->comment .= $row."\n";
            }
        }
    }
    public function __get($key) {
        $key = str_replace("_","-",$key);
        if (array_key_exists($key,$this->tags))
            return $this->tags[$key];
        return null;
    }
}

<?php

namespace Cherry\Web\Loaders;

use \Cherry\Data\Ddl\SdlTag;
use \Cherry\Web\HtmlTag as h;

/**
 * ATL: Another Template Language.
 *
 *
 *
 */
class AtlLoader extends Loader {

    public function load($filename,$output=false) {
        $doc = SdlTag::createFromFile($filename);
        $out = "";
        foreach($doc->getChildren() as $child) {
            $out.= $this->buildNodeTree($child);
        }
        
        if (class_exists("tidy")) {
            $t = new \tidy();
            $cfg = [
                "indent" => true,
                "wrap" => 200,
                "doctype" => "omit"
            ];
            $t->parseString($out, $cfg, 'utf8');
            $t->cleanRepair();
            $out = (string)$t;
        }

        $out = "<!DOCTYPE HTML>\n".$out;
        if ($output)
            echo $out;
        return $out;
    }

    private function buildNodeTree(SdlTag $node) {
        $tag = strtolower($node->getName());
        $attr = "";
        foreach($node->getAttributes() as $name=>$value) {
            $attr.=" {$name}=\"{$value}\"";
        }
        $out = "";
        if ($node->hasChildren()) {
            if ($tag) $out.= "<{$tag}{$attr}>";
            foreach($node->getChildren() as $child) {
                $out.= $this->buildNodeTree($child);
            }
            if ($tag) $out.= "</{$tag}>";
        } else {
            if (count($node)>0)
                if (!$tag)
                    $out.= $node[0];
                else
                    $out.= "<{$tag}{$attr}>".$node[0]."</{$tag}>";
            else
                $out.= "<{$tag}{$attr}>";
        }
        return $out;
    }

}

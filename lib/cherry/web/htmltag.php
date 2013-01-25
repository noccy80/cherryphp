<?php

namespace Cherry\Web;

class HtmlTag {

    private $value;
    private $attrs = [];
    private $tag;

    static function __callStatic($name,array $args) {
        if (count($args)>0) {
            $value = $args[0];
        } else {
            $value = null;
        }
        return new HtmlTag($name,$value);
    }

    public function __construct($tag,$value=null,array $opts=null) {
        $this->tag = $tag;
        $this->value = $value;
    }

    public function setStyle($styles) {
        if (is_array($styles)) {
            $cssr = [];
            foreach($styles as $prop=>$val) $cssr[] = "{$prop}:{$val}";
            $css = join("; ", $cssr).";";
            $this->attrs['style'] = $css;
        } else {
            $this->attrs['style'] = $styles;
        }
        return $this;
    }

    public function setValue($value) {
        $this->value = $value;
        return $this;
    }

    public function setId($id) {
        $this->attrs['id'] = $id;
        return $this;
    }

    public function setName($name) {
        $this->attrs['name'] = $name;
        return $this;
    }

    public function checkbox($id=null,$name=null,$value=null) {
        $this->attrs['type'] = 'checkbox';
        $this->setId($id);
        $this->setName($name);
        $this->attrs['value'] = $value;
        return $this;
    }

    public function checked($state=true) {
        $this->attrs['checked'] = $state;
        return $this;
    }

    public function disabled($state=true) {
        $this->attrs['disabled'] = $state;
        return $this;
    }

    public function write($value) {
        $args = func_get_args();
        $this->value.= call_user_func_array('sprintf',$args);
        return $this;
    }

    public function writeMulti($values) {
        $args = func_get_args();
        $this->value.= join("",$args);
        return $this;
    }

    public function __toString() {
        $earr = [];
        foreach($this->attrs as $k=>$v) {
            if ($v === true) $v = $k;
            if (!empty($v)) $earr[] = " {$k}=\"{$v}\"";
        }
        $extra = join(" ",$earr);
        return "<{$this->tag}{$extra}>{$this->value}</{$this->tag}>";
    }

}

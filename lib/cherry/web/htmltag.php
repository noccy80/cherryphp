<?php

namespace Cherry\Web;

class HtmlTag {

    private $value;
    private $attrs = [];
    private $tag;

    static function __callStatic($name,array $args) {
        $value = null;
        foreach((array)$args as $arg) {
            $value.= (string)$arg;
        }
        return new HtmlTag($name,$value);
    }

    public function __construct($tag,$value=null,array $opts=null) {
        $this->tag = $tag;
        $this->value = (string)$value;
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

    public function setAttr($attr,$val) {
        $this->attrs[$attr] = $val;
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

    public function makeCheckbox($id=null,$name=null,$value=null) {
        $this->attrs['type'] = 'checkbox';
        $this->setId($id);
        $this->setName($name);
        $this->attrs['value'] = $value;
        return $this;
    }

    public function makeTextbox($id=null,$name=null,$value=null,$placeholder=null) {
        $this->attrs['type'] = 'textbox';
        $this->setId($id);
        $this->_placeholder($placeholder);
        $this->_value($value);
        $this->_name($name);
        return $this;
    }

    public function makeSubmit($text) {
        $this->_type("submit");
        $this->_value($text);
        return $this;
    }

    public function makeForm($id=null,$method="get",$action=null) {
        $this->attrs['type'] = 'checkbox';
        $this->setId($id);
        $this->_action($action);
        $this->_method($method);
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

    public function __call($attr,$vals) {
        if ($attr[0] == "_") {
            $attr = substr($attr,1);
            if (count($vals) == 0)
                $val = true;
            else
                $val = $vals[0];
            $this->attrs[$attr] = $val;
            return $this;
        }
    }

    public function __invoke($args) {
        //$args = func_get_args();
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
        $html = "<{$this->tag}{$extra}>{$this->value}</{$this->tag}>";
        return $html;
    }

    public function open() {
        $earr = [];
        foreach($this->attrs as $k=>$v) {
            if ($v === true) $v = $k;
            if (!empty($v)) $earr[] = " {$k}=\"{$v}\"";
        }
        $extra = join(" ",$earr);
        $html = "<{$this->tag}{$extra}>";
        return $html;
    }

    public function close() {
        $html = "</{$this->tag}>";
        return $html;
    }

}

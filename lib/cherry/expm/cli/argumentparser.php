<?php

namespace Cherry\Expm\Cli;

/*
 * class ArgumentParser
 */

class ArgumentParser {
    private $options = [];
    private $args = null;
    public $arguments = null;
    public $params = [];
    function __construct($args=null) {
        global $argv;
        if (!$args) {
            if (isset($argv)) {
                $this->args = array_slice($argv,1);
                return;
            } elseif ((isset($_SERVER) && array_key_exists('argv',$_SERVER))) {
                $args = $_SERVER['argv'];
            } else {
                $args = '';
            }
        }
        $matches = [];
        if (preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $args, $matches)) {
            $this->args = $matches[0];
        } else {
            $this->args = [];
        }

    }
    /**
     *
     * Options:
     *  - export => [true|false]
     */
    function addOption($id, Option $option, $description, array $options = null) {
        $this->options[$id] = (object)compact('id','option','description','options');
    }

    function usage() {
        echo "Valid arguments:\n";
        foreach($this->options as $option) {
            if ($option->option instanceof BooleanOption)
                echo "    ".$option->option->getString()."  - ".$option->description."\n";
            elseif ($option->option instanceof ValueOption)
                echo "    ".$option->option->getString()." ..  - ".$option->description."\n";
            else
                echo "    ".$option->option->getString()." .. [ .. ] - ".$option->description."\n";


        }
    }

    function parse() {
        $argl = $this->args;
        $params = [];
        while(count($argl)>0) {
            $arg = array_shift($argl);
            if (substr($arg,0,2)=='--') {
                $str = substr($arg,2);
                $long = true;
            } elseif (substr($arg,0,1)=='-') {
                $str = substr($arg,1);
                $long = false;
            } else {
                $long = null;
                $params[] = trim($arg,"\"'");
                // Handle value
            }
            if ($long!==null) {
                foreach($this->options as $option) {
                    $option = $option->option;
                    if (!$option->matched) {
                        $r = $option->check($long,$str);
                        if ($r == Option::RET_WANT_VALUE) {
                            $val = array_shift($argl);
                            $option->setValue($val);
                        }

                    }
                }
            }
        }
        $argo = [];
        foreach($this->options as $option) {
            $argo[$option->id] = $option->option->getValue();
        }
        $this->arguments = (object)$argo;
        $this->params = $params;
        return [ $this->arguments, $this->params ];
    }
}

abstract class Option {
    const RET_IGNORE = 0;
    const RET_PARSED = 1;
    const RET_WANT_VALUE = 2;
    public $matched = false;
    protected $longopt = [];
    protected $shortopt = null;
    function __construct($options) {
        $opts = (array)$options;
        if (strlen($opts[0]) == 1) {
            $this->shortopt = array_shift($opts);
        }
        $this->longopt = $opts;
    }
    public function getString() {
        $out = [];
        if ($this->shortopt) $out[] = "-{$this->shortopt}";
        foreach ($this->longopt as $opt) $out[] = "--{$opt}";
        return join(", ",(array)$out);
    }
    abstract public function check($long,$option);
}

class BooleanOption extends Option {
    private $value = false;
    public function check($long,$option) {
        if (!$long) {
            // todo: extract long/shortopt in constructor
            $sopt = $option[0];
            // match short
            if ($option[0] == $this->shortopt) {
                // todo: check matched, booleans are exclusive
                if (strlen($option)>1)
                    throw new ArgumentParserException("Invalid argument: {$option}");
                $this->matched = true;
                $this->value = true;
            }
        } else {
            if (strpos($option,"=")!==false) {
                $ke = explode("=",$option,2);
                $key = $ke[0];
            } else {
                $ke = explode(" ",$option,2);
                $key = $ke[0];
            }
            // check long options
            foreach($this->longopt as $longopt) {
                if ($key == $longopt) {
                    // todo: check matched, booleans are exclusive
                    $this->matched = true;
                    $this->value = true;
                    return Option::RET_PARSED;
                }
            }
        }

    }
    public function getValue() {
        return $this->value;
    }
}
class ValueOption extends Option {
    private $value = null;
    public function check($long,$option) {
        if (!$long) {
            // todo: extract long/shortopt in constructor
            $sopt = $option[0];
            // match short
            if ($option[0] == $this->shortopt) {
                // todo: check matched, booleans are exclusive
                if (strlen($option)>1) {
                    $this->matched = true;
                    $this->value = substr($option,1);
                    return Option::RET_PARSED;
                } else {
                    $this->matched = true;
                    $this->value = null;
                    return Option::RET_WANT_VALUE;
                }
            }
        } else {
            if (strpos($option,"=")!==false) {
                $ke = explode("=",$option,2);
                $key = $ke[0];
                if (count($ke>1)) { $value = $ke[1]; }
            } else {
                $key = $option;
                $value = null;
            }
            // check long options
            foreach($this->longopt as $longopt) {
                if ($key == $longopt) {
                    // todo: check matched, booleans are exclusive
                    $this->matched = true;
                    $this->value = $value;
                    if (!$value) return Option::RET_WANT_VALUE;
                    return Option::RET_PARSED;
                }
            }
        }
    }
    public function getValue() {
        return $this->value;
    }
    public function setValue($value) {
        $this->value = trim($value,"\"'");
    }
}
class ListOption extends Option {
    private $value = [];
    public function check($long,$option) {}
    public function getValue() {
        return $this->value;
    }
}

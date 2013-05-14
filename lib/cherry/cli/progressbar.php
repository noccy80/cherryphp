<?php

namespace Cherry\Cli;

class ProgressBar {
    private $value;
    private $max;
    private $label;
    private $width;
    public function __construct($width=40) {
        $this->width = $width;
    }
    public function setProgress($current,$max=null,$label=null) {
        $this->value = $current;
        if ($max !== null) $this->max = $max;
        if ($label !== null) $this->label = $label;
    }
    public function update() {
        $v = min($this->value,$this->max);
        $m = max(1,$this->max);
        $fill = ceil(($this->width/$m)*$v);
        $pc = (100/$m)*$v;
        $bar = "\033[32;1m".str_repeat(Glyph::fullblock(),$fill);
        $bar.= "\033[32;21m".str_repeat(Glyph::darkshade(),$this->width - $fill);
        $bar.= "\033[0m";
        fprintf(STDOUT,"\r\033[37m%s\033[0m ... \033[32m[%s\033[32m]\033[33m (\033[1m%5.1f%%\033[21m)\033[0m\033[K",$this->label,$bar,$pc);
    }
    public function done() {
        echo "\n";
    }
}

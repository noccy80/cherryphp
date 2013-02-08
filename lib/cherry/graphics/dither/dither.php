<?php

namespace Cherry\Graphics\Dither;

abstract class Dither {
    protected
            $r, $g, $b, $a, $x, $y;
    private function cv2rgba($c) {
    }
    private function rgba2cv($c) {
    }
    private function cv($x) {
        return ($x < 0x00)?0:(($x > 0xFF)?0xFF:$x);
    }
    public function ditherColor($x,$y,$c) {
        $this->a = ($c >> 24) & 0xFF;
        $this->g = ($c >> 16) & 0xFF;
        $this->b = ($c >> 8) & 0xFF;
        $this->r = ($c) & 0xFF;
        $ret = $this->ditherFunc($x,$y);
        return ($this->cv($this->a) << 24) | ($this->cv($this->g) << 16) | ($this->cv($this->b) << 8) | ($this->cv($this->r));
    }
    abstract protected function ditherFunc($x,$y);
}

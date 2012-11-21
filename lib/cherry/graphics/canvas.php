<?php

namespace Cherry\Graphics;

class Canvas {
    use
        Cherry\Traits\Extendable;
    
    private
        $himage = null;
    
    public function __construct($width=null,$height=null,$bgcolor=null) {
        if ($width && $height)
            $this->create($width,$height,$bgcolor);
    }
    
    public function __destruct() {
        if ($this->himage)
            imagedestroy($this->himage);
    }
    
    public function create($width,$height,$bgcolor=null) {
        if ($this->himage)
            imagedestroy($this->himage);
        $this->himage = imagecreatetruecolor($width,$height);
        $this->refresh();
    }
    
    public function setPixel($x,$y,$color) {
        $c = $color->map($this);
        imagesetpixel($this->himage, $x, $y, $c);
    }
    
}
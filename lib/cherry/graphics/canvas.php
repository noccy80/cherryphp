<?php

namespace Cherry\Graphics;

//use Cherry\Graphics\Drawable;
interface IDrawable {
    public function draw(Canvas $dest, Rect $destrect = null, Rect $srcrect = null);
    public function measure();
}

class Rect {
    public
        $x, $y, $w, $h; ///< Coordinates of the rect
    /**
     * @brief Create a rect
     */
    public function __construct($x,$y,$w,$h) {
        $this->x = $x;
        $this->y = $y;
        $this->w = $w;
        $this->h = $h;
    }
    /**
     * @brief Helper function
     */
    public static function rect($x,$y,$w,$h) {
        return new Rect($x,$y,$w,$h);
    }
}
//function rect($x,$y,$w,$h) {
//    return new Rect($x,$y,$w,$h);
//}

class Canvas implements IDrawable {
    use
        \Cherry\Traits\Extendable;

    private
        $himage = null,
        $width = null,
        $height = null,
        $truecolor = null;

    public function __construct($width=null,$height=null,$bgcolor=null) {
        if ($width && $height)
            $this->create($width,$height,$bgcolor);
    }

    public function __destruct() {
        if ($this->himage)
            imagedestroy($this->himage);
    }

    /**
     * @brief Clone canvas on object cloning.
     *
     */
    public function __clone() {
        if ($this->himage) {
            $newimage = imagecreatetruecolor($this->width, $this->height);
            imagecopy($newimage,$this->himage,0,0,0,0,$this->width,$this->height);
            $this->himage = $newimage;
        }
        $this->refresh();
    }

    /**
     * @brief Refresh the metadata from the canvas.
     *
     * This will populate the width, height and truecolor properties.
     */
    public function refresh() {
        $this->width = imagesx($this->himage);
        $this->height = imagesy($this->himage);
        $this->truecolor = imageistruecolor($this->himage);
    }

    /**
     * @brief Create a canvas from an image file.
     *
     */
    public function load($filename) {
        if ($this->himage)
            imagedestroy($this->himage);
        $this->himage = imagecreatefromstring(file_get_contents($filename));
        $this->refresh();
    }

    /**
     * @brief Create a new canvas
     *
     */
    public function create($width,$height,$bgcolor=null) {
        if ($this->himage)
            imagedestroy($this->himage);
        $this->himage = imagecreatetruecolor($width,$height);
        $this->truecolor = true;
        $this->refresh();
    }

    public function setPixel($x,$y,$color) {
        $c = $this->map($color,$x,$y);
        imagesetpixel($this->himage, $x, $y, $c);
    }

    public function getPixel($x,$y) {
        $c = imagecolorat($this->himage, $x, $y);
    }

    public function map($color,$x=null,$y=null) {
        /*if ((func_num_args()>1) && (!is_array($color)))
            $color = func_get_args();*/
        if (is_integer($color)) {
            // Color is already a color value
            return $color;
        } elseif (is_array($color)) {
            if (($x) && ($y))
                if ((($x % 2) == 0) && (($y % 2) == 0))
                    $color = array_map("floor",$color);
                else
                    $color = array_map("ceil",$color);
            else
                $color = array_map("intval",$color);
            // RGB[A]
            if (count($color) < 3)
                user_error("Array provided to map must be [r,g,b]");
            if (count($color)==3)
                $color[] = null;
            list($r,$g,$b,$a) = $color;
        } elseif ($color instanceof Color) {
            // Color is a color
            list($r,$g,$b,$a) = $color->getRGBA();
        } else {
            user_error("No parsable color provided to map");
        }
        // For truecolor images, we just return the color
        if ($this->truecolor) {
            if (!$a) $a = 255;
            $a = ((~((int)$a)) & 0xff) >> 1;
            return ( (($a & 0x7F) << 24) | (($b & 0xFF) << 16) | (($g & 0xFF) << 8) | ($r & 0xFF) );
        }
        // Make sure we don't use more than 255 colors. This might not be
        // the optimal solution but it works for now. It does mean we can
        // allocate a desired palette, as colorclosest would pick the closest
        // color in the palette, so could indeed be useful.
        if (imagecolorstotal($this->himage) >= 255)
            return imagecolorclosest($a,$r,$g,$b);
        // Check if the color has already been allocated and exist in the
        // palette. If not, allocate it.
        $c = imagecolorexact($this->himage,$r,$g,$b);
        if ($c == -1)
            return imagecolorallocate($this->himage,$r,$g,$b);
        return $c;
    }

    /**
     * @brief Save the canvas to a file
     *
     */
    public function save($file) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        switch(strtolower($ext)) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($this->himage, $file);
                break;
            case 'png':
                imagepng($this->himage, $file);
                break;
            case 'gif':
                imagegif($this->himage, $file);
                break;
            default:
                user_error("Unsupported image type: ".$ext);
                break;
        }
    }

    public function resize($width,$height,$hq=false,$resample=true) {

        $nw = $width;
        $nh = $height;

        // Experimental HQ resize
        if ($hq) {
            $cw = $this->width;
            $ch = $this->height;
            while ( ($nw > $cw * 1.05) || ($nh > $ch * 1.05) ) {
                // TODO: This will cause fractions which might bork the aspect
                $cw = (int)$cw*1.05;
                $ch = (int)$ch*1.05;
                $this->resize($cw,$ch,false,false);
            }
        }

        $htemp = imagecreatetruecolor($nw,$nh);
        if ($resample)
            imagecopyresampled($htemp, $this->himage, 0, 0, 0, 0, $nw, $nh, $this->width, $this->height);
        else
            imagecopyresized($htemp, $this->himage, 0, 0, 0, 0, $nw, $nh, $this->width, $this->height);
        imagedestroy($this->himage);
        $this->himage = $htemp;
        $this->refresh();
    }

    public function draw(Canvas $dest, Rect $destrect = null, Rect $srcrect = null) {}

    public function measure() {}

    public function __toString() {
        ob_start();
        imagejpeg($this->himage);
        $img = ob_get_clean();
        return $img;
    }

}

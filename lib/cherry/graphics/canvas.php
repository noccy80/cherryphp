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

    public function move($x,$y) {
        $this->x+= $x;
        $this->y+= $y;
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
        $truecolor = null,
        $dither = null;

    public function __construct($width=null,$height=null,$bgcolor=null) {
        if ($width && $height)
            $this->create($width,$height,$bgcolor);
    }

    public function __destruct() {
        if ($this->himage)
            imagedestroy($this->himage);
    }

    public function __get($key) {
        switch($key) {
            case 'width':
                return $this->width;
            case 'height':
                return $this->height;
            case 'truecolor':
                return $this->truecolor;
            case 'himage':
                return $this->himage;
            default:
                throw new \BadFunctionCallException("Can not access missing property {$key}");
        }
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

    public static function createFromFile($filename) {
        $c = new Canvas();
        $c->load($filename);
        return $c;
    }

    public static function createTrueColor($width,$height) {
        $c = new Canvas($width,$height);
        return $c;
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

    public function setDitherClass(Dither $class) {
        $this->dither = $class;
    }

    public function drawLine($x1,$y1,$x2,$y2,$color) {
        $c = $this->map($color);
        //echo "Drawing line [{$x1}x{$y1}-{$x2}x{$y2}] c={$c}\n";
        imageline($this->himage,$x1,$y1,$x2,$y2,$c);
    }

    public function drawRect($x1,$y1,$x2,$y2,$color) {
        $c = $this->map($color);
        //echo "Drawing line [{$x1}x{$y1}-{$x2}x{$y2}] c={$c}\n";
        imagerectangle($this->himage,$x1,$y1,$x2,$y2,$c);
    }

    public function drawFilledRect($x1,$y1,$x2,$y2,$color) {
        $c = $this->map($color);
        //echo "Drawing line [{$x1}x{$y1}-{$x2}x{$y2}] c={$c}\n";
        imagefilledrectangle($this->himage,$x1,$y1,$x2,$y2,$c);
    }

    public function setPixel($x,$y,$color) {
        $c = $this->map($color);
        if ($this->dither != null) $c = $this->dither->ditherColor($x,$y,$c);
        imagesetpixel($this->himage,$x,$y,$c);
    }

    public function getPixel($x,$y) {
        $c = imagecolorat($this->himage, $x, $y);
        return $c;
    }

    public function map($color) {
        /*if ((func_num_args()>1) && (!is_array($color)))
            $color = func_get_args();*/
        if (is_integer($color)) {
            // Color is already a color value
            return $color;
        } elseif (is_array($color)) {
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

    public function draw(Canvas $dest, Rect $destrect = null, Rect $srcrect = null) {
        imagecopyresampled($dest->himage, $this->himage,
                           $destrect->x, $destrect->y,
                           $srcrect->x, $srcrect->y,
                           $destrect->w, $destrect->h,
                           $srcrect->w, $destrect->h);
    }

    public function measure() {
        return new Rect(0, 0, $this->width, $this->height);
    }

    public function __toString() {
        ob_start();
        imagejpeg($this->himage);
        $img = ob_get_clean();
        return $img;
    }

}

interface ITrueColorDither { }
interface IOrderedDither { }
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
class OrderedDither extends Dither implements ITrueColorDither,IOrderedDither {
    public static
        $mthreshold2x2 = [
            [ 1, 3 ],
            [ 4, 2 ]
        ],
        $mthreshold3x3 = [
            [ 3, 7, 4 ],
            [ 6, 1, 9 ],
            [ 2, 8, 5 ]
        ],
        $mthreshold4x4 = [
            [ 1,  9,  3, 11 ],
            [ 13, 5, 15,  7 ],
            [ 4,  12, 2, 10 ],
            [ 16, 8, 14,  6 ]
        ];
    private
        $matrix = [],
        $bias = 0,
        $adjust = 1;
    const
        ODT_2X2 = 1,
        ODT_3X3 = 2,
        ODT_4x4 = 3;
    public function __construct(array $matrix) {
        $max = max(max($matrix));
        $this->bias = $max / 2;
        $this->adjust = $this->bias / 2;
        $this->matrix = $matrix;
    }
    protected function ditherFunc($x,$y) {
        $x = $x % 3;
        $y = $y % 3;
        $tm = ($this->matrix[$x][$y] - $this->bias) / $this->adjust;
        $this->r = $this->r + $tm;
        $this->g = $this->g + $tm;
        $this->b = $this->b + $tm;
    }
}

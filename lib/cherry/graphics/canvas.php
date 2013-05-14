<?php

namespace Cherry\Graphics;

use \Cherry\Types\Rect;
use \Cherry\Types\Point;

//use Cherry\Graphics\Drawable;
interface IDrawable {
    public function draw(Canvas $dest, Rect $destrect = null, Rect $srcrect = null);
    public function measure();
}

class Canvas implements IDrawable {
    use \Cherry\Traits\Extendable;
    use \Cherry\Traits\TDebug;
        

    private
        $himage = null,
        $width = null,
        $height = null,
        $truecolor = null,
        $dither = null,
        $mimetype = null,
        $line_last_x = null,
        $line_last_y = null;

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
            case 'mimetype':
                return $this->mimetype;
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

    public function loadString($string) {
        if (function_exists('finfo_buffer')) {
            $fb = finfo_open();
            $this->mimetype = \finfo_buffer($fb,$string,\FILEINFO_MIME_TYPE | \FILEINFO_SYMLINK);
        } else {
            $this->mimetype = "[PECL fileinfo missing]";
        }
        $this->himage = imagecreatefromstring($string);
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
        imagealphablending($this->himage,false);
        $this->truecolor = true;
        $this->refresh();
        if ($bgcolor)
            $this->clear($bgcolor);
    }

    public function clear($color) {
        $this->drawFilledRect(0,0,$this->width,$this->height,$color);
    }

    public function setDitherClass(Dither\Dither $class) {
        $this->dither = $class;
    }

    public function drawLine($x1,$y1,$x2,$y2,$color) {
        $c = $this->map($color);
        //echo "Drawing line [{$x1}x{$y1}-{$x2}x{$y2}] c={$c}\n";
        imageline($this->himage,$x1,$y1,$x2,$y2,$c);
    }

    public function drawLineTo($x,$y,$color) {
        $c = $this->map($color);
        if ($this->line_last_x == null) {
            $this->line_last_x = $x;
            $this->line_last_y = $y;
            return;
        }
        //echo "Drawing line [{$x1}x{$y1}-{$x2}x{$y2}] c={$c}\n";
        imageline($this->himage,$this->line_last_x,$this->line_last_y,$x,$y,$c);
        $this->line_last_x = $x;
        $this->line_last_y = $y;
    }
    public function drawLineEnd() {
        $this->line_last_x = null;
        $this->line_last_y = null;
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
        $cc = imagecolorsforindex($this->himage, $c); 
        list($r,$g,$b,$a) = [ $cc["red"], $cc["green"], $cc["blue"], $cc["alpha"] ];
        //$this->debug("Got pixel: %d %d %d %d (from %ld, for %dx%d)\n", $r, $g, $b, $a, $c,$x,$y);
        return $r | ($g << 8) | ($b << 16) | ($a << 24);
    }

    public function getPixelRGB($x,$y) {
        $c = imagecolorat($this->himage, $x, $y);
        $r = ($c >> 32) & 0xFF;
        $g = ($c >> 16) & 0xFF;
        $b = ($c >> 8) & 0xFF;
        $a = ($c & 0xFF);
        return [$r,$g,$b,$a];
    }

    public function map($color) {
        /*if ((func_num_args()>1) && (!is_array($color)))
            $color = func_get_args();*/
        if (is_integer($color)) {
            // Color is already a color value
            list($r,$g,$b) = [
                ($color & 0xFF),
                (($color >> 8) & 0xFF),
                (($color >> 16) & 0xFF)
            ];
            $a = 0; // 127;
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
            user_error("No parsable color provided to map: {$color}");
        }
        // For truecolor images, we just return the color
        if ($this->truecolor) {
            return imagecolorallocate($this->himage,$r,$g,$b);
            //return ( (($a & 0x7F) << 24) | (($r & 0xFF) << 16) | (($g & 0xFF) << 8) | ($b & 0xFF) );
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

    /**
     * @brief Output canvas to the browser
     *
     */
    public function output($type) {
        switch(strtolower($type)) {
            case 'jpg':
            case 'jpeg':
                header("Content-Type: image/jpeg",true);
                imagejpeg($this->himage);
                break;
            case 'png':
                header("Content-Type: image/png",true);
                imagepng($this->himage);
                break;
            case 'gif':
                header("Content-Type: image/gif",true);
                imagegif($this->himage);
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

    public function getRect() {
        return new Rect(0,0,$this->width,$this->height);
    }

    public function draw(Canvas $dest, Rect $destrect = null, Rect $srcrect = null) {
        if (!$srcrect) {
            $srcrect = $this->getRect();
        }
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

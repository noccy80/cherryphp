<?php

namespace Cherry\Graphics;

class Color {
    private $r,$g,$b,$a;
	private static $instance;
    public static function RGBtoHSV($r,$g,$b,$a=127) {
        if (!self::$instance)
            self::$instance = new Color();
        return self::$instance->fromRGB($r,$g,$b,$a)->toHSV();
    }
    public static function HSVtoRGB($h,$s,$v,$a=127) {
        if (!self::$instance)
            self::$instance = new Color();
        return self::$instance->fromHSV($h,$s,$v,$a)->toRGB();
    }
    public function __construct($r=0,$g=0,$b=0,$a=0) {
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
        $this->a = $a;
    }
    public function fromRGB($r,$g,$b,$a=127) {
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
        return $this;
    }
    public function fromHSV($h,$s,$v,$a=127) {
        $this->a = $a;
        $s = $s / 255;
	    if( $s == 0 ) {
		    // achromatic (grey)
	        $this->r = $v;
	        $this->g = $v;
	        $this->b = $v;
		    return $this;
	    }
        $v = $v / 255;

	    $h /= 42;			// sector 0 to 5 (was 60)
	    $i = floor( $h );
	    $f = $h - $i;			// factorial part of h
	    $p = $v * ( 1 - $s );
	    $q = $v * ( 1 - $s * $f );
	    $t = $v * ( 1 - $s * ( 1 - $f ) );

	    switch( $i ) {
		    case 0:
			    $r = $v;
			    $g = $t;
			    $b = $p;
			    break;
		    case 1:
			    $r = $q;
			    $g = $v;
			    $b = $p;
			    break;
		    case 2:
			    $r = $p;
			    $g = $v;
			    $b = $t;
			    break;
		    case 3:
			    $r = $p;
			    $g = $q;
			    $b = $v;
			    break;
		    case 4:
			    $r = $t;
			    $g = $p;
			    $b = $v;
			    break;
		    default:		// case 5:
			    $r = $v;
			    $g = $p;
			    $b = $q;
			    break;
	    }
	    $this->r = (int)($r * 255);
	    $this->g = (int)($g * 255);
	    $this->b = (int)($b * 255);
	    return $this;

    }
    public function toRGB() {
        return [ $this->r, $this->g, $this->b ];
    }
    public function getRGBA() {
        return [ $this->r, $this->g, $this->b, $this->a ];
    }
    public function toHSV() {
        $hsv = (object)[ "hue"=>null, "sat"=>null, "val"=>null ];
        $hsv->val = max($this->r,$this->g,$this->b);
        if ($hsv->val == 0) {
            $hsv->sat = 0;
            $hsv->hue = 0;
            return array_values((array)$hsv);
        }
        /* Normalize value to 1 */
        $r = (float)$this->r / $hsv->val;
        $g = (float)$this->g / $hsv->val;
        $b = (float)$this->b / $hsv->val;
        $rgbmin = min($r,$g,$b);
        $rgbmax = max($r,$g,$b);
        $hsv->sat = ($rgbmax - $rgbmin) * 255;
        if ($hsv->sat == 0) {
            $hsv->hue = 0;
            return array_values((array)$hsv);
        }
        /* Normalize saturation to 1 */
        $r = ($r - $rgbmin)/($rgbmax - $rgbmin);
        $g = ($g - $rgbmin)/($rgbmax - $rgbmin);
        $b = ($b - $rgbmin)/($rgbmax - $rgbmin);
        $rgbmin = max($r, $g, $b);
        $rgbmax = max($r, $g, $b);
        /* Compute hue */
        if ($rgbmax == $r) {
            $hsv->hue = 0.0 + 60.0*($g - $b);
            if ($hsv->hue < 0.0) {
                $hsv->hue += 360.0;
            }
        } else if ($rgbmax == $g) {
            $hsv->hue = 120.0 + 60.0*($b - $r);
        } else /* $rgbmax == $b */ {
            $hsv->hue = 240.0 + 60.0*($r - $g);
        }
        $hsv->hue = (int)($hsv->hue / 360 * 255);
        return array_values((array)$hsv);
    }
    public function toHSVint() {
        $rgb = (object)[ "r" => $this->r, "g" => $this->g, "b" => $this->b ];
        $hsv = (object)[ "hue"=>null, "sat"=>null, "val"=>null ];
        $hsv->val = max($rgb->r,$rgb->g,$rgb->b);
        if ($hsv->val == 0) {
            $hsv->sat = 0;
            $hsv->hue = 0;
            return array_values((array)$hsv);
        }
        $rgbmin = min($rgb->r,$rgb->g,$rgb->b);
        $rgbmax = max($rgb->r,$rgb->g,$rgb->b);
        $hsv->sat = 255 * ($rgbmax - $rgbmin) / $hsv->val;
        if ($hsv->sat == 0) {
            $hsv->hue = 0;
            return array_values((array)$hsv);
        }
        if ($rgbmax == $rgb->r) {
            $hsv->hue = 0 + 43*($rgb->g - $rgb->b)/($rgbmax - $rgbmin);
        } else if ($rgbmax == $rgb->g) {
            $hsv->hue = 85 + 43*($rgb->b - $rgb->r)/($rgbmax - $rgbmin);
        } else /* $rgbmax == $rgb->b */ {
            $hsv->hue = 171 + 43*($rgb->r - $rgb->g)/($rgbmax - $rgbmin);
        }
        $hsv->hue = round($hsv->hue);
        return array_values((array)$hsv);
    }
}

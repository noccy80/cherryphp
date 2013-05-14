<?php

namespace Cherry\Graphics;

use ArrayAccess;
use Cherry\Types\Rect;

/**
 * This is a layered canvas, allowing for basic compositing of layers. Layers
 * can be moved and resized within the canvas, and there is a total of 13
 * different blend modes available. Each layer has its own blend mode and
 * opacity.
 *
 * The blend modes are partially based on the GIMP layer modes, and the
 * algorithm currently works with floats.
 *
 * @author Christopher Vagnetoft <noccylabs.info>
 * @license GNU GPL v3
 */
class LayeredCanvas implements ArrayAccess {

    const BLEND_NORMAL = 0;
    const BLEND_ADD = 1;
    const BLEND_SUBTRACT = 2;
    const BLEND_MULTIPLY = 3;
    const BLEND_SCREEN = 4;
    const BLEND_OVERLAY = 5;
    const BLEND_SOFT_LIGHT = 6;
    const BLEND_HARD_LIGHT = 7;
    const BLEND_DIVIDE = 8;
    const BLEND_DIFFERENCE = 9;
    const BLEND_DARKEN_ONLY = 10;
    const BLEND_LIGHTEN_ONLY = 11;
    const BLEND_BURN = 12;
    const BLEND_DODGE = 13;

    const NUM_BLEND_MODES = 14;

    /**
     * @var Lookup-table for blend mode names.
     */
    public static $blendmodes = [
        self::BLEND_NORMAL => "Normal",
        self::BLEND_ADD => "Add",
        self::BLEND_SUBTRACT => "Subtract",
        self::BLEND_MULTIPLY => "Multiply",
        self::BLEND_SCREEN => "Screen",
        self::BLEND_OVERLAY => "Overlay",
        self::BLEND_SOFT_LIGHT => "Soft Light",
        self::BLEND_HARD_LIGHT => "Hard Light",
        self::BLEND_DIVIDE => "Divide",
        self::BLEND_DIFFERENCE => "Difference",
        self::BLEND_DARKEN_ONLY => "Darken Only",
        self::BLEND_LIGHTEN_ONLY => "Lighten Only",
        self::BLEND_BURN => "Burn",
        self::BLEND_DODGE => "Dodge"
    ];

    private $layers;

    public function __construct($width,$height,Canvas $background=null) {
        $this->width = $width;
        $this->height = $height;
        $this->background = $background;
        $this->layers = [];
    }
    
    public function addLayer(Canvas $canvas, Rect $position, $blend = self::BLEND_NORMAL, $opacity = 1.0) {
    
        array_push($this->layers,(object)[
            "canvas" => $canvas,
            "blend" => $blend,
            "opacity" => max(0.0,min($opacity,1.0)),
            "position" => $position
        ]);
    
    }
    
    public function offsetExists($index) {
        return array_key_exists($index,$this->layers);
    }
    
    public function offsetSet($index,$value) {
        $this->layers[$index] = $value;
    }
    
    public function offsetGet($index) {
        return $this->layers[$index];
    }
    
    public function offsetUnset($index) {
        unset($this->layers[$index]);
        sort($this->layers);
    }
    
    /**
     * Merge all the layers and make it the new background.
     */
    public function flatten() {
        $this->background = $this->getCompositeCanvas();
        $this->layers = [];
    }
    
    /**
     * Get the composited canvas.
     *
     */
    public function getCompositeCanvas() {

        // Create our target canvas
        $canvas = new Canvas($this->width, $this->height, 0x0);
        $crect = $canvas->getRect();

        // Precalculate some stuff for performance
        $samesize = [];
        foreach($this->layers as $i=>$layer) {
            $samesize[$i] = ($layer->position == $layer->canvas->getRect());
        }

        // Process the composite canvas        
        for($n = 0; $n < $this->width; $n++) {
            for($m = 0; $m < $this->height; $m++) {
                if ($this->background)
                    $co = $this->background->getPixel($n,$m);
                else
                    $co = 0x0;
                foreach($this->layers as $i=>$layer) {
                    //printf("Pre [%d] (mode=%d): 0x%06x ", $i, $layer->blend, $co);
                    if ($layer->position->isXYWithin($n,$m)) {
                        $co = $this->blend(
                            $co,
                            $layer->canvas->getPixel($n,$m),
                            $layer->blend,
                            $layer->opacity);
                    }
                    //printf("Post: 0x%06x\n", $co);
                }
                $canvas->setPixel($n,$m,$co);
            }
        }
        
        return $canvas;
        
    }
    
    /**
     * Apply the blend operation on RGB values.
     *
     *
     */
    public function blend($c1, $c2, $blend, $opacity = 1.0) {

        //printf("%08x | %08x -> ", $c1, $c2);
        list($b1,$g1,$r1,$a1) = [ ($c1 & 0xFF),
                                  (($c1 >> 8) & 0xFF), 
                                  (($c1 >> 16) & 0xFF),
                                  (($c1 >> 24) & 0xFF) ];
        list($b2,$g2,$r2,$a2) = [ ($c2 & 0xFF),
                                  (($c2 >> 8) & 0xFF), 
                                  (($c2 >> 16) & 0xFF),
                                  (($c2 >> 24) & 0xFF) ];
        
        //printf("%.1f %.1f %.1f %.1f | %.1f %.1f %.1f %.1f | (%d)\n", $r1,$g1,$b1,$a1,$r2,$g2,$b2,$a2,$blend);

        $oo = min(1.0,max(0.0,$opacity));
        $ao = 0xFF;
        
        
        $ro = BlendFuncs::blend($r1,$r2,$blend);
        $go = BlendFuncs::blend($g1,$g2,$blend);
        $bo = BlendFuncs::blend($b1,$b2,$blend);
        
        //printf(" → %.1f %.1f %.1f %.1f\n", $ro,$go,$bo,0x7F);
        $ro = (int)(($r1*(1-$oo))+($ro*$oo));
        $go = (int)(($g1*(1-$oo))+($go*$oo));
        $bo = (int)(($b1*(1-$oo))+($bo*$oo));
        
        return $bo | ($go << 8) | ($ro << 16); // | ($ao << 24);
        
    }

}

class CompositorLayer {
    private $filters = [];
    public function addLayerFilter(LayerFilter $filter) {
        $this->filters[] = $filter;
    }
}

class BlendFuncs {
    /**
     * Blend two values according to the blend operation.
     *
     */
    public static function blend($v1,$v2,$blend) {

        $v1 = floatval($v1/255);
        $v2 = floatval($v2/255);

        switch($blend) {
            case LayeredCanvas::BLEND_ADD:
                $o = $v1 + $v2;
                break;
            case LayeredCanvas::BLEND_SUBTRACT:
                $o = $v2-$v1;
                break;
            case LayeredCanvas::BLEND_MULTIPLY:
                $o = $v1 * $v2;
                break;
            case LayeredCanvas::BLEND_SCREEN:
                $o = 1 - (1 - $v1) * (1 - $v2);
                break;
            case LayeredCanvas::BLEND_OVERLAY:
                $o = ($v2<0.5)?(2*$v1*$v2):(1-2*(1-$v1)*(1-$v2));
                break;
            case LayeredCanvas::BLEND_SOFT_LIGHT:
                //$o = ($v2<0.5)?2*$v1*$v2+($v1*$v1)*(1-2*$v2):2*$v1*(1-$v2)+sqrt($v1*(2*$v2-1));
                $rs = 1.0 - ((1.0-$v1)*(1.0-$v2));
                $o = ((1.0-$v2)*$v1+$rs)*$v2;
                break;
            case LayeredCanvas::BLEND_HARD_LIGHT:
                $o = ($v1<0.5)?(2*$v1*$v2):(1.0-(1.0-2.0*($v2-0.5))*(1.0-$v1));
                break;
            case LayeredCanvas::BLEND_DIVIDE:
                $o = ($v1>0)?($v2/$v1):0.0;
                break;
            case LayeredCanvas::BLEND_DIFFERENCE:
                $o = ($v1>$v2)?$v1-$v2:$v2-$v1;
                break;
            case LayeredCanvas::BLEND_DARKEN_ONLY:
                $o = min($v1,$v2);
                break;
            case LayeredCanvas::BLEND_LIGHTEN_ONLY:
                $o = max($v1,$v2);
                break;
            case LayeredCanvas::BLEND_BURN:
                $o = 1.0-((1.1*(1.0-$v1))/($v2+.1));
                break;
            case LayeredCanvas::BLEND_DODGE:
                $o = (1.1*$v2)/((1.0-$v1)+1.0);
                break;
            default:
            case LayeredCanvas::BLEND_NORMAL:
                $o = $v2;
                break;
        }
        $o = max(0.0,min(1.0,$o)) * 255;
        return $o;
    }
}

class BlendFuncsI {
    /**
     * Blend two values according to the blend operation.
     *
     */
    public static function blend($v1,$v2,$blend) {

        switch($blend) {
            case LayeredCanvas::BLEND_ADD:
                $o = $v1 + $v2;
                break;
            case LayeredCanvas::BLEND_SUBTRACT:
                $o = $v2-$v1;
                break;
            case LayeredCanvas::BLEND_MULTIPLY:
                $o = $v1 * $v2;
                break;
            case LayeredCanvas::BLEND_SCREEN:
                $o = 255 - (255 - $v1) * (255 - $v2);
                break;
            case LayeredCanvas::BLEND_OVERLAY:
                $o = ($v2<127)?(510*$v1*$v2):(255-511*(255-$v1)*(255-$v2));
                break;
            case LayeredCanvas::BLEND_SOFT_LIGHT:
                //$o = ($v2<0.5)?2*$v1*$v2+($v1*$v1)*(1-2*$v2):2*$v1*(1-$v2)+sqrt($v1*(2*$v2-1));
                $rs = 255 - ((255-$v1)*(255-$v2));
                $o = ((255-$v2)*$v1+$rs)*$v2;
                break;
            case LayeredCanvas::BLEND_HARD_LIGHT:
                $o = ($v1<127)?(2*$v1*$v2):(255-(255-511*($v2-127))*(255-$v1));
                break;
            case LayeredCanvas::BLEND_DIVIDE:
                $o = ($v1>0)?($v2/$v1):0.0;
                break;
            case LayeredCanvas::BLEND_DIFFERENCE:
                $o = ($v1>$v2)?$v1-$v2:$v2-$v1;
                break;
            case LayeredCanvas::BLEND_DARKEN_ONLY:
                $o = min($v1,$v2);
                break;
            case LayeredCanvas::BLEND_LIGHTEN_ONLY:
                $o = max($v1,$v2);
                break;
            case LayeredCanvas::BLEND_BURN:
                $o = 255-((256*(255-$v1))/($v2+1));
                break;
            case LayeredCanvas::BLEND_DODGE:
                $o = (256*$v2)/((255-$v1)+255);
                break;
            default:
            case LayeredCanvas::BLEND_NORMAL:
                $o = $v1;
                break;
        }
        $o = (int)max(0,min(255,$o));
        return $o;
    }
}

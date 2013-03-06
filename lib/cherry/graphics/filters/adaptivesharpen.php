<?php

namespace Cherry\Graphics\Filters;

class AdaptiveSharpen implements IFilter {

    private $radius;
    private $sigma;

    public function __construct($radius = 2, $sigma = 1) {
        $this->radius = $radius;
        $this->sigma = $sigma;
    }

    public function applyImageFilter(Canvas $canvas, Rect $rect=null) {
        if ($rect) {
            $image = $canvas->getImageRect($rect);
        } else {
            $image = $canvas;
        }
        $im = $image->toImagick();
        $im->adaptiveSharpenImage($this->radius,$this->sigma);
        $image->fromImagick($im);
        
        if ($rect) {
            // Draw dest onto canvas
            $c = new Canvas();
            $c->fromImagick($image);
            $c->draw($rect);
        } else {
            $canvas = $image;
        }
        return $canvas;
    }


}

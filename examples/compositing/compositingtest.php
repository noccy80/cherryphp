<?php

require_once "cherryphp";

use Cherry\Types\Rect;
use Cherry\Graphics\Canvas;
use Cherry\Graphics\Color;
use Cherry\Graphics\LayeredCanvas;
use Cherry\Graphics\Font\BitmapFont;

class CompositingTest {

    private $status;

    public function test() {

        $this->status = "Setting up";
        $max = 5*LayeredCanvas::NUM_BLEND_MODES;
        $this->doProgress(0,$max);

        // Set up a layered canvas for compositing our image
        $sw = 320; // $this->rect->w;
        $ssw = $sw/5;
        $sh = 240;
        $c = new Canvas($sw*4,$sh*4,0);
        $f = new BitmapFont(3);
        $f2 = new BitmapFont(0);
        $fb = new BitmapFont(7);
        $fb->drawText($c,$c->width-$sw+20,$c->height-50,"Cherry LayeredCanvas Demo",0xFFFFFF);
        $f->drawText($c,$c->width-$sw+20,$c->height-30,"Showing all ".LayeredCanvas::NUM_BLEND_MODES." available blend modes",0xFFFFFF);

        // Preload our canvases
        $c2 = Canvas::createFromFile("320x240-1.png");
        $c1 = Canvas::createFromFile("320x240-2.png");
        $cs1 = new Canvas($ssw,$sh);
        $cs2 = new Canvas($ssw,$sh);

        for ($blend = 0; $blend < LayeredCanvas::NUM_BLEND_MODES; $blend++) {
            $this->status = "Rendering ".LayeredCanvas::$blendmodes[$blend];
            $tslice = [];
            for($slice = 0; $slice < 5; $slice++) {
                $c1->draw($cs1,$cs1->getRect(),new Rect($ssw*$slice,0,$ssw,$sh));
                $c2->draw($cs2,$cs2->getRect(),new Rect($ssw*$slice,0,$ssw,$sh));
                $lc = new LayeredCanvas($ssw,$sh,$cs1);
                $lc->addLayer($cs2, $cs2->getRect(), $blend, $slice*0.25);
                $tstart = microtime(true);
                $co = $lc->getCompositeCanvas();
                $tslice[] = (float)(microtime(true)-$tstart);
                $col = ($blend % 4);
                $row = floor($blend / 4);
                $ostr = sprintf("%d%%",max(0,min(100,$slice*25)));
                $f2->drawTextUp($co,$ssw-11,$sh-4,$ostr,0x000000);
                $f2->drawTextUp($co,$ssw-12,$sh-5,$ostr,0xFFFFFF);
                $co->draw(
                    $c,
                    new Rect($col*$sw+($ssw*$slice),$row*$sh,$ssw,$sh)
                );
                $this->doProgress($blend*5+$slice,$max);
            }
            $modestr = sprintf("%s",LayeredCanvas::$blendmodes[$blend]);
            $f->drawText($c,$col*$sw+6,$row*$sh+6,$modestr,0x000000);
            $f->drawText($c,$col*$sw+5,$row*$sh+5,$modestr,0xFFFFFF);
            $tslice = array_sum($tslice);
            $timestr = sprintf("Mode %d",$blend);
            $f2->drawText($c,$col*$sw+6,$row*$sh+21,$timestr,0x000000);
            $f2->drawText($c,$col*$sw+5,$row*$sh+20,$timestr,0xFFFFFF);
        }
        $this->status = "Done";
        $this->doProgress($max,$max,true);
        $c->save("composite.png");
        return;

    }

    public function doProgress($curprogress,$maxprogress,$final=false) {
    
        static $start;
        static $last;
        if (!$start) $start = microtime(true);
        $time = microtime(true);
        if (!$final)
            if ($last > $time-1) return;
        $last = $time;
        $ela = ($time-$start);
        $pct = (100/$maxprogress)*$curprogress;
        if ($final) {
            $pps = ($curprogress/$ela);
            printf("\r\033[J%s ... %d of %d (%.1f%%) %ds elapsed (%.1fpps)\n", $this->status, $curprogress, $maxprogress, $pct, $ela, $pps);
        } elseif ($ela>1) {
            $pps = ($curprogress/$ela);
            $rem = abs(($maxprogress-$curprogress)/$pps);
            printf("\r\033[J%s ... %d of %d (%.1f%%) %ds remaining", $this->status, $curprogress, $maxprogress, $pct, $rem);
        } else {
            printf("\r\033[J%s ... %d of %d (%.1f%%)", $this->status, $curprogress, $maxprogress, $pct);
        }
    
    }
    
}

$ct = new CompositingTest();
$ct->test();

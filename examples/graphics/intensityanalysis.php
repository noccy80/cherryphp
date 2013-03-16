<?php
/**
 * @todo Spectrum class should be able to ->draw() into rect
 */

use Cherry\Graphics\Canvas;
use Cherry\Cli\ConsoleApplication;

define('SPECTRUM_SIZE',256);
define('SPECTRUM_MONO',false);

require_once getenv('CHERRY_LIB') . "/lib/bootstrap.php";

function _property_exists($class,$prop) {
    return property_exists($class,$prop);
}

/**
 * @brief Property access methods.
 *
 * This trait provides extended access to public properties via getters and
 * setters. Direct access to properties are only allowed if the properties
 * are public and don't have a get/set method associated with it.
 *
 * To defined a getter for a property, define a preferably private method named
 * get{property}, for example getFoo. To define a setter, define set{property}.
 * The setter method will receive one parameter, namely the value set.
 */
trait PropertyMethods {

    public function __set($name,$value) {
        $setter = "set{$name}";
        if (is_callable([$this,$setter])) {
            return $this->{$setter}($value);
        } else {
            $propvar = '_'.$name;
            if (_property_exists($this,$propvar)) {
                $this->{$propvar} = $value;
                return true;
            }
        }
        define('ERROR_POPBACK',true);
        user_error("No such property {$name}");
    }

    public function __get($name) {
        $getter = "get{$name}";
        if (is_callable([$this,$getter])) {
            return $this->{$getter}();
        } else {
            $propvar = '_'.$name;
            if (_property_exists($this,$propvar)) {
                return $this->{$propvar};
            }
        }
        define('ERROR_POPBACK',true);
        user_error("No such property {$name}");
    }

}

class SpectrumAnalysis {

    use PropertyMethods;

    private $img;
    private $xbuckets;
    private $ybuckets;

    public $spectrumsize = 256;
    public $color = true;

    private function setColor($color) {
        $this->color = ($color==true);
    }
    private function setSpectrumSiz($size) {
        $this->spectrumsize = ($size>256?256:($size<0?0:$size));
    }

    public function __construct($filename) {
        // Load the image to analyze from disk
        $this->img = Canvas::createFromFile($filename);
        $this->log("Loaded canvas of [{$this->img->width}x{$this->img->height}] for analysis");
    }

    public function analyze() {
        // Now scan every row and measure the intensity
        $ybucket = []; $ybucketr = []; $ybucketg = []; $ybucketb = [];
        $this->log("Analyzing image rows...");
        for($y = 0; $y < $this->img->height; $y++) {
            // Prepare the spectrum buckets
            $sb = []; for($n = 0; $n < SPECTRUM_SIZE; $n++) $sb[$n] = 0;
            $scr = []; for($n = 0; $n < SPECTRUM_SIZE; $n++) $scr[$n] = 0;
            $scb = []; for($n = 0; $n < SPECTRUM_SIZE; $n++) $scb[$n] = 0;
            $scg = []; for($n = 0; $n < SPECTRUM_SIZE; $n++) $scg[$n] = 0;
            $sum = 0;
            $rb = 0; $gb = 0; $bb = 0;
            for($x = 0; $x < $this->img->width; $x++) {
                $c = $this->img->getPixel($x,$y);
                $r = $c & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = ($c >> 16) & 0xFF;
                //echo "Grab color {$c} [{$r},{$g},{$b}]\n";
                $avg = ($r + $g + $b) / 3;
                $sb[floor($avg/(256/SPECTRUM_SIZE))]++;
                $scr[floor($r/(256/SPECTRUM_SIZE))]++;
                $scg[floor($g/(256/SPECTRUM_SIZE))]++;
                $scb[floor($b/(256/SPECTRUM_SIZE))]++;
            }
            $ybucket[$y] = $sb;
            $ybucketr[$y] = $scr;
            $ybucketg[$y] = $scg;
            $ybucketb[$y] = $scb;
        }

        $this->ybuckets = [
            $ybucket,
            $ybucketr,
            $ybucketg,
            $ybucketb
        ];

        // And every column
        $xbucket = []; $xbucketr = []; $xbucketg = []; $xbucketb = [];
        $this->log("Analyzing image columns...");
        for($x = 0; $x < $this->img->width; $x++) {
            $sb = []; for($n = 0; $n < SPECTRUM_SIZE; $n++) $sb[$n] = 0;
            $scr = []; for($n = 0; $n < SPECTRUM_SIZE; $n++) $scr[$n] = 0;
            $scb = []; for($n = 0; $n < SPECTRUM_SIZE; $n++) $scb[$n] = 0;
            $scg = []; for($n = 0; $n < SPECTRUM_SIZE; $n++) $scg[$n] = 0;
            $sum = 0;
            $rb = 0; $gb = 0; $bb = 0;
            for($y = 0; $y < $this->img->height; $y++) {
                $c = $this->img->getPixel($x,$y);
                $r = $c & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = ($c >> 16) & 0xFF;
                //echo "Grab color {$c} [{$r},{$g},{$b}]\n";
                $avg = ($r + $g + $b) / 3;
                $sb[floor($avg/(256/SPECTRUM_SIZE))]++;
                $scr[floor($r/(256/SPECTRUM_SIZE))]++;
                $scg[floor($g/(256/SPECTRUM_SIZE))]++;
                $scb[floor($b/(256/SPECTRUM_SIZE))]++;
            }
            $xbucket[$x] = $sb;
            $xbucketr[$x] = $scr;
            $xbucketg[$x] = $scg;
            $xbucketb[$x] = $scb;
        }

        $this->xbuckets = [
            $xbucket,
            $xbucketr,
            $xbucketg,
            $xbucketb
        ];

    }

    private function drawSpectrumY($wi,$buckets,$drect) {

        list($ybucket,$ybucketr,$ybucketg,$ybucketb) = $buckets;

        $this->log("Drawing spectrum...");

        // Get the max values
        $max = max(max($ybucket));
        $maxr = max(max($ybucketr));
        $maxg = max(max($ybucketg));
        $maxb = max(max($ybucketb));

        $s = ($this->img->width/$max);
        for($y = 0; $y < $this->img->height; $y++) {
            for($n = 0; $n < SPECTRUM_SIZE; $n++) {
                if (!$this->color) {
                    $c = $ybucket[$y][$n]*$s;
                    //$c = (255/sqrt($max*2))*sqrt($c);
                    $c = ($c>255)?255:$c;
                    $wi->setPixel($n,$y+$drect->y,[$c,$c,$c]);
                } else {
                    $cr = $ybucketr[$y][$n]*$s;
                    //$cr = (255/sqrt($maxr))*sqrt($cr);
                    $cr = ($cr>255)?255:$cr;
                    $cg = $ybucketg[$y][$n]*$s;
                    //$cg = (255/sqrt($maxg))*sqrt($cg);
                    $cg = ($cg>255)?255:$cg;
                    $cb = $ybucketb[$y][$n]*$s;
                    //$cb = (255/sqrt($maxb))*sqrt($cb);
                    $cb = ($cb>255)?255:$cb;
                    $wi->setPixel($n,$y+$drect->y,[$cr,$cg,$cb]);
                }
            }
        }
    }

    private function drawSpectrumX($wi,$buckets,$drect) {

        list($xbucket,$xbucketr,$xbucketg,$xbucketb) = $buckets;

        // Get the max values
        $max = max(max($xbucket));
        $maxr = max(max($xbucketr));
        $maxg = max(max($xbucketg));
        $maxb = max(max($xbucketb));

        // We can't have more than height samples per bucket.
        $s = ($this->img->height/$max);
        for($x = 0; $x < $this->img->width; $x++) {
            for($n = 0; $n < SPECTRUM_SIZE; $n++) {
                if (!$this->color) {
                    $c = $xbucket[$x][$n]*$s;
                    //$c = (255/sqrt($max*2))*sqrt($c);
                    $c = ($c>255)?255:$c;
                    $wi->setPixel($x+$drect->x,$n,[$c,$c,$c]);
                } else {
                    $cr = $xbucketr[$x][$n]*$s;
                    //$cr = (255/sqrt($maxr))*sqrt($cr);
                    $cr = ($cr>255)?255:$cr;
                    $cg = $xbucketg[$x][$n]*$s;
                    //$cg = (255/sqrt($maxg))*sqrt($cg);
                    $cg = ($cg>255)?255:$cg;
                    $cb = $xbucketb[$x][$n]*$s;
                    //$cb = (255/sqrt($maxb))*sqrt($cb);
                    $cb = ($cb>255)?255:$cb;
                    $wi->setPixel($x+$drect->x,$n,[$cr,$cg,$cb]);
                }
            }
        }
    }

    public function saveComposite($filename) {
        // Measure the image and create an image 32x32 pixels wider than the source
        $ir = $this->img->measure();
        $wi = Canvas::createTrueColor($ir->w+SPECTRUM_SIZE+4,$ir->h+SPECTRUM_SIZE+4);

        // Draw the image into the new working image offset by 32x32 puxels
        $srect = $this->img->measure();
        $drect = $this->img->measure();
        $drect->move(SPECTRUM_SIZE+4,SPECTRUM_SIZE+4);
        $this->img->draw($wi,$drect,$srect);
        $wi->drawLine(0,$drect->y - 2,$wi->width,$drect->y - 2,[255,255,255]);
        $wi->drawLine($drect->x - 2,0,$drect->x - 2,$wi->height,[255,255,255]);
        $wi->drawFilledRect(0,0,$drect->x - 4, $drect->y - 4, [64,64,64]);
        $this->drawSpectrumX($wi,$this->xbuckets,$drect);
        $this->drawSpectrumY($wi,$this->ybuckets,$drect);

        // Save the working image
        $wi->save($filename);
    }

    private function log($str) {
        if (App::app())
            App::app()->log($str);
    }

}

class AnalysisApplication extends ConsoleApplication {

    public function getApplicationInfo() {
        return [
            'appname' => 'IAT Spectrum',
            'version' => '1.0.0',
            'description' => 'Image Analysis Toolkit: Spectrum Analyzer'
        ];
    }

    public function usageinfo() {
        echo "This tool will analyze an image and render appropriate spectrum graphs\n";
        echo "representing the intensity of either the monochrome colors or the three\n";
        echo "color channels red, green and blue.\n\n";
    }

    public function init() {
        $this->addArgument("h","help","Show this help");
        $this->addArgument("b:","boost","Boost values (specify as float)");
        $this->addArgument("s:","size","Size of the spectrum (default: 256)");
        $this->addArgument("x",null,"Apply to X axis only (default: both)");
        $this->addArgument("y",null,"Apply to Y axis only (default: both)");
        $this->addArgument("m","mono","Render a monochrome spectrum (default: color)");
    }

    public function main() {
        if ($this->hasArgument('h') || (count($this->parameters)<2)) {
            $this->usage();
            return;
        }

        // Get info
        $fin = $this->parameters[0];
        $fout = $this->parameters[1];
        if ($fout == $fin) die("Can't overwrite input\n");

        echo "Processing file: {$fin}\n";
        $sa = new SpectrumAnalysis($fin);
        $sa->spectrumsize = ($this->hasArgument('s')?$this->getArgument('s'):255);
        $sa->color = (!$this->hasArgument('m'));
        $sa->analyze();
        $sa->saveComposite($fout);
    }

}

\App::run(new AnalysisApplication());

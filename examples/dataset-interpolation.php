<?php

require_once "xenon/xenon.php";
xenon\xenon::framework("cherryphp");

use Cherry\Data\DataSet;
use Cherry\Data\DataSetValueInterpolator\LinearInterpolator;
use Cherry\Graphics\Canvas;
use Cherry\Graphics\Font\BitmapFont;

class DatasetInterpolationExample extends \Cherry\Cli\ConsoleApplication {
    private $ds;
    private function populate() {
        // Stuff some random numbers in there
        for($n = 0; $n <= 50; $n++) {
            $val = rand(0,45);
            $this->ds->addRow([
                'time' => $n*50,
                'value' => $val
            ]);
        }
    }
    private function drawchart($cv,$interpolation,$label,$offset,$color) {

        // We also need a font
        $bf = new BitmapFont(1);
        $bfc = new BitmapFont(2);

        $ip1 = $this->ds->getSampleSet("time","value")->getValueInterpolator($interpolation);
        // Now, let's plot 1000 points of data; we do this by using getValueAt() and
        // then call the drawLineTo() function which will either set the starting point
        // to draw from (in its internal state) or draw a line from the last point (or
        // starting point).
        for($n = 0; $n < 1000; $n++) {
            $v1 = $ip1->getValueAt($n);
            $xv = $offset + 200 - ($v1*3) - 10;
            $cv->drawLineTo($n,$xv,$color);
            if (!($n % 50)) {
                $bs = 3;
                $cv->drawFilledRect($n-$bs, $xv-$bs, $n+$bs, $xv+$bs, $color);
                $bf->drawText($cv,$n+(2*$bs),$xv-$bs,sprintf("%.3f",$v1), [50,50,50]);
            }
        }
        $bfc->drawText($cv,5,$offset+5,$label,[0,0,0]);
        // When we are done, we end the line sequence so that the next call to
        // drawLineTo will start a new line.
        $cv->drawLineEnd();

    }
    public function main() {

        // Create a new dataset
        $this->ds = new DataSet();
        $this->ds->setColumnType("time",DataSet::TYPE_SCALE);
        $this->ds->setColumnType("value",DataSet::TYPE_INTEGER);

        // Populate the dataset
        $this->populate();

        // We need a canvas, so let's make us one!
        $cv = new Canvas();
        $cv->create(1000,600,[255,255,255]);

        // Linear interpolation.
        $this->drawchart($cv,"linear","Linear Interpolation", 0, [150,0,0]);
        // Rinse and repeat, this time for the cosine interpolator. This one gives us
        // slightly softer curves. The drawing and fetching is the same.
        $this->drawchart($cv,"cosine","Cosine Interpolation", 200, [0,150,0]);
        $this->drawchart($cv,"cubic","Cubic Interpolation", 400, [0,0,150]);

        // Separate our graphs
        foreach([200,400,600] as $divx)
            $cv->drawLine(0,(int)$divx,1000,(int)$divx,[100,100,100]);

        if (_IS_CLI_SERVER) {
            $cv->output("png");
        }
        // Save the rendered canvas
        $cv->save("interpolation.png");

    }
}

\App::run(new DatasetInterpolationExample(""));

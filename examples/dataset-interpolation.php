<?php

require_once "xenon/xenon.php";
xenon\xenon::framework("cherryphp");

use Cherry\Data\DataSet;
use Cherry\Data\DataSetValueInterpolator\LinearInterpolator;
use Cherry\Graphics\Canvas;
use Cherry\Graphics\Font\BitmapFont;

// Create a new dataset
$ds = new DataSet();
$ds->setColumnType("time",DataSet::TYPE_SCALE);
$ds->setColumnType("value",DataSet::TYPE_INTEGER,"linear");
$ds->setColumnType("value2",DataSet::TYPE_INTEGER,"cosine");
$ds->setColumnType("value3",DataSet::TYPE_INTEGER,"cosine");
$ds->setColumnType("value4",DataSet::TYPE_INTEGER,"cosine");

// Stuff some random numbers in there
for($n = 0; $n <= 50; $n++) {
    $val = rand(0,45);
    $ds->addRow([ 'time' => $n*50,
                 'value' => $val,
                 'value2' => $val,
                 'value3' => $val,
                 'value4' => $val
    ]);
}

// We need a canvas, so let's make us one!
$cv = new Canvas();
$cv->create(1000,800,[255,255,255]);

// We also need a font
$bf = new BitmapFont(1);
$bfc = new BitmapFont(3);

// Now, let's grab the interpolator for the value column, which we previously
// set to 'linear'. This should give us a linear interpolation of any value
// along the scale axis.
$ip1 = $ds->getValueInterpolator("value","time");
// Now, let's plot 1000 points of data; we do this by using getValueAt() and
// then call the drawLineTo() function which will either set the starting point
// to draw from (in its internal state) or draw a line from the last point (or
// starting point). 
for($n = 0; $n < 1000; $n++) {
    $v1 = $ip1->getValueAt($n);
    $xv = 200 - ($v1*4);
    $cv->drawLineTo($n,$xv,[255,200,200]);
    if (!($n % 50)) {
        $bs = 3;
        $cv->drawFilledRect($n-$bs, $xv-$bs, $n+$bs, $xv+$bs, [255,200,200]);
        $bf->drawText($cv,$n+(2*$bs),$xv-$bs,sprintf("%.3f",$v1), [50,50,50]);
    }
}
$bfc->drawText($cv,5,5,"Linear Interpolation",[0,0,0]);
// When we are done, we end the line sequence so that the next call to 
// drawLineTo will start a new line.
$cv->drawLineEnd();

// Rinse and repeat, this time for the cosine interpolator. This one gives us
// slightly softer curves. The drawing and fetching is the same.
$ip2 = $ds->getValueInterpolator("value2","time");
for($n = 0; $n < 1000; $n++) {
    $v2 = $ip2->getValueAt($n);
    $xv = 200+200 - ($v2*4)-5;
    $cv->drawLineTo($n,$xv,[0,0,255]);
    if (!($n % 50)) {
        $bs = 3;
        $cv->drawFilledRect($n-$bs, $xv-$bs, $n+$bs, $xv+$bs, [0,0,255]);
        $bf->drawText($cv,$n+(2*$bs),$xv-$bs,sprintf("%.3f",$v2), [50,50,50]);
    }
}
$bfc->drawText($cv,5,205,"Cosine Interpolation",[0,0,0]);
// End the line sequence again.
$cv->drawLineEnd();

// Rinse and repeat, this time with the cubic interpolation
$ip2 = $ds->getValueInterpolator("value3","time");
for($n = 0; $n < 1000; $n++) {
    $v2 = $ip2->getValueAt($n);
    $xv = 400+200 - ($v2*4)-5;
    $cv->drawLineTo($n,$xv,[0,255,0]);
    if (!($n % 50)) {
        $bs = 3;
        $cv->drawFilledRect($n-$bs, $xv-$bs, $n+$bs, $xv+$bs, [0,255,0]);
        $bf->drawText($cv,$n+(2*$bs),$xv-$bs,sprintf("%.3f",$v2), [50,50,50]);
    }
}
$bfc->drawText($cv,5,405,"Cubic Interpolation",[0,0,0]);
// End the line sequence again.
$cv->drawLineEnd();

// Rinse and repeat, this time with the catmull-rom interpolation
$ip2 = $ds->getValueInterpolator("value4","time");
for($n = 0; $n < 1000; $n++) {
    $v2 = $ip2->getValueAt($n);
    $xv = 600+200 - ($v2*4)-5;
    $cv->drawLineTo($n,$xv,[200,200,0]);
    if (!($n % 50)) {
        $bs = 3;
        $cv->drawFilledRect($n-$bs, $xv-$bs, $n+$bs, $xv+$bs, [200,200,0]);
        $bf->drawText($cv,$n+(2*$bs),$xv-$bs,sprintf("%.3f",$v2), [50,50,50]);
    }
}
$bfc->drawText($cv,5,605,"Catmull-Rom Interpolation",[0,0,0]);
// End the line sequence again.
$cv->drawLineEnd();

// Separate our graphs
foreach([200,400,600] as $divx)
    $cv->drawLine(0,(int)$divx,1000,(int)$divx,[100,100,100]);


// Save the rendered canvas
$cv->save("interpolation.png");


<?php

namespace Cherry\Data\DataSetValueInterpolator;

/**
 *
 * Paul Breeuwsma proposes the following coefficients for a smoother
 * interpolated curve, which uses the slope between the previous
 * point and the next as the derivative at the current point. This
 * results in what are generally referred to as Catmull-Rom splines.
 *
 *   a0 = -0.5*y0 + 1.5*y1 - 1.5*y2 + 0.5*y3;
 *   a1 = y0 - 2.5*y1 + 2*y2 - 0.5*y3;
 *   a2 = -0.5*y0 + 0.5*y2;
 *   a3 = y1;
 *
 * Source: http://paulbourke.net/miscellaneous/interpolation/
 */

use Cherry\Data\DataSetValueInterpolator;

class CatmullRomInterpolator extends DataSetValueInterpolator {

    public function interpolate($p1,$p2,$mu) {
        $mu2 = $mu * $mu;
        $p0 = $p1->getPreviousPoint();
        $p3 = $p2->getNextPoint();
        $a0 = -0.5*$p0->value + 1.5*$p1->value - 1.5*$p2->value + 0.5*$p3->value;
        $a1 = $p0->value - 2.5*$p1->value + 2*$p2->value - 0.5*$p3->value;
        $a2 = -0.5*$p0->value + 0.5*$p2->value;
        $a3 = $p1->value;
        return
            ($a0 * $mu * $mu2 + $a1 * $mu2 + $a2 * $mu + $a3);
    }

    public function _interpolate($p1,$p2,$mu) {
        $mu2 = (1-cos($mu*PI))/2;
        return
            ($p1->value*(1-$mu2)+$p2->value*$mu2);
    }

}


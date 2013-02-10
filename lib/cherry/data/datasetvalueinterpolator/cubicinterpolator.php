<?php

namespace Cherry\Data\DataSetValueInterpolator;

/**
 *
 * double CubicInterpolate(
 *    double y0,double y1,
 *    double y2,double y3,
 *    double mu)
 * {
 *    double a0,a1,a2,a3,mu2;
 *
 *    mu2 = mu*mu;
 *    a0 = y3 - y2 - y0 + y1;
 *    a1 = y0 - y1 - a0;
 *    a2 = y2 - y0;
 *    a3 = y1;
 *
 *    return(a0*mu*mu2+a1*mu2+a2*mu+a3);
 * }
 *
 * Source: http://paulbourke.net/miscellaneous/interpolation/
 */

use Cherry\Data\DataSetValueInterpolator;

class CubicInterpolator extends DataSetValueInterpolator {

    public function interpolate($p1,$p2,$mu) {
        $mu2 = $mu * $mu;
        $p0 = $p1->getPreviousPoint();
        $p3 = $p2->getNextPoint();
        $a0 = $p3->value - $p2->value - $p0->value + $p1->value;
        $a1 = $p0->value - $p1->value - $a0;
        $a2 = $p2->value - $p0->value;
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


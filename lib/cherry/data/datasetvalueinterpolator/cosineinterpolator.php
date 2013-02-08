<?php

namespace Cherry\Data\DataSetValueInterpolator;

if (!defined("PI")) define("PI",3.14159265358979323846);

use Cherry\Data\DataSetValueInterpolator;

class CosineInterpolator extends DataSetValueInterpolator {

    public function interpolate($p1,$p2,$mu) {
        $mu2 = (1-cos($mu*PI))/2;
        return
            ($p1->value*(1-$mu2)+$p2->value*$mu2);
    }

}


<?php

namespace Cherry\Data\DataSetValueInterpolator;

use Cherry\Data\DataSetValueInterpolator;

class LinearInterpolator extends DataSetValueInterpolator {

    public function interpolate($p1,$p2,$mu) {
        return
            ($p1->value * (1 - $mu) + $p2->value * $mu);
    }

}



<?php

namespace Cherry\Graphics\Dither;

class OrderedDither extends Dither implements ITrueColorDither,IOrderedDither {
    public static
        $mthreshold2x2 = [
            [ 1, 3 ],
            [ 4, 2 ]
        ],
        $mthreshold3x3 = [
            [ 3, 7, 4 ],
            [ 6, 1, 9 ],
            [ 2, 8, 5 ]
        ],
        $mthreshold4x4 = [
            [ 1,  9,  3, 11 ],
            [ 13, 5, 15,  7 ],
            [ 4,  12, 2, 10 ],
            [ 16, 8, 14,  6 ]
        ];
    private
        $matrix = [],
        $bias = 0,
        $adjust = 1;
    const
        ODT_2X2 = 1,
        ODT_3X3 = 2,
        ODT_4x4 = 3;
    public function __construct(array $matrix) {
        $max = max(max($matrix));
        $this->bias = $max / 2;
        $this->adjust = $this->bias / 2;
        $this->matrix = $matrix;
    }
    protected function ditherFunc($x,$y) {
        $x = $x % 3;
        $y = $y % 3;
        $tm = ($this->matrix[$x][$y] - $this->bias) / $this->adjust;
        $this->r = $this->r + $tm;
        $this->g = $this->g + $tm;
        $this->b = $this->b + $tm;
    }
}

<?php

namespace Cherry\Data\Dataset;

use Cherry\Data\Interpolator\Interpolator;

/*
 * class DataSet
 */
class DataSet {

    /** @var The interpolator to use when retrieving values */
    private $_interpolator;

    function __construct() {

    }

    /**
     * @brief Function invocation handler.
     *
     * Allows us to use the DataSet to provide arbitrary values from the set,
     * both raw and interpolated:
     *
     * @code
     *   $ds = $obj->getDataSet();
     *   for( $n = -5; $n < 5; $n += .05 ) {
     *       plot($n,$ds($n));
     *   }
     * @endcode
     */
    public function __invoke($args) {
        list($v0,$v1) = $this->findIndexes($args[0]);
    }

    public function findIndexes($value) {
        $ci = $this->_arrGetClosest($vale,$this->_index);
        if ($this->_index[$ci] > $value) {
            if ($ci < count($this->_index)-1) return [ $ci, NULL ];
            return [ $ci, ]
        }
    }
    private function _arrGetClosest($search, $arr) {
        $closest = null; $cidx = null;
        foreach($arr as $idx=>$item) {
            if($closest == null || abs($search - $closest) > abs($item - $search)) {
                $closest = $item;
                $cidx = $idx;
            }
        }
        return $cidx;
    }

    public function setIndexScale($scale = 1.0) {

    }

    public function setInterpolator(Interpolator $i) {
        $this->_interpolator = $i;
    }

    public function getInterpolator() {
        return $this->_interpolator;
    }

}

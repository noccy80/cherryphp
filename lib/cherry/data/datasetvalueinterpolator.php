<?php

namespace Cherry\Data;

/**
 * Algorithms from http://paulbourke.net/miscellaneous/interpolation/
 *
 */
abstract class DataSetValueInterpolator {
    protected $sampleset = null;
    protected $scalecolumn = null;
    protected $valuecolumn = null;
    public static function factory(SampleSet $ss, $type, $scalecolumn, $valuecolumn) {
        $base = "\\Cherry\\Data\\DataSetValueInterpolator\\";
        switch($type) {
            case DataSet::IPF_LINEAR:
                $cn = "{$base}LinearInterpolator";
                break;
            case DataSet::IPF_COSINE:
                $cn = "{$base}CosineInterpolator";
                break;
            case DataSet::IPF_CUBIC:
                $cn = "{$base}CubicInterpolator";
                break;
            case DataSet::IPF_CATMULL_ROM:
                $cn = "{$base}CatmullRomInterpolator";
                break;
            case null:
                throw new \UnexpectedValueException("Factory can't create {$type} of ".__CLASS__);
            default:
                $cn = $type;
        }
        return new $cn($ss,$scalecolumn,$valuecolumn);
    }
    public function __construct(SampleSet $ss, $scalecolumn, $valuecolumn) {
        $this->sampleset = $ss;
        $this->scalecolumn = $scalecolumn;
        $this->valuecolumn = $valuecolumn;
    }
    public function getValueAt($time) {
        $p0 = $this->sampleset->getNearestSamplePoint($time,0);
        if ($p0->time == $time) return $p0->value;
        // Find the two points next to time
        $p1 = $this->sampleset->getNearestSamplePoint($time,-1);
        $p2 = $this->sampleset->getNearestSamplePoint($time,1);
        // Get the time offsets
        $t1 = $p1->time;
        $t2 = $p2->time;
        // Get the time difference
        $td = $t2 - $t1;
        // Get mu
        $mu = (float)((1 / $td) * ($time - $t1));
        return $this->interpolate($p1,$p2,$mu);
    }
    abstract function interpolate($p1,$p2,$mu);
}


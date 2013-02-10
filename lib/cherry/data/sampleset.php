<?php

namespace Cherry\Data;

class SampleSet {
    public $dataset = null;
    public $timecolumn = null;
    public $valuecolumn = null;
    public function __construct(DataSet $ds, $timecolumn, $valuecolumn) {
        $this->dataset = $ds;
        $this->timecolumn = $timecolumn;
        $this->valuecolumn = $valuecolumn;
    }
    public function getValueInterpolator($interpolator='linear') {
        \debug("Creating interpolator for {$this->valuecolumn} (vs {$this->timecolumn})");
        // Then we try to create an interpolator
        $sc = $this->timecolumn;
        $vc = $this->valuecolumn;
        $it = $interpolator; // $this->columns[$valuecolumn]->interpolator;
        $ip = DataSetValueInterpolator::factory($this,$it,$sc,$vc);
        // Did we get an interpolator back?
        if (!$ip) {
            return null;
        }
        // Otherwise, return the interpolator.
        return $ip;
    }
    public function getNearestSamplePoint($value,$offset=0) {
        // TODO: better implementation
        $dl = count($this->dataset->data);
        $min = null; $max = null;
        $nmin = null; $nmax = null;
        for($n = 0; $n < $dl; $n++) {
            $cv = $this->dataset->data[$n][$this->timecolumn];
            if ($max == null) {
                if ($cv <= $value) {
                    $min = $cv;
                    $nmin = $n;
                }
                if ($cv >= $value) {
                    $max = $cv;
                    $nmax = $n;
                    break;
                }
            } else {
                break;
            }
        }
        // If we are asking for the previous noode
        if ($offset == -1) {
            $pt = $nmin;
        } elseif ($offset == 1) {
            $pt = $nmax;
        } else {
            $dmax = $max - $value;
            $dmin = $value - $min;
            if ($dmax < $dmin)
                $pt = $nmax;
            else
                $pt = $nmin;
        }
        return new SamplePoint($this,$this->dataset->data[$pt][$this->timecolumn],$this->dataset->data[$pt][$this->valuecolumn],$pt);
    }
    public function getSamplePoint($pt) {
        if ($pt < 0) {
            $pm = 0;
            return new SamplePoint($this,$this->dataset->data[$pm][$this->timecolumn],$this->dataset->data[$pm][$this->valuecolumn],$pt);
        } elseif ($pt > count($this->dataset->data)) {
            $pm = count($this->dataset->data)-1;
            return new SamplePoint($this,$this->dataset->data[$pm][$this->timecolumn],$this->dataset->data[$pm][$this->valuecolumn],$pt);
        }
        return new SamplePoint($this,$this->dataset->data[$pt][$this->timecolumn],$this->dataset->data[$pt][$this->valuecolumn],$pt);
    }
}


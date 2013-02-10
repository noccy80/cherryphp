<?php

namespace Cherry\Data;

class SamplePoint {
    public $dataset = null;
    public $sampleset = null;
    public $time = null;
    public $value = null;
    public $index = null;
    public function __construct(SampleSet $ss,$stime,$value,$index) {
        $this->dataset = $ss->dataset;
        $this->sampleset = $ss;
        $this->time = $stime;
        $this->value = $value;
        $this->index = $index;
    }
    public function getPreviousPoint() {
        return $this->sampleset->getSamplePoint($this->index - 1);
    }
    public function getNextPoint() {
        return $this->sampleset->getSamplePoint($this->index + 1);
    }
}

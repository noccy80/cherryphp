<?php

namespace Cherry\Data;

class DataSet implements \Countable {

    const TYPE_STRING = 0x01;
    const TYPE_INTEGER = 0x10;
    const TYPE_FLOAT = 0x11;
    
    const TYPE_DATE = 0x20;
    /**
     * @var Scale is an arbitrary index value that denotes the sample time.
     */
    const TYPE_SCALE = 0x21;
    
    const IPF_LINEAR = "linear";
    const IPF_COSINE = "cosine";
    const IPF_CUBIC = "cubic";
    const IPF_CATMULL_ROM = "catmullrom";
    
    const TYPEFLAG_INTERPOLATE = 0x10;
    
    private $scalecolumn = null;
    private $columns = [];
    private $data = [];

    public function count() {
        return count($this->data);
    }

    public function setColumnType($column,$type,$ipf=null) {
        $this->columns[$column] = (object)[
            'name' => $column,
            'type' => $type,
            'interpolator' => $ipf
        ];
        if ($type & 0x20) $this->scalecolumn = $column;
    }
    
    public function addRow(array $data) {
        $this->data[] = $data;    
    }

    public function getValueInterpolator($valuecolumn,$scalecolumn=null) {
        // If no scale column assigned, we get the default one.
        if (!$scalecolumn)
            $scalecolumn = $this->scalecolumn;
        \debug("Creating interpolator for {$valuecolumn} (vs {$scalecolumn})");
        // Then we try to create an interpolator
        $sc = $scalecolumn;
        $vc = $valuecolumn;
        $it = $this->columns[$valuecolumn]->interpolator;
        $ip = DataSetValueInterpolator::factory($this,$it,$sc,$vc);
        // Did we get an interpolator back?
        if (!$ip) {
            return null;        
        }
        // Otherwise, return the interpolator.
        return $ip;
    }

    public function getNearestSamplePoint($value,$offset=0,$scalecolumn=null,$valuecolumn = null) {
        // If no scale column assigned, we get the default one.
        if (!$scalecolumn)
            $scalecolumn = $this->scalecolumn;
        // TODO: better implementation
        $dl = count($this->data);
        $min = null; $max = null;
        $nmin = null; $nmax = null;
        for($n = 0; $n < $dl; $n++) {
            $cv = $this->data[$n][$this->scalecolumn];
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
        if ($valuecolumn) {
            return new SamplePoint($this->data[$pt][$scalecolumn],$this->data[$pt][$valuecolumn]);
        } else {
            return $pt;
        }
    }

    
}

class SamplePoint {
    public $time;
    public $value;
    public function __construct($stime,$value) {
        $this->time = $stime;
        $this->value = $value;
    }
}

/**
 * Does not exist in the DataSet because it exists before the first or after
 * the last node.
 */
class VirtualSamplePoint extends SamplePoint {

}

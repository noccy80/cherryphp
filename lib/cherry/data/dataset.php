<?php

namespace Cherry\Data;

class DataSet implements \Countable {

    const TYPE_STRING = 0x01;
    const TYPE_INTEGER = 0x10;
    const TYPE_FLOAT = 0x11;
    const TYPE_DATE = 0x20;
    const TYPE_SCALE = 0x21; ///< @Scale is an arbitrary index value that denotes the sample time.

    const IPF_LINEAR = "linear";
    const IPF_COSINE = "cosine";
    const IPF_CUBIC = "cubic";
    const IPF_CATMULL_ROM = "catmullrom";

    const TYPEFLAG_INTERPOLATE = 0x10;

    private $timecolumn = null;
    private $columns = [];
    public $data = [];

    private $_def_column_options = [
        'unit' => null,
        'xlabel' => false,
        'ylabel' => false
    ];

    public function count() {
        return count($this->data);
    }

    public function setColumnType($column,$type,array $options=null) {
        $this->columns[$column] = (object)[
            'name' => $column,
            'type' => $type,
            'options' => array_merge($this->_def_column_options,(array)$options)
        ];
        if ($type & 0x20) $this->timecolumn = $column;
    }

    public function addRow(array $data) {
        $this->data[] = $data;
    }

    public function getSampleSet($timecolumn,$valuecolumn) {
        return new SampleSet($this,$timecolumn,$valuecolumn);
    }

}


<?php

namespace Cherry\Data\Charting;

use \Cherry\Graphics\IDrawable;

/**
 * @class Chart
 * @brief Renders colorful charts.
 *
 * To use, create your chart instance and then add your content
 * charts by pointing to the DataSeries for the chart.
 *
 * DataSet-->DataSeries
 *            \-->DataInterpolator
 */
class Chart implements IDrawable {

    const CHART_LINE = 0x01;
    const CHART_CURVE = 0x02;
    const CHART_PIE = 0x03;
    const CHART_BARCHART = 0x04;
    const CHART_BOX = 0x05;

    private $background = [255,255,255];

    public function __construct($width,$height) {

    }

    protected function _background_set($color) {
        $this->background = $color;
    }
    protected function _background_get() {
        return $this->background;
    }

    public function addData(SampleSet $s, $charttype) {

    }

    public function addLegend() {
    }


    public function getCanvas() {
    }
}

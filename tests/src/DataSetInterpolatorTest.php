<?php

use Cherry\Data\DataSet;
use Cherry\Data\DataSetValueInterpolator\LinearInterpolator;

class DatasetInterpolatorTest extends PHPUnit_Framework_TestCase {

    public function testLinearInterpolator() {
        $ds = new DataSet();
        $ds->setColumnType("time",DataSet::TYPE_SCALE);
        $ds->setColumnType("value",DataSet::TYPE_INTEGER,"linear");
        $ds->addRow([ 'time' => 0, 'value' => 0 ]);
        $ds->addRow([ 'time' => 10, 'value' => 10 ]);
        $ds->addRow([ 'time' => 20, 'value' => 0 ]);
        $this->assertEquals(3,count($ds),"DataSet size discrepancy.");
        $ip = $ds->getValueInterpolator("value","time");
        $this->assertInstanceOf('\Cherry\Data\DataSetValueInterpolator',$ip);
        $this->assertEquals(5,$ip->getValueAt(5),"Interpolated value #1 does not match");
        $this->assertEquals(10,$ip->getValueAt(10),"Interpolated value #2 does not match");
        $this->assertEquals(5,$ip->getValueAt(15),"Interpolated value #3 does not match");
        $this->assertEquals(0,$ip->getValueAt(20),"Interpolated value #4 does not match");
    }

}

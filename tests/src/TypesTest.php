<?php

use Cherry\Types\Point;
use Cherry\Types\Rect;

class PointTest extends \PHPUnit_Framework_TestCase {

    public function testCreateEmptyPoint() {
        $point = new Point();
        $this->assertInstanceOf('\Cherry\Types\Point',$point);
        $this->assertEquals(0,$point->x);
        $this->assertEquals(0,$point->y);
    }

    public function testCreatePoint() {
        $point = new Point(100,100);
        $this->assertEquals(100,$point->x);
        $this->assertEquals(100,$point->y);
    }

    public function testCreatePointViaHelper() {
        $point = Point::Point(100,100);
        $this->assertInstanceOf('\Cherry\Types\Point',$point);
        $this->assertEquals(100,$point->x);
        $this->assertEquals(100,$point->y);
    }

    public function testMovePoint() {
        $point = new Point(100,100);
        $point->move(50,-50);
        $this->assertEquals(150,$point->x);
        $this->assertEquals(50,$point->y);
    }

    /**
     * @dataProvider provPointDistance
     */
    public function testPointDistance($x1,$y1,$x2,$y2,$distance) {
        $p1 = new Point($x1,$y1);
        $p2 = new Point($x2,$y2);
        $calculated = $p1->getDistance($p2);
        $this->assertEquals($distance,$calculated);
    }

    public static function provPointDistance() {
        return [
            [0,0, 100,0, 100],
            [0,0, 0,100, 100],
            [0,0, 100,100, 141.42135623731]
        ];
    }

}

class RectTest extends PHPUnit_Framework_TestCase {

    public function testCreateEmptyRect() {
        $rect = new Rect();
        $this->assertInstanceOf('\Cherry\Types\Rect',$rect);
        $this->assertEquals(0,$rect->x);
        $this->assertEquals(0,$rect->y);
        $this->assertEquals(0,$rect->w);
        $this->assertEquals(0,$rect->h);
    }

    public function testCreateRect() {
        $rect = new Rect(100,100,100,100);
        $this->assertEquals(100,$rect->x);
        $this->assertEquals(100,$rect->y);
        $this->assertEquals(100,$rect->w);
        $this->assertEquals(100,$rect->h);
    }

    public function testCreateRectViaHelper() {
        $rect = Rect::Rect(100,100,200,200);
        $this->assertInstanceOf('\Cherry\Types\Rect',$rect);
        $this->assertEquals(100,$rect->x);
        $this->assertEquals(100,$rect->y);
        $this->assertEquals(200,$rect->w);
        $this->assertEquals(200,$rect->h);
    }

    public function testMoveRect() {
        $point = new Rect(100,100,100,100);
        $point->move(50,-50);
        $this->assertEquals(150,$point->x);
        $this->assertEquals(50,$point->y);
    }

}

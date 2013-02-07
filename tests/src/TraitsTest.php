<?php

class TraitTest {
    use \Cherry\Traits\SingletonAccess;
    public function foo() {
        return "bar";
    }
}

class TraitsTest extends PHPUnit_Framework_TestCase {
    public function testThatInstanceCanBeRetreved() {
        $i = TraitTest::getInstance();
        $this->assertInstanceOf('TraitTest', $i);
    }
    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testThatInstanceCanNotBeSerialized() {
        $i = TraitTest::getInstance();
        $x = serialize($i);
    }
    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testThatInstanceCanNotBeCloned() {
        $i = TraitTest::getInstance();
        $j = clone $i;
    }
}

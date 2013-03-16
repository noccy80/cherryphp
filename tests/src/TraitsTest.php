<?php

class TraitTest {
    use \Cherry\Traits\TSingletonAccess;
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
     * @expectedException \RuntimeException
     */
    public function testThatInstanceCanNotBeCloned() {
        $i = TraitTest::getInstance();
        $j = clone $i;
    }
}

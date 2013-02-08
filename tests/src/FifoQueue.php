<?php

use \Cherry\Types\Queue\FifoQueue;
use \Data\Queue;

class FifoQueueTest extends \PHPUnit_Framework_TestCase {

    public function testCreateUnlimitedQueue() {
        $queue = new FifoQueue();
        $this->assertInstanceOf('\Cherry\Types\Queue\FifoQueue',$queue);
        foreach(range(0,20) as $i) {
            $queue->push($i);
            $this->assertEquals($i+1,count($queue),"Mismatch during write");
        }
        foreach(range(0,20) as $i) {
            $v = $queue->peek();
            $this->assertEquals($i,$v,"Mismatch during compare");
            $v = $queue->pop();
            $this->assertEquals($i,$v,"Mismatch during compare");
        }
        $v = $queue->pop();
        $this->assertEquals(null,$v,"Empty queue should give null");
    }
    public function testCreateLimitedQueue() {
        $queue = new FifoQueue(10);
        $this->assertInstanceOf('\Cherry\Types\Queue\FifoQueue',$queue);
        foreach(range(0,20) as $i) {
            $queue->push($i);
            $this->assertEquals(min($i+1,10),count($queue),"Mismatch during write");
        }
        $this->assertEquals(10,count($queue));
        foreach(range(11,20) as $i) {
            $v = $queue->peek();
            $this->assertEquals($i,$v,"Mismatch during compare");
            $v = $queue->pop();
            $this->assertEquals($i,$v,"Mismatch during compare");
        }
    }
    /**
     * @expectedException OutOfBoundsException
     */
    public function testCreateLimitedQueueWithUnderflow() {
        $queue = new FifoQueue(10,FifoQueue::QUEUE_UNDERFLOW_EXCEPTION);
        $this->assertInstanceOf('\Cherry\Types\Queue\FifoQueue',$queue);
        foreach(range(0,20) as $i) {
            $queue->push($i);
            $this->assertEquals(min($i+1,10),count($queue),"Mismatch during write");
        }
        $this->assertEquals(10,count($queue));
        foreach(range(11,21) as $i) {
            $v = $queue->pop();
            $this->assertEquals($i,$v,"Mismatch during compare");
        }
    }
    /**
     * @expectedException OutOfBoundsException
     */
    public function testCreateLimitedQueueWithOverflow() {
        $queue = new FifoQueue(10,FifoQueue::QUEUE_OVERFLOW_EXCEPTION);
        $this->assertInstanceOf('\Cherry\Types\Queue\FifoQueue',$queue);
        foreach(range(0,20) as $i) {
            $queue->push($i);
            $this->assertEquals(min($i+1,10),count($queue),"Mismatch during write");
        }
    }
    public function testQueuePopAll() {
        $queue = new FifoQueue();
        $this->assertInstanceOf('\Cherry\Types\Queue\FifoQueue',$queue);
        foreach(range(0,20) as $i) {
            $queue->push($i);
        }
        $vars = $queue->popAll();
        $this->assertEquals(range(0,20),$vars,"popAll doesn't return the proper data");
        $this->assertEquals(0,count($queue),"Queue should be empty after popAll");
    }
}

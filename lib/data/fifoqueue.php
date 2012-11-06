<?php

namespace Data;

abstract class Queue {

    public abstract function push($data);
    public abstract function pop();
    public abstract function popAll();

}

class FifoQueue extends Queue implements \Countable {

    const QUEUE_UNDERFLOW_EXCEPTION = 0x01;
    const QUEUE_OVERFLOW_EXCEPTION = 0x02;
    const ERR_UNDERFLOW = 1;
    const ERR_OVERFLOW = 2;

    private $maxsize = 0;
    private $flags = 0x00;
    private $queue = array();

    public function __construct($maxsize=0,$flags=0x00) {
        $this->maxsize = $maxsize;
        $this->flags = $flags;
        $this->queue = array();
    }

    public function push($data) {
        array_unshift($this->queue, $data);
        if (($this->maxsize > 0) && (count($this->queue >= $this->maxsize))) {
            if ($this->flags & self::QUEUE_OVERFLOW_EXCEPTION)
                throw new \OutOfBoundsException("FIFO Queue overflow", self::ERR_OVERFLOW);
            $this->queue = array_slice($this->queue,0,$this->maxsize);
        }
    }

    public function pop() {
        if (count($this->queue) == 0) {
            if ($this->flags & self::QUEUE_UNDERFLOW_EXCEPTION)
                throw new \OutOfBoundsException("FIFO Queue underflow", self::ERR_UNDERFLOW);
            return null;
        }
        return array_pop($self->queue);
    }

    public function count() {
        return count($this->queue);
    }

    public function popAll() {
        $qtemp = $this->queue;
        $this->queue = array();
        return array_reverse($qtemp);
    }

    public function peek() {
        return array_reverse($this->queue);
    }

}

class FiloQueue extends Queue implements \Countable {

    const QUEUE_UNDERFLOW_EXCEPTION = 0x01;
    const QUEUE_OVERFLOW_EXCEPTION = 0x02;
    const ERR_UNDERFLOW = 1;
    const ERR_OVERFLOW = 2;

    private $maxsize = 0;
    private $flags = 0x00;
    private $queue = array();

    public function __construct($maxsize=0,$flags=0x00) {
        $this->maxsize = $maxsize;
        $this->flags = $flags;
        $this->queue = array();
    }

    public function push($data) {
        array_push($this->queue, $data);
        if (($this->maxsize>0) && (count($this->queue >= $this->maxsize))) {
            if ($this->flags & self::QUEUE_OVERFLOW_EXCEPTION)
                throw new \OutOfBoundsException("FIFO Queue overflow", self::ERR_OVERFLOW);
            $this->queue = array_slice($this->queue,0,$this->maxsize);
        }
    }

    public function pop() {
        if (count($this->queue) == 0) {
            if ($this->flags & self::QUEUE_UNDERFLOW_EXCEPTION)
                throw new \OutOfBoundsException("FIFO Queue underflow", self::ERR_UNDERFLOW);
            return null;
        }
        return array_pop($self->queue);
    }

    public function count() {
        return count($this->queue);
    }

    public function popAll() {
        $qtemp = $this->queue;
        $this->queue = array();
        return array_reverse($qtemp);
    }

    public function peek() {
        return array_reverse($this->queue);
    }

}

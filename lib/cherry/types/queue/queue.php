<?php

namespace Cherry\Types\Queue;

abstract class Queue {

    public abstract function push($data);
    public abstract function pop();
    public abstract function popAll();
    public abstract function peek();

}


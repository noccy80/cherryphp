<?php

namespace cherry\mvc\view;

abstract class Base {

    protected $isCacheable = false;

    abstract function load($view);

}

class 
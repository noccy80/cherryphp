<?php

namespace cherry\Mvc\Router;

abstract class Base {

    abstract function route(\cherry\Mvc\Request $request);

}


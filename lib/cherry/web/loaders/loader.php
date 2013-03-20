<?php

namespace Cherry\Web\Loaders;

abstract class Loader {
    
    abstract public function load($filename,$output=false);
    
}

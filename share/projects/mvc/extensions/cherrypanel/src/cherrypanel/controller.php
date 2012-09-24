<?php

namespace CherryPanel;
class Controller extends \Cherry\Mvc\Controller\Base {
    
    function index() {
        
        printf("INDEX!");
        
    }
    
    function foo() {
        
        var_dump($this->request);
        
    }
    
}
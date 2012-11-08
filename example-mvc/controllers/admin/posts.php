<?php

namespace CherryTree\Controllers\Admin;
use Cherry\Mvc\Controller;
use Cherry\Mvc\Html;

class PostsController extends Controller {
    
    public function setup() {
        
    }
    
    public function indexAction() {
        
        echo html::p("This is the index!");
        
    }
    
}
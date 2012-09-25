<?php
namespace MyApp\Controllers;

class IndexController extends \cherry\Mvc\Controller\Base {

    protected $view;

    function initialize() {
        $this->view = new \cherry\mvc\view\Frame('frames/default.phtml');
        //$hashlib = $this->load('\cherry\Crypto\Hashing','sha256');
        //printf("Fancy hash: %s\n", $hashlib->hash(time()));
    }

    public function index() {
        $this->view->load('index/index.phtml');
    }
    
    public function start() {
        $this->view->load('index/start.phtml');
    }
    
    protected function onError($error) {
        $this->view->setData('An error occured: %s',$error->getMessage());
    }

}

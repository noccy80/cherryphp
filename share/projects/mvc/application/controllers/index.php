<?php

namespace MyApp\Controllers;
class IndexController extends \Lepton\Mvc\Controller\Base {

    protected function initialize() {
        $this->view = new \cherry\mvc\view\Frame('frames/default.phtml');
        //$hashlib = $this->load('\Lepton\Crypto\Hashing','sha256');
        //printf("Fancy hash: %s\n", $hashlib->hash(time()));
    }

    public function index() {
        $this->view->load('index/index.phtml');
    }
    
    protected function onError($error) {
        $this->view->setData('An error occured: %s',$error->getMessage());
    }

}

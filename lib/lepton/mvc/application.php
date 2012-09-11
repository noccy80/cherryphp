<?php

namespace Lepton\Mvc;

class Application extends \Lepton\Application {

    function run() {
        $cfg = $this->getConfiguration('application','application');
        if (!empty($cfg['mvc.router'])) {
            $router = $cfg['mvc.router'];
        } else {
            $router = '\Lepton\Mvc\Router\Simple';
        }
        require_once('lib/lepton/mvc/router.php');
        require_once('lib/lepton/mvc/request.php');
        require_once('lib/lepton/mvc/controller.php');
        //require_once('../app/router.php');
        // printf("Linking router %s\n",$router);

        // Create the router object
        $robj = new $router();
        $req = new \Lepton\Mvc\Request();
        $robj->route($req);
    }

}

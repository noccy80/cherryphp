<?php

namespace cherry\Mvc;

class Application extends \cherry\Application {

    function run() {
        $cfg = $this->getConfiguration('application','application');
        if (!empty($cfg['mvc.router'])) {
            $router = $cfg['mvc.router'];
        } else {
            $router = '\cherry\Mvc\Router\Simple';
        }
        require_once('lib/cherry/mvc/router.php');
        require_once('lib/cherry/mvc/request.php');
        require_once('lib/cherry/mvc/controller.php');
        //require_once('../app/router.php');
        // printf("Linking router %s\n",$router);

        // Create the router object
        $robj = new $router();
        $req = new \cherry\Mvc\Request();
        $robj->route($req);
    }

}

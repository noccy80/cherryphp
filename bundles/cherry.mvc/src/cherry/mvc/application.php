<?php

namespace cherry\Mvc;

use Cherry\Extension\ExtensionManager;
use Cherry\Extension\ExtensionException;

class Application extends \cherry\Application {

    function run() {
        $cfg = $this->getConfiguration('application','application');
        if (!empty($cfg['mvc.router'])) {
            $router = $cfg['mvc.router'];
        } else {
            $router = '\cherry\Mvc\Router\Simple';
        }
        //require_once('lib/cherry/mvc/router.php');
        //require_once('lib/cherry/mvc/request.php');
        //require_once('lib/cherry/mvc/controller.php');
        //require_once('../app/router.php');
        // printf("Linking router %s\n",$router);

        $this->loadExtensions();
        
        // Create the router object
        $robj = new $router();
        $req = new \cherry\Mvc\Request();
        $robj->route($req);
    }
    
    private function loadExtensions() {
        
        $sFile = CHERRY_APP._DS_.'data'._DS_.'extensions.json';
        if (file_exists($sFile)) {
            $cfg = json_decode(file_get_contents($sFile));
            foreach((array)$cfg->enabled as $extn) {
                ExtensionManager::load($extn);
            }
        }
        
    }

}

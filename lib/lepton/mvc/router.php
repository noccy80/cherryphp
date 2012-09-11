<?php

namespace Lepton\Mvc\Router;

abstract class Base {

    abstract function route(\Lepton\Mvc\Request $request);

}

class Simple extends Base {

    function route(\Lepton\Mvc\Request $request) {
        $cobj = \Lepton\Mvc\Controller\Base::factory('\MyApp\Controllers\IndexController',$request);
        $cobj->index();
    }

}

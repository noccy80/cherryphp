<?php

namespace cherry\Mvc\Router;

abstract class Base {

    abstract function route(\cherry\Mvc\Request $request);

}

class Simple extends Base {

    function route(\cherry\Mvc\Request $request) {
        $cobj = \cherry\Mvc\Controller\Base::factory('\MyApp\Controllers\IndexController',$request);
        $cobj->index();
    }

}

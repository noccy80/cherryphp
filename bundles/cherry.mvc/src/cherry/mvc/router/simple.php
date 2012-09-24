<?php

namespace cherry\Mvc\Router;

class Simple extends Base {

    function route(\cherry\Mvc\Request $request) {
        $cobj = $this->checkStaticRoutes($request);
        if (!$cobj) {
            $cobj = \cherry\Mvc\Controller\Base::factory('\MyApp\Controllers\IndexController',$request);
        }
        if (is_object($cobj)) $cobj->invoke();
    }

}
